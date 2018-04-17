<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * MessageCollection is used for work with mail messages.
 * 
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 * 
 * @package Mail
 * @subpackage Classes
 */
class MessageCollection extends \MailSo\Base\Collection
{
	/**
	 * Number of messages in the folder.
	 * 
	 * @var int
	 */
	public $MessageCount;

	/**
	 * Number of unread mails in the folder.
	 * 
	 * @var int
	 */
	public $MessageUnseenCount;

	/**
	 * Number of messages returned upon running search.
	 * 
	 * @var int
	 */
	public $MessageResultCount;

	/**
	 * Full name of the folder.
	 * 
	 * @var string
	 */
	public $FolderName;

	/**
	 * Along with **Limit**, denotes a range of message list to retrieve.
	 * 
	 * @var int
	 */
	public $Offset;

	/**
	 * Along with **Offset**, denotes a range of message list to retrieve.
	 * 
	 * @var int
	 */
	public $Limit;

	/**
	 * Denotes search string.
	 * 
	 * @var string
	 */
	public $Search;

	/**
	 * Denotes message lookup type. Typical use case is search in Starred folder.
	 * 
	 * @var string
	 */
	public $Filters;

	/**
	 * List of message UIDs.
	 * 
	 * @var array
	 */
	public $Uids;

	/**
	 * UIDNEXT value for the current folder.
	 * 
	 * @var string
	 */
	public $UidNext;

	/**
	 * Value which changes if any folder parameter, such as message count, was changed.
	 * 
	 * @var string
	 */
	public $FolderHash;

	/**
	 * List of information about new messages. $UidNext is used for obtaining this information.
	 * 
	 * @var array
	 */
	public $New;

	/**
	 * Initializes collection properties.
	 * 
	 * @return void
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->clear();
	}

	/**
	 * Removes all messages from the collection.
	 * 
	 * @return MessageCollection
	 */
	public function clear()
	{
		parent::clear();

		$this->MessageCount = 0;
		$this->MessageUnseenCount = 0;
		$this->MessageResultCount = 0;

		$this->FolderName = '';
		$this->Offset = 0;
		$this->Limit = 0;
		$this->Search = '';
		$this->Filters = '';

		$this->UidNext = '';
		$this->FolderHash = '';
		$this->Uids = array();

		$this->New = array();

		return $this;
	}

	/**
	 * Creates new instance of the object.
	 * 
	 * @return MessageCollection
	 */
	public static function createInstance()
	{
		return new self();
	}
	
	public function toResponseArray($aParameters = array()) {
		return array_merge(
				\Aurora\System\Managers\Response::CollectionToResponseArray($this, $aParameters), 
				array(
					'Uids' => $this->Uids,
					'UidNext' => $this->UidNext,
					'FolderHash' => $this->FolderHash,
					'MessageCount' => $this->MessageCount,
					'MessageUnseenCount' => $this->MessageUnseenCount,
					'MessageResultCount' => $this->MessageResultCount,
					'FolderName' => $this->FolderName,
					'Offset' => $this->Offset,
					'Limit' => $this->Limit,
					'Search' => $this->Search,
					'Filters' => $this->Filters,
					'New' => $this->New
				)				
		);
	}
}