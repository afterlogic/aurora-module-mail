<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

use MailSo\Base\Utils as BaseUtils;

/**
 * Folder class is used for operations with a folder.
 
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 * 
 * @package Mail
 * @subpackage Classes
 */
class Folder
{
	/**
	 * Level of depth at which the folder is located.
	 * 
	 * @var int
	 */
	protected $iNestingLevel;

	/**
	 * Type of folder: 1 = Inbox; 2 = Sent; 3 = Drafts; 4 = Spam; 5 = Trash; 6 = Quarantine; 9 = System; 10 = Custom; 0 = generic folder.
	 * 
	 * @var int
	 */
	protected $iType;

	/**
	 * If **true** the folder exists on IMAP server.
	 * 
	 * @var bool
	 */
	protected $bExists;

	/**
	 * If **true** the folder is subscribed on IMAP server.
	 * 
	 * @var bool
	 */
	protected $bSubscribed;

	/**
	 * ImapFolder object.
	 * 
	 * @var \MailSo\Imap\Folder
	 */
	protected $oImapFolder;

	/**
	 * Collection of subfolders belonging to the folder.
	 * 
	 * @var \Aurora\Modules\Mail\Classes\FolderCollection
	 */
	protected $oSubFolders;

	/**
	 * @var bool
	 */
	public $bAlwaysRefresh;

	/**
	 * Fills in the required data first.
	 * 
	 * @param \MailSo\Imap\Folder $oImapFolder ImapFolder object.
	 * @param bool $bSubscribed = true. If **true** the folder is subscribed on IMAP server.
	 * @param bool $bExists = true. If **true** the folder exists on IMAP server.
	 * 
	 * @return void
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	protected function __construct($oImapFolder, $bSubscribed = true, $bExists = true)
	{
		if ($oImapFolder instanceof \MailSo\Imap\Folder)
		{
			$this->oImapFolder = $oImapFolder;
			$this->oSubFolders = null;
			$this->iType = \Aurora\Modules\Mail\Enums\FolderType::Custom;
			$this->iDeep = 0;
			
			$aNamesRaw = explode($this->oImapFolder->Delimiter(), $this->oImapFolder->FullNameRaw());
			$this->iNestingLevel = count($aNamesRaw);

			if (1 === $this->iNestingLevel && 'INBOX' === strtoupper($this->oImapFolder->FullNameRaw()))
			{
				$this->iType = \Aurora\Modules\Mail\Enums\FolderType::Inbox;
			}

			$this->bSubscribed = $bSubscribed;
			$this->bExists = $bExists;
			$this->bAlwaysRefresh = false;
		}
		else
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}
	}

	/**
	 * Creates new instance of the object. 
	 * 
	 * @param \MailSo\Imap\Folder $oImapFolder ImapFolder object.
	 * @param bool $bSubscribed = true. If **true** the folder is subscribed on IMAP server.
	 * @param bool $bExists = true. If **true** the folder exists on IMAP server.
	 *
	 * @return Folder
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public static function createInstance($oImapFolder, $bSubscribed = true, $bExists = true)
	{
		return new self($oImapFolder, $bSubscribed, $bExists);
	}

	/**
	 * Creates new instance of the object which represents folder not present on IMAP. 
	 * 
	 * @param string $sFullNameRaw Raw full name of the folder.
	 * @param string $sDelimiter Symbol wich is used as delimiter in folder full name on IMAP server.
	 *
	 * @return Folder
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	public static function createNonexistentInstance($sFullNameRaw, $sDelimiter)
	{
		return self::createInstance(
			\MailSo\Imap\Folder::NewInstance($sFullNameRaw, $sDelimiter, array('/Noselect')), true, false);
	}

	/**
	 * Returns type of the folder.
	 * 
	 * @return int 1 = Inbox; 2 = Sent; 3 = Drafts; 4 = Spam; 5 = Trash; 6 = Quarantine; 9 = System; 10 = Custom; 0 = generic folder. 
	 */
	public function getType()
	{
		return $this->iType;
	}

	/**
	 * Returns the depth value of the folder in the folders tree.
	 * 
	 * @return int
	 */
	public function getNestingLevel()
	{
		return $this->iNestingLevel;
	}

	/**
	 * Changes type of the folder.
	 * 
	 * @param int $iType 1 = Inbox; 2 = Sent; 3 = Drafts; 4 = Spam; 5 = Trash; 6 = Quarantine; 9 = System; 10 = Custom; 0 = generic folder. 
	 *
	 * @return Folder
	 */
	public function setType($iType)
	{
		$this->iType = $iType;

		return $this;
	}

	/**
	 * Returns name of the folder.
	 * 
	 * @return string
	 */
	public function getName()
	{
		return BaseUtils::ConvertEncoding($this->getRawName(),
			\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
			\MailSo\Base\Enumerations\Charset::UTF_8);
	}

	/**
	 * Returns full name of the folder.
	 * 
	 * @return string
	 */
	public function getFullName()
	{
		return BaseUtils::ConvertEncoding($this->getRawFullName(),
			\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
			\MailSo\Base\Enumerations\Charset::UTF_8);
	}

	/**
	 * Returns name of the folder with encoding used on IMAP level.
	 * 
	 * @return string
	 */
	public function getRawName()
	{
		return $this->oImapFolder->NameRaw();
	}

	/**
	 * Returns full name of the folder with encoding used on IMAP level.
	 * 
	 * @return string
	 */
	public function getRawFullName()
	{
		return $this->oImapFolder->FullNameRaw();
	}

	/**
	 * Returns a character used as delimiter in full names of IMAP folders.
	 * 
	 * @return string
	 */
	public function getDelimiter()
	{
		return $this->oImapFolder->Delimiter();
	}

	/**
	 * Return list of subfolders belonging to the folder.
	 * 
	 * @param bool $bCreateIfNull = false. If **true** the collection will be created even if there are no subfolders present.
	 *
	 * @return \Aurora\Modules\Mail\Classes\FolderCollection
	 */
	public function getSubFolders($bCreateIfNull = false)
	{
		if ($bCreateIfNull && !$this->oSubFolders)
		{
			$this->oSubFolders = \Aurora\Modules\Mail\Classes\FolderCollection::createInstance();
		}

		return $this->oSubFolders;
	}

	/**
	 * Returns **true** if the folder has at least one subfolder and **false** otherwise.
	 * 
	 * @return bool
	 */
	public function hasSubFolders()
	{
		return $this->oSubFolders && 0 < $this->oSubFolders->Count();
	}

	/**
	 * Returns imap folder status - information about total messages count, unseen messages count and value of uidnext.
	 * 
	 * @return mixed
	 */
	public function getStatus()
	{
		return $this->oImapFolder->GetExtended('STATUS');
	}

	/**
	 * Returns **true** if the folder is subscribed and thus visible in folders tree.
	 * 
	 * @return bool
	 */
	public function isSubscribed()
	{
		return $this->bSubscribed;
	}

	/**
	 * Returns **true** if the folder exists on IMAP.
	 * 
	 * @return bool
	 */
	public function exists()
	{
		return $this->bExists;
	}

	/**
	 * Returns **true** if the folder can be selected.
	 * 
	 * @return bool
	 */
	public function isSelectable()
	{
		return $this->oImapFolder->IsSelectable() && $this->exists();
	}

	/**
	 * Returns a number which denotes folder type. The value is retrieved using IMAP XLIST extension.
	 * 
	 * @return int 1 = Inbox; 2 = Sent; 3 = Drafts; 4 = Spam; 5 = Trash; 6 = Quarantine; 9 = System; 10 = Custom; 0 = generic 
	 */
	public function getFolderXListType()
	{
		$aFlags = $this->oImapFolder->FlagsLowerCase();
		$iXListType = \Aurora\Modules\Mail\Enums\FolderType::Custom;

		if (is_array($aFlags))
		{
			switch (true)
			{
				case in_array('\inbox', $aFlags):
					$iXListType = \Aurora\Modules\Mail\Enums\FolderType::Inbox;
					break;
				case in_array('\sent', $aFlags):
					$iXListType = \Aurora\Modules\Mail\Enums\FolderType::Sent;
					break;
				case in_array('\drafts', $aFlags):
					$iXListType = \Aurora\Modules\Mail\Enums\FolderType::Drafts;
					break;
				case in_array('\junk', $aFlags):
				case in_array('\spam', $aFlags):
					$iXListType = \Aurora\Modules\Mail\Enums\FolderType::Spam;
					break;
				case in_array('\bin', $aFlags):
				case in_array('\trash', $aFlags):
					$iXListType = \Aurora\Modules\Mail\Enums\FolderType::Trash;
					break;
			}
		}

		return $iXListType;
	}
	
	public function toResponseArray($aParameters = array())
	{
		$aExtended = null;
		$mStatus = $this->getStatus();
		if (is_array($mStatus) && isset($mStatus['MESSAGES'], $mStatus['UNSEEN'], $mStatus['UIDNEXT']))
		{
			$aExtended = array(
				'MessageCount' => (int) $mStatus['MESSAGES'],
				'MessageUnseenCount' => (int) $mStatus['UNSEEN'],
				'UidNext' => (string) $mStatus['UIDNEXT'],
				'Hash' => Utils::GenerateFolderHash(
					$this->getRawFullName(), $mStatus['MESSAGES'], $mStatus['UNSEEN'], $mStatus['UIDNEXT']
				)
			);
		}

		return array(
			'Type' => $this->getType(),
			'Name' => $this->getName(),
			'FullName' => $this->getFullName(),
			'FullNameRaw' => $this->getRawFullName(),
			'FullNameHash' => md5($this->getRawFullName()),
			'Delimiter' => $this->getDelimiter(),
			'IsSubscribed' => $this->isSubscribed(),
			'IsSelectable' => $this->isSelectable(),
			'Exists' => $this->exists(),
			'Extended' => $aExtended,
			'SubFolders' => \Aurora\System\Managers\Response::GetResponseObject($this->getSubFolders()),
			'AlwaysRefresh' => $this->bAlwaysRefresh
		);		
	}
}
