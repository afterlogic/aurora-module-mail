<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * Collection for work with mail folders.
 * 
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 * 
 * @package Mail
 * @subpackage Classes
 */
class FolderCollection extends \MailSo\Base\Collection
{
	/**
	 * Information about folder wich includes root folders. If empty there is no such folder.
	 * 
	 * @var string
	 */
	protected $sNamespace;

	/**
	 * Initializes collection property.
	 * 
	 * @return void
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->sNamespace = '';
	}

	/**
	 * Creates new instance of the object.
	 * 
	 * @return FolderCollection
	 */
	public static function createInstance()
	{
		return new self();
	}

	/**
	 * Locates folder within the collection by its full name.
	 * 
	 * @param string $sFullNameRaw Raw full name of the folder.
	 * @param bool $bRec = false. If **true**, full recursive search is performed.
	 *
	 * @return \Aurora\Modules\Mail\Classes\Folder | null
	 */
	public function &getFolder($sFullNameRaw, $bRec = false)
	{
		$mResult = null;
		foreach ($this->aItems as /* @var $oFolder \Aurora\Modules\Mail\Classes\Folder */ $oFolder)
		{
			if ($oFolder->getRawFullName() === $sFullNameRaw)
			{
				$mResult = $oFolder;
				break;
			}
			else if ($bRec && $oFolder->hasSubFolders())
			{
				$mResult = $oFolder->getSubFolders()->getFolder($sFullNameRaw, true);
				if ($mResult)
				{
					break;
				}
			}
		}

		return $mResult;
	}

	/**
	 * Returns namespace of folders.
	 * 
	 * @return string
	 */
	public function getNamespace()
	{
		return $this->sNamespace;
	}

	/**
	 * Modifies namespace information of folders.
	 * 
	 * @param string $sNamespace New namespace string.
	 *
	 * @return FolderCollection
	 */
	public function setNamespace($sNamespace)
	{
		$this->sNamespace = $sNamespace;

		$this->MapList(function ($oFolder) use ($sNamespace) {
			if ($oFolder && $oFolder->getSubFolders())
			{
				$oFolder->getSubFolders()->setNamespace($sNamespace);
			}
		});

		return $this;
	}

	/**
	 * Compares items for sorting.
	 * 
	 * @param \Aurora\Modules\Mail\Classes\Folder $oFolderA First item to compare.
	 * @param \Aurora\Modules\Mail\Classes\Folder $oFolderB Second item to compare.
	 *
	 * @return int
	 */
	protected function _sortHelper($oFolderA, $oFolderB)
	{
		return strnatcmp($oFolderA->getFullName(), $oFolderB->getFullName());
	}

	/**
	 * Initializes collection by unsorted mail folders.
	 * 
	 * @param array $aUnsortedMailFolders Unsorted mail folder list.
	 * 
	 * @return void
	 */
	public function initialize($aUnsortedMailFolders)
	{
		$this->clear();

		$aSortedByLenImapFolders = array();
		foreach ($aUnsortedMailFolders as /* @var $oMailFolder \Aurora\Modules\Mail\Classes\Folder */ &$oMailFolder)
		{
			$aSortedByLenImapFolders[$oMailFolder->getRawFullName()] =& $oMailFolder;
			unset($oMailFolder);
		}
		unset($aUnsortedMailFolders);

		$aAddedFolders = array();
		foreach ($aSortedByLenImapFolders as /* @var $oMailFolder \Aurora\Modules\Mail\Classes\Folder */ $oMailFolder)
		{
			$sDelimiter = $oMailFolder->getDelimiter();
			$aFolderExplode = explode($sDelimiter, $oMailFolder->getRawFullName());

			if (1 < count($aFolderExplode))
			{
				array_pop($aFolderExplode);

				$sNonExistenFolderFullNameRaw = '';
				foreach ($aFolderExplode as $sFolderExplodeItem)
				{
					$sNonExistenFolderFullNameRaw .= (0 < strlen($sNonExistenFolderFullNameRaw))
						? $sDelimiter.$sFolderExplodeItem : $sFolderExplodeItem;

					if (!isset($aSortedByLenImapFolders[$sNonExistenFolderFullNameRaw]))
					{
						$aAddedFolders[$sNonExistenFolderFullNameRaw] =
							\Aurora\Modules\Mail\Classes\Folder::createNonexistentInstance($sNonExistenFolderFullNameRaw, $sDelimiter);
					}
				}
			}
		}

		$aSortedByLenImapFolders = array_merge($aSortedByLenImapFolders, $aAddedFolders);
		unset($aAddedFolders);

		uasort($aSortedByLenImapFolders, array(&$this, '_sortHelper'));

		// INBOX and Utf-7 modified sort
		$aFoot = $aTop = array();
		foreach ($aSortedByLenImapFolders as $sKey => /* @var $oMailFolder \Aurora\Modules\Mail\Classes\Folder */ &$oMailFolder)
		{
			if (0 === strpos($sKey, '&'))
			{
				$aFoot[] = $oMailFolder;
				unset($aSortedByLenImapFolders[$sKey]);
			}
			else if ('INBOX' === strtoupper($sKey))
			{
				array_unshift($aTop, $oMailFolder);
				unset($aSortedByLenImapFolders[$sKey]);
			}
			else if ('[GMAIL]' === strtoupper($sKey))
			{
				$aTop[] = $oMailFolder;
				unset($aSortedByLenImapFolders[$sKey]);
			}
		}

		$aSortedByLenImapFolders = array_merge($aTop, $aSortedByLenImapFolders, $aFoot);

		foreach ($aSortedByLenImapFolders as /* @var $oMailFolder \Aurora\Modules\Mail\Classes\Folder */ &$oMailFolder)
		{
			$this->addFolder($oMailFolder);
			unset($oMailFolder);
		}

		unset($aSortedByLenImapFolders);
	}
	
	/**
	 * Sorts folders of the collection using custom callback function. 
	 * 
	 * @param callable $fCallback Custom callback function for sorting.
	 * 
	 * @return void
	 */
	public function sort($fCallback)
	{
		if (is_callable($fCallback))
		{
			$aList =& $this->GetAsArray();

			usort($aList, $fCallback);

			foreach ($aList as &$oItemFolder)
			{
				if ($oItemFolder->hasSubFolders())
				{
					$oItemFolder->getSubFolders()->sort($fCallback);
				}
			}
		}
	}

	/**
	 * Searches suitable position in collection and adds mail folder.
	 * 
	 * @param \Aurora\Modules\Mail\Classes\Folder $oMailFolder Mail folder for adding to collection.
	 * 
	 * @return bool Returns **true** if folder was added.
	 */
	public function addFolder($oMailFolder)
	{
		$oItemFolder = null;
		$bIsAdded = false;
		$aList =& $this->GetAsArray();

		foreach ($aList as /* @var $oItemFolder Folder */ $oItemFolder)
		{
			if ($oMailFolder instanceof \Aurora\Modules\Mail\Classes\Folder &&
				0 === strpos($oMailFolder->getRawFullName(), $oItemFolder->getRawFullName().$oItemFolder->getDelimiter()))
			{
				if ($oItemFolder->getSubFolders(true)->addFolder($oMailFolder))
				{
					$bIsAdded = true;
				}

				break;
			}
		}

		if (!$bIsAdded && $oMailFolder instanceof \Aurora\Modules\Mail\Classes\Folder)
		{
			$bIsAdded = true;
			$this->Add($oMailFolder);
		}

		return $bIsAdded;
	}

	/**
	 * Iterates through list of folders including all the subfolders.
	 * 
	 * @param mixed $mCallback Callback function which can be run on each loop iteration.
	 * 
	 * @return void
	 */
	public function foreachWithSubFolders($mCallback)
	{
		if (\is_callable($mCallback))
		{
			$this->ForeachList(function (/* @var $oFolder \Aurora\Modules\Mail\Classes\Folder */ $oFolder) use ($mCallback) {
				if ($oFolder)
				{
					\call_user_func($mCallback, $oFolder);

					if ($oFolder->hasSubFolders())
					{
						$oFolder->getSubFolders()->foreachWithSubFolders($mCallback);
					}
				}
			});
		}
	}

	/**
	 * Iterates through list of folders including only root folders, first childs of Inbox and first childs of special GMail account root folder.
	 * 
	 * @param mixed $mCallback Callback function which can be run on each loop iteration.
	 * 
	 * @return void
	 */
	public function foreachOnlyRoot($mCallback)
	{
		$oInboxFolder = $this->getFolder('INBOX');
		if ($oInboxFolder && $oInboxFolder->hasSubFolders())
		{
			$oInboxFolder->getSubFolders()->ForeachList($mCallback);
		}

		$oGmailFolder = $this->getFolder('[Gmail]');
		if ($oGmailFolder && $oGmailFolder->hasSubFolders())
		{
			$oGmailFolder->getSubFolders()->ForeachList($mCallback);
		}

		$this->ForeachList($mCallback);
	}
}
