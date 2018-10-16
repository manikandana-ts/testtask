<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
	/**
	 * @var \Mailchimp\Mailchimp
	 */
	private $mailChimp;
	
	/**
	 * ListsController constructor.
	 *
	 * @param \Doctrine\ORM\EntityManagerInterface $entityManager
	 * @param \Mailchimp\Mailchimp $mailchimp
	 */
	public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
	{
		parent::__construct($entityManager);
		
		$this->mailChimp = $mailchimp;
	}
	
	/**
	 * Create Member/Contact in MailChimp list.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param string $listId
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function create(Request $request, string $listId): JsonResponse
	{
		/** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
		$list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
		if ($list === null) {
			return $this->errorResponse(
				['message' => \sprintf('MailChimpList[%s] not found', $listId)],
				404
			);
		}
		
		// Instantiate entity
		$member = new MailChimpListMember();
		// Set emailaddress
		$member->setEmailAddress($request->input('email_address'));
		// Set status
		$member->setStatus($request->input('status'));
		// Set the mail_chip_id from above list
		$member->setMailChimpId($list->getMailChimpId());
		// set the list to memeber
		$member->addList($list);
		
		// Validate entity
		$validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());
		
		if ($validator->fails()) {
			// Return error response if validation failed
			return $this->errorResponse([
				'message' => 'Invalid data given',
				'errors' => $validator->errors()->toArray()
			]);
		}
		
		try {
			// Save member into db
			$this->saveEntity($member);
			// Save member into MailChimp list
			$response = $this->mailChimp->post('lists/'.$list->getMailChimpId().'/members', $member->toMailChimpArray());
			// Set MailChimp member id on the list and save member into db
			$this->saveEntity($member->setMailChimpMemberId($response->get('id')));
		} catch (Exception $exception) {
			// Return error response if something goes wrong
			return $this->errorResponse(['message' => $exception->getMessage()]);
		}
		
		return $this->successfulResponse($member->toArray());
	}
	
	/**
	 * Remove MailChimp Member.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param string $listId
	 * @param string $memberId
	 * @param string $removeFlag
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function remove(Request $request, string $listId, string $memberId, string $removeFlag = null): JsonResponse
	{
		/** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
		$list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
		if ($list === null) {
			return $this->errorResponse(
				['message' => \sprintf('MailChimpList[%s] not found', $listId)],
				404
			);
		}
		
		/** @var \App\Database\Entities\MailChimp\MailChimpListMember|null $memberId */
		$member = $this->entityManager->getRepository(MailChimpListMember::class)->find($memberId);
		
		if ($member === null) {
			return $this->errorResponse(
				['message' => \sprintf('MailChimpListMember[%s] not found', $memberId)],
				404
			);
		}
		
		try {
			// Remove member/ Remove member permanently from MailChimp
			if($request->isMethod('post') && $removeFlag == 'delete' ){
				$this->mailChimp->post(\sprintf('lists/%s/members/%s/actions/delete-permanent', $list->getMailChimpId(), $member->getMailChimpMemberId()));
			}else{
				$this->mailChimp->delete(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpMemberId()));
			}
			// Remove member from database
			$this->removeEntity($member);
		} catch (Exception $exception) {
			return $this->errorResponse(['message' => $exception->getMessage()]);
		}
		
		return $this->successfulResponse([]);
	}
	
	/**
	 * Retrieve and return MailChimp Member list.
	 *
	 * @param string $listId
	 * @param string $memberId
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(string $listId, string $memberId = null): JsonResponse
	{
		if(!empty($memberId)){
			/** @var \App\Database\Entities\MailChimp\MailChimpListMember|null $memberId */
			$member = $this->entityManager->getRepository(MailChimpListMember::class)->find($memberId);
			
			if ($member === null) {
				return $this->errorResponse(
					['message' => \sprintf('MailChimpListMember[%s] not found', $memberId)],
					404
				);
			}
			
			return $this->successfulResponse($member->toArray());
		}else{
			/** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
			$list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
			if ($list === null) {
				return $this->errorResponse(
					['message' => \sprintf('MailChimpList[%s] not found', $listId)],
					404
				);
			}
			
			/** @var \App\Database\Entities\MailChimp\MailChimpListMember|null $listId */
			$members = $this->entityManager->getRepository(MailChimpListMember::class)->findBy(['mailChimpId' => $list->getMailChimpId()]);
			
			if ($members === null || count($members)==0) {
				return $this->errorResponse(
					['message' => \sprintf('MailChimpList[%s] not found', $listId)],
					404
				);
			}
			
			$returnData = array();
			// Iterate the members list
			foreach($members as $key => $member){
				$returnData[$key] = $member->toArray();
			}
			
			return $this->successfulResponse($returnData);
		}
		
	}
	
	/**
	 * Update MailChimp list.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param string $listId
	 * @param string $memberId
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update(Request $request, string $listId, string $memberId): JsonResponse
	{
		/** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
		$list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
		
		if ($list === null) {
			return $this->errorResponse(
				['message' => \sprintf('MailChimpList[%s] not found', $listId)],
				404
			);
		}
		
		/** @var \App\Database\Entities\MailChimp\MailChimpListMember|null $memberId */
		$member = $this->entityManager->getRepository(MailChimpListMember::class)->find($memberId);
		
		if ($member === null) {
			return $this->errorResponse(
				['message' => \sprintf('MailChimpListMember[%s] not found', $memberId)],
				404
			);
		}
		
		// Update member properties
		$member->fill($request->all());
		
		// Validate entity
		$validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());
		
		if ($validator->fails()) {
			// Return error response if validation failed
			return $this->errorResponse([
				'message' => 'Invalid data given',
				'errors' => $validator->errors()->toArray()
			]);
		}
		
		try {
			// Update list into MailChimp
			if($request->isMethod('patch')){
				$this->mailChimp->patch(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpMemberId()), $member->toMailChimpArray());
			}else{
				$this->mailChimp->put(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpMemberId()), $member->toMailChimpArray());
			}
			
			// Update list into database
			$this->saveEntity($member);
			
		} catch (Exception $exception) {
			return $this->errorResponse(['message' => $exception->getMessage()]);
		}
		
		return $this->successfulResponse($member->toArray());
	}
}
