<?php
declare(strict_types=1);

namespace App\Database\Entities\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\JoinColumn;
use EoneoPay\Utils\Str;

/**
 * @ORM\Entity()
 */
class MailChimpListMember extends MailChimpEntity
{
	/**
	 * @ORM\Column(name="email_address", type="string")
	 *
	 * @var string
	 */
	private $emailAddress;
	
	/**
	 * @ORM\Column(name="email_type", type="string", nullable=true)
	 *
	 * @var string
	 */
	private $emailType;
	
	/**
	 * @ORM\Column(name="status", type="string", nullable=true)
	 *
	 * @var string
	 */
	private $status;
	
	/**
	 * @ORM\Column(name="merge_fields", type="array")
	 *
	 * @var array
	 */
	private $mergeFields;
	
	/**
	 * @ORM\Column(name="interests", type="array")
	 *
	 * @var array
	 */
	private $interests;
	
	/**
	 * @ORM\Column(name="ip_signup", type="string", nullable=true)
	 *
	 * @var string
	 */
	private $ipSignup;
	
	/**
	 * @ORM\Column(name="unsubscribe_reason", type="string", nullable=true)
	 *
	 * @var string
	 */
	private $unsubscribeReason;
	
	/**
	 * @ORM\Column(name="email_client", type="string", nullable=true)
	 *
	 * @var string
	 */
	private $emailClient;
	
	/**
	 * @ORM\Column(name="tags", type="array")
	 *
	 * @var array
	 */
	private $tags;
	
	/**
	 * @ORM\Column(name="stats", type="array")
	 *
	 * @var array
	 */
	private $stats;
	
	/**
	 * @ORM\Column(name="mail_chimp_member_id", type="string", nullable=true)
	 *
	 * @var string
	 */
	private $mailChimpMemberId;
	
	/**
	 * @ORM\Column(name="mail_chimp_id", type="string", nullable=true)
	 *
	 * @var string
	 */
	private $mailChimpId;
	
	/**
	 * @ORM\Id()
	 * @ORM\Column(name="id", type="guid")
	 * @ORM\GeneratedValue(strategy="UUID")
	 *
	 * @var string
	 */
	private $memberId;
	
	/**
	 * Many MailChimpListMember have Many MailChimpList.
	 * @ManyToMany(targetEntity="MailChimpList")
	 * @JoinTable(name="mail_chimp_list_members_group",
	 *      joinColumns={@JoinColumn(name="member_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@JoinColumn(name="list_id", referencedColumnName="id")}
	 *      )
	 */
	private $lists;
	
	public function __construct() {
		parent::__construct();
		$this->lists = new ArrayCollection();
	}
	
	/**
	 * Get id.
	 *
	 * @return null|string
	 */
	public function getId(): ?string
	{
		return $this->memberId;
	}
	
	/**
	 * Get mailchimp id of the list.
	 *
	 * @return null|string
	 */
	public function getMailChimpId(): ?string
	{
		return $this->mailChimpId;
	}
	
	/**
	 * Get mailchimp member id from the list.
	 *
	 * @return null|string
	 */
	public function getMailChimpMemberId(): ?string
	{
		return $this->mailChimpMemberId;
	}
	
	/**
	 * Get validation rules for mailchimp entity.
	 *
	 * @return array
	 */
	public function getValidationRules(): array
	{
		return [
			'email_address' => 'required|string',
			'email_type' => 'nullable|string|in:html,text',
			'status' => 'required|string|in:subscribed,unsubscribed,cleaned,pending',
			'lists' => 'required|exists:App\Database\Entities\MailChimp\MailChimpList,listId',
			'mail_chimp_id' => 'required|exists:App\Database\Entities\MailChimp\MailChimpList,mailChimpId',
		];
	}
	
	/**
	 * Set email_address for Member.
	 *
	 * @param string $emailAddress
	 *
	 * @return MailChimpListMember
	 */
	public function setEmailAddress(string $emailAddress): MailChimpListMember
	{
		$this->emailAddress = $emailAddress;
		
		return $this;
	}
	
	/**
	 * Get email_address for Member.
	 *
	 * @return string
	 */
	public function getEmailAddress()
	{
		return $this->emailAddress;
	}
	
	/**
	 * Set status for Member.
	 *
	 * @param string $status
	 *
	 * @return MailChimpListMember
	 */
	public function setStatus(string $status): MailChimpListMember
	{
		$this->status = $status;
		
		return $this;
	}
	
	/**
	 * Set list for Member.
	 *
	 * @param MailChimpList $list
	 *
	 * @return MailChimpListMember
	 */
	public function addList(MailChimpList $list): MailChimpListMember
	{
		$this->lists[] = $list;
		
		return $this;
	}
	
	/**
	 * Remove list
	 *
	 * @param MailChimpList $list
	 *
	 * @return MailChimpListMember
	 */
	public function removeList(MailChimpList $list): MailChimpListMember
	{
		$this->lists->removeElement($list);
		return $this;
	}
	
	/**
	 * Get list
	 *
	 * @return ArrayCollection
	 */
	public function getList()
	{
		return $this->lists;
	}
	
	/**
	 * Set mailchimp id of the list.
	 *
	 * @param string $mailChimpId
	 *
	 * @return MailChimpListMember
	 */
	public function setMailChimpId(string $mailChimpId): MailChimpListMember
	{
		$this->mailChimpId = $mailChimpId;
		
		return $this;
	}
	
	/**
	 * Set mailchimp member id of the list.
	 *
	 * @param string $mailChimpMemberId
	 *
	 * @return \App\Database\Entities\MailChimp\MailChimpList
	 */
	public function setMailChimpMemberId(string $mailChimpMemberId): MailChimpListMember
	{
		$this->mailChimpMemberId = $mailChimpMemberId;
		
		return $this;
	}
	
	/**
	 * Get array representation of entity.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$array = [];
		$str = new Str();
		
		foreach (\get_object_vars($this) as $property => $value) {
			$array[$str->snake($property)] = $value;
		}
		
		return $array;
	}
}
