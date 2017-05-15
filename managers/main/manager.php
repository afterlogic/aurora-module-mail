<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

use Aurora\System\Exceptions;

/**
 * Manager for work with ImapClient.
 * 
 * @package Mail
 */
class CApiMailMainManager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @var array List of ImapClient objects.
	 */
	protected $aImapClientCache;
	
	/**
	 * @var \Aurora\System\Managers\Eav\Manager
	 */
	private $oEavManager = null;
	

	/**
	 * Initializes manager property.
	 * 
	 * @param \Aurora\System\Managers\GlobalManager &$oManager Manager object.
	 * 
	 * @return void
	 */
	public function __construct(\Aurora\System\Managers\GlobalManager &$oManager, $sForcedStorage = '', \Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct('main', $oManager, $oModule);

		$this->aImapClientCache = array();
		
		if ($oModule instanceof \Aurora\System\Module\AbstractModule)
		{
			$this->oEavManager = \Aurora\System\Api::GetSystemManager('eav', 'db');
		}
	}

	/**
	 * Returns ImapClient object from cache.
	 * 
	 * @param CMailAccount $oAccount Account object.
	 * @param int $iForceConnectTimeOut = 0. The value overrides connection timeout value.
	 * @param int $iForceSocketTimeOut = 0. The value overrides socket timeout value.
	 *
	 * @return \MailSo\Imap\ImapClient|null
	 */
	public function &_getImapClient(CMailAccount $oAccount, $iForceConnectTimeOut = 0, $iForceSocketTimeOut = 0)
	{
		$oResult = null;
		if ($oAccount)
		{
			$sCacheKey = $oAccount->Email;
			if (!isset($this->aImapClientCache[$sCacheKey]))
			{
				$oSettings =& \Aurora\System\Api::GetSettings();
				$iConnectTimeOut = $oSettings->GetConf('SocketConnectTimeoutSeconds', 10);
				$iSocketTimeOut = $oSettings->GetConf('SocketGetTimeoutSeconds', 20);
				$bVerifySsl = !!$oSettings->GetConf('SocketVerifySsl', false);

				if (0 < $iForceConnectTimeOut)
				{
					$iConnectTimeOut = $iForceConnectTimeOut;
				}
				
				if (0 < $iForceSocketTimeOut)
				{
					$iSocketTimeOut = $iForceSocketTimeOut;
				}

				$this->aImapClientCache[$sCacheKey] = \MailSo\Imap\ImapClient::NewInstance();
				$this->aImapClientCache[$sCacheKey]->SetTimeOuts($iConnectTimeOut, $iSocketTimeOut); // TODO
				$this->aImapClientCache[$sCacheKey]->SetLogger(\Aurora\System\Api::MailSoLogger());
			}

			$oResult =& $this->aImapClientCache[$sCacheKey];
			if (!$oResult->IsConnected())
			{
				$oServer = $oAccount->getServer();
				if ($oServer instanceof \CMailServer)
				{
					$oResult->Connect($oServer->IncomingServer, $oServer->IncomingPort, $oServer->IncomingUseSsl
							? \MailSo\Net\Enumerations\ConnectionSecurityType::SSL
							: \MailSo\Net\Enumerations\ConnectionSecurityType::NONE, $bVerifySsl);
				}
			}

			if (!$oResult->IsLoggined())
			{
//				$sProxyAuthUser = !empty($oAccount->CustomFields['ProxyAuthUser'])
//					? $oAccount->CustomFields['ProxyAuthUser'] : '';

				$oResult->Login($oAccount->IncomingLogin, $oAccount->IncomingPassword, '');
			}
		}

		return $oResult;
	}

	/**
	 * Creates a new instance of ImapClient class.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param type $iForceConnectTimeOut = 0. The value overrides connection timeout value.
	 * @param type $iForceSocketTimeOut = 0. The value overrides socket timeout value.
	 *
	 * @return \MailSo\Imap\ImapClient|null 
	 */
	public function &getImapClient(CAccount $oAccount, $iForceConnectTimeOut = 0, $iForceSocketTimeOut = 0)
	{
		$oImap = false;
		try
		{
			$oImap =& $this->_getImapClient($oAccount, $iForceConnectTimeOut, $iForceSocketTimeOut);
		}
		catch (\Exception $oException)
		{
		}

		return $oImap;
	}

	/**
	 * Checks if user of the account can successfully connect to mail server.
	 * 
	 * @param CMailAccount $oAccount Account object.
	 * 
	 * @return void
	 *
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function validateAccountConnection($oAccount)
	{
		try
		{
			$oImapClient = null;
			$oImapClient =& $this->_getImapClient($oAccount);
		}
		catch (\MailSo\Net\Exceptions\SocketCanNotConnectToHostException $oException)
		{
			throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_AccountConnectToMailServerFailed, $oException);
		}
		catch (\MailSo\Imap\Exceptions\LoginBadCredentialsException $oException)
		{
			throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_AccountAuthentication, $oException);
		}
		catch (\Exception $oException)
		{
			throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_AccountLoginFailed, $oException);
		}
	}

	/**
	 * Updates information on system folders use.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param array $aSystemNames Array containing mapping of folder types and their actual IMAP names.
	 *
	 * @return bool
	 */
	public function setSystemFolderNames($oAccount, $aSystemNames)
	{
		foreach ($aSystemNames as $sKey => $iTypeValue)
		{
			$aEntities = $this->oEavManager->getEntities(
				'CSystemFolder', 
				array(),
				0,
				1,
				array(
					'IdAccount' => $oAccount->EntityId,
					'Type' => $iTypeValue
				)
			);
			$oSystemFolder = new \CSystemFolder($this->GetModule()->GetName());
			
			if (count($aEntities) > 0 && $aEntities[0] instanceof \CSystemFolder)
			{
				$oSystemFolder = $aEntities[0];
			}
			else 
			{
				$oSystemFolder->IdAccount = $oAccount->EntityId;
			}
			$oSystemFolder->FolderFullName = $sKey;
			$oSystemFolder->Type = $iTypeValue;
			$this->oEavManager->saveEntity($oSystemFolder);
		}
		
		return true;
	}

	/**
	 * Gets information about system folders of the account.
	 * 
	 * @param CAccount $oAccount Account object.
	 *
	 * @return array|bool
	 */
	public function getSystemFolderNames($oAccount)
	{
		$aFolders = array();
		$aEntities = $this->oEavManager->getEntities(
			'CSystemFolder', 
			array(),
			0,
			9,
			array(
				'IdAccount' => $oAccount->EntityId
			)
		);
		if (count($aEntities) > 0)
		{
			foreach ($aEntities as $oEntity)
			{
				$aFolders[$oEntity->FolderFullName] = $oEntity->Type;
			}
		}
		
		return $aFolders;
	}

	/**
	 * Deletes information about system folders of the account.
	 * @param int $iAccountId Account identifier.
	 * @return boolean
	 */
	public function deleteSystemFolderNames($iAccountId)
	{
		$bResult = true;
		
		$iOffset = 0;
		$iLimit = 0;
		$aFilters = array('IdAccount' => array($iAccountId, '='));
		$aSystemFolders = $this->oEavManager->getEntities('CSystemFolder',  array(), $iOffset, $iLimit, $aFilters);
		if (is_array($aSystemFolders))
		{
			foreach ($aSystemFolders as $oSystemFolder)
			{
				$bResult = $bResult && $this->oEavManager->deleteEntity($oSystemFolder->EntityId);
			}
		}
		
		return $bResult;
	}

	/**
	 * Initializes system folders.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param CApiMailFolderCollection $oFolderCollection Collection of folders.
	 * @param bool $bCreateUnExistenSystemFilders Create non-existen system folders.
	 *
	 * @return bool
	 */
	private function _initSystemFolders($oAccount, &$oFolderCollection, $bCreateUnExistenSystemFilders)
	{
		$bAddSystemFolder = false;
		try
		{
			$aFoldersMap = array(
				EFolderType::Inbox => array('INBOX', 'Inbox'),
				EFolderType::Drafts => array('Drafts', 'Draft'),
				EFolderType::Sent => array('Sent', 'Sent Items', 'Sent Mail'),
				EFolderType::Spam => array('Spam', 'Junk', 'Junk Mail', 'Junk E-mail', 'Bulk Mail'),
				EFolderType::Trash => array('Trash', 'Bin', 'Deleted', 'Deleted Items'),
			);
			
			unset($aFoldersMap[EFolderType::Inbox]);
			
			$aTypes = [
				EFolderType::Inbox, 
				EFolderType::Drafts, 
				EFolderType::Sent, 
				EFolderType::Spam, 
				EFolderType::Trash
			];

			$aUnExistenSystemNames = array();
			$aSystemNames = $this->getSystemFolderNames($oAccount);

			$oInbox = $oFolderCollection->getFolder('INBOX');
			$oInbox->setType(EFolderType::Inbox);

			if (is_array($aSystemNames) && 0 < count($aSystemNames))
			{
				unset($aSystemNames['INBOX']);
				$aUnExistenSystemNames = $aSystemNames;

				foreach ($aSystemNames as $sSystemFolderFullName => $iFolderType)
				{
					$iKey = array_search($iFolderType, $aTypes);
					if (false !== $iKey)
					{
						$oFolder = /* @var $oFolder CApiMailFolder */ $oFolderCollection->getFolder($sSystemFolderFullName, true);
						if ($oFolder)
						{
							unset($aTypes[$iKey]);
							unset($aFoldersMap[$iKey]);
							unset($aUnExistenSystemNames[$sSystemFolderFullName]);
							
							$oFolder->setType($iFolderType);
						}
					}
				}
			}
			else
			{
				// set system type from flags
				$oFolderCollection->foreachWithSubFolders(function (/* @var $oFolder CApiMailFolder */ $oFolder) use (&$aTypes, &$aFoldersMap) {
						$iXListType = $oFolder->getFolderXListType();
						$iKey = array_search($iXListType, $aTypes);

						if (false !== $iKey && EFolderType::Custom === $oFolder->getType() && isset($aFoldersMap[$iXListType]))
						{
							unset($aTypes[$iKey]);
							unset($aFoldersMap[$iXListType]);
							
							$oFolder->setType($iXListType);
						}
					}
				);

				if (is_array($aFoldersMap) && 0 < count($aFoldersMap))
				{
					$oFolderCollection->foreachOnlyRoot(
						function (/* @var $oFolder CApiMailFolder */ $oFolder) use (&$aFoldersMap) {
							if (EFolderType::Custom === $oFolder->getType())
							{
								foreach ($aFoldersMap as $iFolderType => $aFoldersNames)
								{
									$aList = array();
									if (is_array($aFoldersNames))
									{
										$aList = $aFoldersNames;
									}
									else if (is_string($aFoldersNames))
									{
										$aList = array($aFoldersNames);
									}

									if (is_array($aList) && 0 < count($aList))
									{
										if (in_array($oFolder->getRawName(), $aList) || in_array($oFolder->getName(), $aList))
										{
											unset($aFoldersMap[$iFolderType]);

											$oFolder->setType($iFolderType);
										}
									}
								}
							}
						}
					);
				}

				if (is_array($aFoldersMap) && 0 < count($aFoldersMap))
				{
					$sNamespace = $oFolderCollection->getNamespace();
					foreach ($aFoldersMap as $iFolderType => $mFolderName)
					{
						$sFolderFullName = is_array($mFolderName) &&
							isset($mFolderName[0]) && is_string($mFolderName[0]) && 0 < strlen($mFolderName[0]) ?
								$mFolderName[0] : (is_string($mFolderName) && 0 < strlen($mFolderName) ? $mFolderName : '');

						if (0 < strlen($sFolderFullName))
						{
							$aUnExistenSystemNames[$sNamespace.$sFolderFullName] = $iFolderType;
						}
					}
				}
			}

			if ($bCreateUnExistenSystemFilders && is_array($aUnExistenSystemNames) && 0 < count($aUnExistenSystemNames))
			{
				foreach ($aUnExistenSystemNames as $sFolderFullName => $iFolderType)
				{
					$this->createFolderByFullName($oAccount, $sFolderFullName);
					$bAddSystemFolder = true;
				}
			}
		}
		catch (Exception $oException)
		{
			$bAddSystemFolder = false;
		}

		return $bAddSystemFolder;
	}
	
	public function isSafetySender($iIdUser, $sEmail)
	{
		$bResult = false;
		$aEntities = $this->oEavManager->getEntities(
			'CSender', 
			array(),
			0,
			1,
			array(
				'IdUser' => $iIdUser,
				'Email' => $sEmail
			)
		);
		if (count($aEntities) > 0)
		{
			$bResult = true;
		}
		
		return $bResult;
	}

	public function setSafetySender($iIdUser, $sEmail)
	{
		$bResult = true;
		if (!$this->isSafetySender($iIdUser, $sEmail))
		{
			$oEntity = \CSender::createInstance($this->GetName());
			
			$oEntity->IdUser = $iIdUser;
			$oEntity->Email = $sEmail;
			$bResult = $this->oEavManager->saveEntity($oEntity);
		}

		return $bResult;
	}
	/**
	 * Obtains the list of IMAP folders.
	 * 
	 * @param CMailAccount $oAccount Account object.
	 * @param bool $bCreateUnExistenSystemFolders = true. Creating folders is required for WebMail work, usually it is done on first login to the account.
	 *
	 * @return CApiMailFolderCollection Collection of folders.
	 */
	public function getFolders($oAccount, $bCreateUnExistenSystemFolders = true)
	{
		$oFolderCollection = false;

		$sParent = '';
		$sListPattern = '*';

		$oImapClient =& $this->_getImapClient($oAccount);

		$oNamespace = $oImapClient->GetNamespace();

		$aFolders = $oImapClient->FolderList($sParent, $sListPattern);
		$aSubscribedFolders = $oImapClient->FolderSubscribeList($sParent, $sListPattern);

		$aImapSubscribedFoldersHelper = array();
		if (is_array($aSubscribedFolders))
		{
			foreach ($aSubscribedFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aImapSubscribedFoldersHelper[] = $oImapFolder->FullNameRaw();
			}
		}

		$aMailFoldersHelper = null;
		if (is_array($aFolders))
		{
			$aMailFoldersHelper = array();

			foreach ($aFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
			{
				$aMailFoldersHelper[] = CApiMailFolder::createInstance($oImapFolder,
					in_array($oImapFolder->FullNameRaw(), $aImapSubscribedFoldersHelper) || $oImapFolder->IsInbox()
				);
			}
		}

		if (is_array($aMailFoldersHelper))
		{
			$oFolderCollection = CApiMailFolderCollection::createInstance();

			if ($oNamespace)
			{
				$oFolderCollection->setNamespace($oNamespace->GetPersonalNamespace());
			}

			$oFolderCollection->initialize($aMailFoldersHelper);

			if ($this->_initSystemFolders($oAccount, $oFolderCollection, $bCreateUnExistenSystemFolders) && $bCreateUnExistenSystemFolders)
			{
				$oFolderCollection = $this->getFolders($oAccount, false);
			}
		}

		if ($oFolderCollection && $oNamespace)
		{
			$oFolderCollection->setNamespace($oNamespace->GetPersonalNamespace());
		}

		$aFoldersOrderList = $this->getFoldersOrder($oAccount);
		$aFoldersOrderList = is_array($aFoldersOrderList) && 0 < count($aFoldersOrderList) ? $aFoldersOrderList : null;

		$oFolderCollection->sort(function ($oFolderA, $oFolderB) use ($aFoldersOrderList) {

			if (!$aFoldersOrderList)
			{
				if (EFolderType::Custom !== $oFolderA->getType() || EFolderType::Custom !== $oFolderB->getType())
				{
					if ($oFolderA->getType() === $oFolderB->getType())
					{
						return 0;
					}

					return $oFolderA->getType() < $oFolderB->getType() ? -1 : 1;
				}
			}
			else
			{
				$iPosA = array_search($oFolderA->getRawFullName(), $aFoldersOrderList);
				$iPosB = array_search($oFolderB->getRawFullName(), $aFoldersOrderList);
				if (is_int($iPosA) && is_int($iPosB))
				{
					return $iPosA < $iPosB ? -1 : 1;
				}
				else if (is_int($iPosA))
				{
					return -1;
				}
				else if (is_int($iPosB))
				{
					return 1;
				}
			}

			return strnatcmp(strtolower($oFolderA->getFullName()), strtolower($oFolderB->getFullName()));
		});

		if (null === $aFoldersOrderList)
		{
			$aNewFoldersOrderList = array();
			$oFolderCollection->foreachWithSubFolders(function (/* @var $oFolder CApiMailFolder */ $oFolder) use (&$aNewFoldersOrderList) {
				if ($oFolder)
				{
					$aNewFoldersOrderList[] = $oFolder->getRawFullName();
				}
			});

			if (0 < count($aNewFoldersOrderList))
			{
				$this->updateFoldersOrder($oAccount, $aNewFoldersOrderList);
			}
		}

		return $oFolderCollection;
	}

	/**
	 * Obtains folders order.
	 * 
	 * @param CAccount $oAccount Account object.
	 *
	 * @return array
	 */
	public function getFoldersOrder($oAccount)
	{
		$aList = array();
		
		$aOrder = @json_decode($oAccount->FoldersOrder, 3);
		if (is_array($aOrder) && 0 < count($aOrder))
		{
			$aList = $aOrder;
		}

		
		return $aList;
	}

	/**
	 * Updates folders order.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param array $aOrder New folders order.
	 *
	 * @return bool
	 */
	public function updateFoldersOrder($oAccount, $aOrder)
	{
		$oAccount->FoldersOrder = @json_encode($aOrder);
		return $this->oEavManager->saveEntity($oAccount);
	}

	/**
	 * Creates a new folder using its full name in IMAP folders tree. 
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 * @param bool $bSubscribeOnCreation = true. If **true** the folder will be subscribed and thus made visible in the interface.
	 * 
	 * @return void
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function createFolderByFullName($oAccount, $sFolderFullNameRaw, $bSubscribeOnCreation = true)
	{
		if (0 === strlen($sFolderFullNameRaw))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderCreate($sFolderFullNameRaw);

		if ($bSubscribeOnCreation)
		{
			$oImapClient->FolderSubscribe($sFolderFullNameRaw);
		}
	}

	/**
	 * Obtains folders information - total messages count, unread messages count, uidNext.
	 * 
	 * @param type $oImapClient ImapClient object.
	 * @param type $sFolderFullNameRaw Raw full name of the folder.
	 *
	 * @return array [$iMessageCount, $iMessageUnseenCount, $sUidNext]
	 */
	private function _getFolderInformation($oImapClient, $sFolderFullNameRaw)
	{
		$aFolderStatus = $oImapClient->FolderStatus($sFolderFullNameRaw, array(
			\MailSo\Imap\Enumerations\FolderResponseStatus::MESSAGES,
			\MailSo\Imap\Enumerations\FolderResponseStatus::UNSEEN,
			\MailSo\Imap\Enumerations\FolderResponseStatus::UIDNEXT
		));

		$iStatusMessageCount = isset($aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::MESSAGES])
			? (int) $aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::MESSAGES] : 0;

		$iStatusMessageUnseenCount = isset($aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UNSEEN])
			? (int) $aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UNSEEN] : 0;

		$sStatusUidNext = isset($aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UIDNEXT])
			? (string) $aFolderStatus[\MailSo\Imap\Enumerations\FolderResponseStatus::UIDNEXT] : '0';

		if (0 === strlen($sStatusUidNext))
		{
			$sStatusUidNext = '0';
		}

		// gmail hack
		$oFolder = $oImapClient->FolderCurrentInformation();
		if ($oFolder && null !== $oFolder->Exists && $oFolder->FolderName === $sFolderFullNameRaw)
		{
			$iSubCount = (int) $oFolder->Exists;
			if (0 < $iSubCount && $iSubCount < $iStatusMessageCount)
			{
				$iStatusMessageCount = $iSubCount;
			}
		}

		return array($iStatusMessageCount, $iStatusMessageUnseenCount, $sStatusUidNext,
			\Aurora\System\Utils::GenerateFolderHash($sFolderFullNameRaw, $iStatusMessageCount, $iStatusMessageUnseenCount, $sStatusUidNext));
	}

	/**
	 * Checks if particular extension is supported.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sExtensionName Extension name.
	 *
	 * @return bool
	 */
	public function isExtensionSupported($oAccount, $sExtensionName)
	{
		if (0 === strlen($sExtensionName))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);
		return $oImapClient->IsSupported($sExtensionName);
	}

	/**
	 * Obtains information about particular folder.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 *
	 * @return array array containing the following elements:
			- total number of messages;
			- number of unread messages;
			- UIDNEXT value for the folder;
			- hash string which changes its value if any of the other 3 values were changed. 
	 */
	public function getFolderInformation($oAccount, $sFolderFullNameRaw)
	{
		if (0 === strlen($sFolderFullNameRaw))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		return $this->_getFolderInformation($oImapClient, $sFolderFullNameRaw);
	}

	/**
	 * Retrieves information about new message, primarily used for Inbox folder. 
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 * @param string $sUidnext UIDNEXT value used for this operation.
	 *
	 * @return array
	 */
	public function getNewMessagesInformation($oAccount, $sFolderFullNameRaw, $sUidnext, $sNewInboxUidnext)
	{
		if (!isset($sNewInboxUidnext) || $sNewInboxUidnext === '')
		{
			$sNewInboxUidnext = '*';
		}
		
		if (0 === strlen($sFolderFullNameRaw) || 0 === strlen($sFolderFullNameRaw))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$aResult = array();
		$aFetchResponse = $oImapClient->Fetch(array(
				\MailSo\Imap\Enumerations\FetchType::INDEX,
				\MailSo\Imap\Enumerations\FetchType::UID,
				\MailSo\Imap\Enumerations\FetchType::FLAGS,
				\MailSo\Imap\Enumerations\FetchType::BuildBodyCustomHeaderRequest(array(
					\MailSo\Mime\Enumerations\Header::FROM_,
					\MailSo\Mime\Enumerations\Header::SUBJECT,
					\MailSo\Mime\Enumerations\Header::CONTENT_TYPE
				))
			), $sUidnext.':'.$sNewInboxUidnext, true);

		if (\is_array($aFetchResponse) && 0 < \count($aFetchResponse))
		{
			foreach ($aFetchResponse as /* @var $oFetchResponse \MailSo\Imap\FetchResponse */ $oFetchResponse)
			{
				$aFlags = \array_map('strtolower', $oFetchResponse->GetFetchValue(
					\MailSo\Imap\Enumerations\FetchType::FLAGS));

				if (!\in_array(\strtolower(\MailSo\Imap\Enumerations\MessageFlag::SEEN), $aFlags))
				{
					$sUid = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID);
					$sHeaders = $oFetchResponse->GetHeaderFieldsValue();

					$oHeaders = \MailSo\Mime\HeaderCollection::NewInstance()->Parse($sHeaders);

					$sContentTypeCharset = $oHeaders->ParameterValue(
						\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
						\MailSo\Mime\Enumerations\Parameter::CHARSET
					);

					$sCharset = '';
					if (0 < \strlen($sContentTypeCharset))
					{
						$sCharset = $sContentTypeCharset;
					}

					if (0 < \strlen($sCharset))
					{
						$oHeaders->SetParentCharset($sCharset);
					}
					
					if ($sUid !== $sNewInboxUidnext)
					{
						$aResult[] = array(
							'Folder' => $sFolderFullNameRaw,
							'Uid' => $sUid,
							'Subject' => $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::SUBJECT, 0 === \strlen($sCharset)),
							'From' => $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::FROM_, 0 === \strlen($sCharset))
						);
					}
				}
			}
		}

		return $aResult;
	}
	
	/**
	 * Obtains information about particular folders.
	 * 
	 * @param CMailAccount $oAccount Account object.
	 * @param array $aFolderFullNamesRaw Array containing a list of folder names to obtain information for.
	 *
	 * @return array Array containing elements like those returned by **getFolderInformation** method. 
	 */
	public function getFolderListInformation($oAccount, $aFolderFullNamesRaw)
	{
		if (!is_array($aFolderFullNamesRaw) || 0 === count($aFolderFullNamesRaw))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$aResult = array();
		if (2 < count($aFolderFullNamesRaw) && $oImapClient->IsSupported('LIST-STATUS'))
		{
			$aFolders = $oImapClient->FolderStatusList();

			if (is_array($aFolders))
			{
				foreach ($aFolders as /* @var $oImapFolder \MailSo\Imap\Folder */ $oImapFolder)
				{
					$oFolder = CApiMailFolder::createInstance($oImapFolder, true);
					if ($oFolder)
					{
						$mStatus = $oFolder->getStatus();
						if (is_array($mStatus) && isset($mStatus['MESSAGES'], $mStatus['UNSEEN'], $mStatus['UIDNEXT']))
						{
							$aResult[$oFolder->getRawFullName()] = array(
								(int) $mStatus['MESSAGES'],
								(int) $mStatus['UNSEEN'],
								(string) $mStatus['UIDNEXT'],
								\Aurora\System\Utils::GenerateFolderHash(
									$oFolder->getRawFullName(), $mStatus['MESSAGES'], $mStatus['UNSEEN'], $mStatus['UIDNEXT'])
							);
						}
					}

					unset($oFolder);
				}
			}
		}
		else
		{
			foreach ($aFolderFullNamesRaw as $sFolderFullNameRaw)
			{
				$sFolderFullNameRaw = (string) $sFolderFullNameRaw;

				try
				{
					$aResult[$sFolderFullNameRaw] = $this->_getFolderInformation($oImapClient, $sFolderFullNameRaw);
				}
				catch (\Exception $oException) {}
			}
		}

		return $aResult;
	}

	/**
	 * Creates a new folder.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderNameInUtf8 Folder name in utf8.
	 * @param string $sDelimiter IMAP delimiter value.
	 * @param string $sFolderParentFullNameRaw = ''. Parent folder this new one is created under.
	 * @param bool $bSubscribeOnCreation = true. If **true**, the folder will be subscribed and thus made visible in the interface.
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 * @throws \Aurora\System\Exceptions\BaseException
	 * 
	 * @return void
	 */
	public function createFolder($oAccount, $sFolderNameInUtf8, $sDelimiter, $sFolderParentFullNameRaw = '', $bSubscribeOnCreation = true)
	{
		if (0 === strlen($sFolderNameInUtf8) || 0 === strlen($sDelimiter))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$sFolderNameInUtf8 = trim($sFolderNameInUtf8);

		if (0 < strlen($sFolderParentFullNameRaw))
		{
			$sFolderParentFullNameRaw .= $sDelimiter;
		}

		$sNameToCreate = \MailSo\Base\Utils::ConvertEncoding($sFolderNameInUtf8,
			\MailSo\Base\Enumerations\Charset::UTF_8,
			\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

		if (0 < strlen($sDelimiter) && false !== strpos($sNameToCreate, $sDelimiter))
		{
			throw new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\Errs::Mail_FolderNameContainDelimiter);
		}

		$this->createFolderByFullName(
			$oAccount, $sFolderParentFullNameRaw.$sNameToCreate, $bSubscribeOnCreation);
	}

	/**
	 * Deletes folder.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 * @param bool $bUnsubscribeOnDeletion = true. If **true** the folder will be unsubscribed along with its deletion.
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function deleteFolder($oAccount, $sFolderFullNameRaw, $bUnsubscribeOnDeletion = true)
	{
		if (0 === strlen($sFolderFullNameRaw))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		if ($bUnsubscribeOnDeletion)
		{
			$oImapClient->FolderUnSubscribe($sFolderFullNameRaw);
		}

		$oImapClient->FolderDelete($sFolderFullNameRaw);
	}

	/**
	 * Changes folder's name.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sPrevFolderFullNameRaw Raw full name of the folder.
	 * @param string $sNewTopFolderNameInUtf8 = ''. New name for the folder in utf8.
	 *
	 * @return string
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 * @throws \Aurora\System\Exceptions\BaseException
	 */
	public function renameFolder($oAccount, $sPrevFolderFullNameRaw, $sNewTopFolderNameInUtf8)
	{
		$sNewTopFolderNameInUtf8 = trim($sNewTopFolderNameInUtf8);
		if (0 === strlen($sPrevFolderFullNameRaw) || 0 === strlen($sNewTopFolderNameInUtf8))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$aFolders = $oImapClient->FolderList('', $sPrevFolderFullNameRaw);
		if (!is_array($aFolders) || !isset($aFolders[0]))
		{
			throw new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\Errs::Mail_CannotRenameNonExistenFolder);
		}

		$sDelimiter = $aFolders[0]->Delimiter();
		$iLast = strrpos($sPrevFolderFullNameRaw, $sDelimiter);
		$sFolderParentFullNameRaw = false === $iLast ? '' : substr($sPrevFolderFullNameRaw, 0, $iLast + 1);

		$aSubscribeFolders = $oImapClient->FolderSubscribeList($sPrevFolderFullNameRaw, '*');
		if (is_array($aSubscribeFolders) && 0 < count($aSubscribeFolders))
		{
			foreach ($aSubscribeFolders as /* @var $oFolder \MailSo\Imap\Folder */ $oFolder)
			{
				$oImapClient->FolderUnSubscribe($oFolder->FullNameRaw());
			}
		}

		$sNewFolderFullNameRaw = \MailSo\Base\Utils::ConvertEncoding($sNewTopFolderNameInUtf8,
			\MailSo\Base\Enumerations\Charset::UTF_8,
			\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

		if (0 < strlen($sDelimiter) && false !== strpos($sNewFolderFullNameRaw, $sDelimiter))
		{
			throw new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\Errs::Mail_FolderNameContainDelimiter);
		}

		$sNewFolderFullNameRaw = $sFolderParentFullNameRaw.$sNewFolderFullNameRaw;

		$oImapClient->FolderRename($sPrevFolderFullNameRaw, $sNewFolderFullNameRaw);

		if (is_array($aSubscribeFolders) && 0 < count($aSubscribeFolders))
		{
			foreach ($aSubscribeFolders as /* @var $oFolder \MailSo\Imap\Folder */ $oFolder)
			{
				$sFolderFullNameRawForResubscrine = $oFolder->FullNameRaw();
				if (0 === strpos($sFolderFullNameRawForResubscrine, $sPrevFolderFullNameRaw))
				{
					$sNewFolderFullNameRawForResubscrine = $sNewFolderFullNameRaw.
						substr($sFolderFullNameRawForResubscrine, strlen($sPrevFolderFullNameRaw));

					$oImapClient->FolderSubscribe($sNewFolderFullNameRawForResubscrine);
				}
			}
		}

		$aOrders = $this->getFoldersOrder($oAccount);
		if (is_array($aOrders))
		{
			foreach ($aOrders as &$sName)
			{
				if ($sPrevFolderFullNameRaw === $sName)
				{
					$sName = $sNewFolderFullNameRaw;
					$this->updateFoldersOrder($oAccount, $aOrders);
					break;
				}
			}
		}

		return $sNewFolderFullNameRaw;
	}

	/**
	 * Subscribes to IMAP folder or unsubscribes from it.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder. 
	 * @param bool $bSubscribeAction = true. If **true** the folder will be subscribed, otherwise unsubscribed.
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function subscribeFolder($oAccount, $sFolderFullNameRaw, $bSubscribeAction = true)
	{
		if (0 === strlen($sFolderFullNameRaw))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		if ($bSubscribeAction)
		{
			$oImapClient->FolderSubscribe($sFolderFullNameRaw);
		}
		else
		{
			$oImapClient->FolderUnSubscribe($sFolderFullNameRaw);
		}
	}

	/**
	 * Purges all the content of a particular folder.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 *
	 * @return void
	 * 
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function clearFolder($oAccount, $sFolderFullNameRaw)
	{
		if (0 === strlen($sFolderFullNameRaw))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderSelect($sFolderFullNameRaw);

		$oInfo = $oImapClient->FolderCurrentInformation();
		if ($oInfo && 0 < $oInfo->Exists)
		{
			$oImapClient->MessageStoreFlag('1:*', false,
				array(\MailSo\Imap\Enumerations\MessageFlag::DELETED),
				\MailSo\Imap\Enumerations\StoreAction::ADD_FLAGS_SILENT
			);
		}

		$oImapClient->MessageExpunge();
	}

	/**
	 * Deletes one or several messages from IMAP.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Folder the messages are to be deleted from.
	 * @param array $aUids List of message UIDs.
	 * 
	 * @return void
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function deleteMessage($oAccount, $sFolderFullNameRaw, $aUids)
	{
		if (0 === strlen($sFolderFullNameRaw) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderSelect($sFolderFullNameRaw);

		$sUidsRange = implode(',', $aUids);

		$oImapClient->MessageStoreFlag($sUidsRange, true,
			array(\MailSo\Imap\Enumerations\MessageFlag::DELETED),
			\MailSo\Imap\Enumerations\StoreAction::ADD_FLAGS_SILENT
		);

		$oImapClient->MessageExpunge($sUidsRange, true);
	}

	/**
	 * Moves message from one folder to another.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFromFolderFullNameRaw Raw full name of the source folder.
	 * @param string $sToFolderFullNameRaw Raw full name of the destination folder.
	 * @param array $aUids List of message UIDs.
	 *
	 * @return void
	 * 
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function moveMessage($oAccount, $sFromFolderFullNameRaw, $sToFolderFullNameRaw, $aUids)
	{
		if (0 === strlen($sFromFolderFullNameRaw) || 0 === strlen($sToFolderFullNameRaw) ||
			!is_array($aUids) || 0 === count($aUids))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderSelect($sFromFolderFullNameRaw);

		if ($oImapClient->IsSupported('MOVE'))
		{
			$oImapClient->MessageMove($sToFolderFullNameRaw, implode(',', $aUids), true);
		}
		else
		{
			$oImapClient->MessageCopy($sToFolderFullNameRaw, implode(',', $aUids), true);
			$this->deleteMessage($oAccount, $sFromFolderFullNameRaw, $aUids);
		}
	}
	
	/**
	 * Copies one or several message from one folder to another.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFromFolderFullNameRaw Raw full name of source folder.
	 * @param string $sToFolderFullNameRaw Raw full name of destination folder.
	 * @param array $aUids List of message UIDs.
	 * 
	 * @return void
	 * 
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function copyMessage($oAccount, $sFromFolderFullNameRaw, $sToFolderFullNameRaw, $aUids)
	{
		if (0 === strlen($sFromFolderFullNameRaw) || 0 === strlen($sToFolderFullNameRaw) ||
			!is_array($aUids) || 0 === count($aUids))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderSelect($sFromFolderFullNameRaw);

		$oImapClient->MessageCopy($sToFolderFullNameRaw, implode(',', $aUids), true);
	}

	/**
	 * Sends message out.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param \MailSo\Mime\Message $oMessage Message to be sent out.
	 * @param CFetcher $oFetcher = null. Fetcher object which may override sending settings.
	 * @param string $sSentFolder = ''. Name of Sent folder.
	 * @param string $sDraftFolder = ''. Name of Sent folder.
	 * @param string $sDraftUid = ''. Last UID value of the message saved in Drafts folder.
	 *
	 * @return array|bool
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function sendMessage($oAccount, $oMessage, $oFetcher = null, $sSentFolder = '', $sDraftFolder = '', $sDraftUid = '')
	{
		if (!$oAccount || !$oMessage)
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}
		
		$oImapClient =& $this->_getImapClient($oAccount);

		$rMessageStream = \MailSo\Base\ResourceRegistry::CreateMemoryResource();
		
		$iMessageStreamSize = \MailSo\Base\Utils::MultipleStreamWriter(
			$oMessage->ToStream(true), array($rMessageStream), 8192, true, true, true);

		$mResult = false;
		if (false !== $iMessageStreamSize && is_resource($rMessageStream))
		{
			$oRcpt = $oMessage->GetRcpt();
			if ($oRcpt && 0 < $oRcpt->Count())
			{
				$sRcptEmail = '';
				try
				{
					$oSettings =& \Aurora\System\Api::GetSettings();
					$iConnectTimeOut = $oSettings->GetConf('SocketConnectTimeoutSeconds', 5);
					$iSocketTimeOut = $oSettings->GetConf('SocketGetTimeoutSeconds', 5);
					$bVerifySsl = !!$oSettings->GetConf('SocketVerifySsl', false);

					$oSmtpClient = \MailSo\Smtp\SmtpClient::NewInstance();
					$oSmtpClient->SetTimeOuts($iConnectTimeOut, $iSocketTimeOut);

					$oLogger = $oImapClient->Logger();
					if ($oLogger)
					{
						$oSmtpClient->SetLogger($oLogger);
					}

					$iSecure = \MailSo\Net\Enumerations\ConnectionSecurityType::AUTO_DETECT;
					if ($oFetcher)
					{
						$iSecure = $oFetcher->OutgoingMailSecurity;
					}
					else if ($oAccount->getServer()->OutgoingUseSsl)
					{
						$iSecure = \MailSo\Net\Enumerations\ConnectionSecurityType::SSL;
					}

					$sOutgoingLogin = '';
					if ($oFetcher)
					{
						$sOutgoingLogin = $oFetcher->IncomingLogin;
					}

					if (0 === strlen($sOutgoingLogin))
					{
						$sOutgoingLogin = $oAccount->IncomingLogin;
					}

					$sOutgoingPassword = '';
					if ($oFetcher)
					{
						$sOutgoingPassword = $oFetcher->IncomingPassword;
					}

					if (0 === strlen($sOutgoingPassword))
					{
						$sOutgoingPassword = $oAccount->IncomingPassword;
					}

					$sEhlo = \MailSo\Smtp\SmtpClient::EhloHelper();

					if ($oFetcher)
					{
						$oSmtpClient->Connect($oFetcher->OutgoingServer, $oFetcher->OutgoingPort, $sEhlo, $iSecure, $bVerifySsl);
					}
					else
					{
						$oSmtpClient->Connect($oAccount->getServer()->OutgoingServer, $oAccount->getServer()->OutgoingPort, $sEhlo, $iSecure, $bVerifySsl);
					}
					
					if (($oFetcher && $oFetcher->OutgoingUseAuth) || (!$oFetcher && $oAccount->getServer()->OutgoingUseAuth))
					{
						$oSmtpClient->Login($sOutgoingLogin, $sOutgoingPassword);
					}

					$oSmtpClient->MailFrom($oFetcher ? $oFetcher->Email : $oAccount->Email, (string) $iMessageStreamSize);

					$aRcpt =& $oRcpt->GetAsArray();

					foreach ($aRcpt as /* @var $oEmail \MailSo\Mime\Email */ $oEmail)
					{
						$sRcptEmail = $oEmail->GetEmail();
						$oSmtpClient->Rcpt($sRcptEmail);
					}

					$oSmtpClient->DataWithStream($rMessageStream);

					$oSmtpClient->LogoutAndDisconnect();
				}
				catch (\MailSo\Net\Exceptions\ConnectionException $oException)
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_AccountConnectToMailServerFailed, $oException);
				}
				catch (\MailSo\Smtp\Exceptions\LoginException $oException)
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_AccountLoginFailed, $oException);
				}
				catch (\MailSo\Smtp\Exceptions\NegativeResponseException $oException)
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_CannotSendMessage, $oException);
				}
				catch (\MailSo\Smtp\Exceptions\MailboxUnavailableException $oException)
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_MailboxUnavailable, 
							$oException, array(), array('Mailbox' => $sRcptEmail));
				}

				if (0 < strlen($sSentFolder))
				{
					try
					{
						if (!$oMessage->GetBcc())
						{
							if (is_resource($rMessageStream))
							{
								rewind($rMessageStream);
							}

							$oImapClient->MessageAppendStream(
								$sSentFolder, $rMessageStream, $iMessageStreamSize, array(
									\MailSo\Imap\Enumerations\MessageFlag::SEEN
								));
						}
						else
						{
							$rAppendMessageStream = \MailSo\Base\ResourceRegistry::CreateMemoryResource();

							$iAppendMessageStreamSize = \MailSo\Base\Utils::MultipleStreamWriter(
								$oMessage->ToStream(), array($rAppendMessageStream), 8192, true, true, true);

							$oImapClient->MessageAppendStream(
								$sSentFolder, $rAppendMessageStream, $iAppendMessageStreamSize, array(
									\MailSo\Imap\Enumerations\MessageFlag::SEEN
								));

							if (is_resource($rAppendMessageStream))
							{
								@fclose($rAppendMessageStream);
							}
						}
					}
					catch (\Exception $oException)
					{
						throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_CannotSaveMessageInSentItems, $oException);
					}

					if (is_resource($rMessageStream))
					{
						@fclose($rMessageStream);
					}
				}
				
				if (0 < strlen($sDraftFolder) && 0 < strlen($sDraftUid))
				{
					try
					{
						$this->deleteMessage($oAccount, $sDraftFolder, array($sDraftUid));
					}
					catch (\Exception $oException) {}
				}

				$mResult = true;
			}
			else
			{
				throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Mail_InvalidRecipients);
			}
		}

		return $mResult;
	}

	/**
	 * Saves message to a specific folder. The method is primarily used for saving drafts.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param \MailSo\Mime\Message $oMessage Object representing message to be saved.
	 * @param string $sDraftFolder Folder the message is saved to.
	 * @param string $sDraftUid = ''. UID of the message to be replaced; saving new draft removes the previous version.
	 *
	 * @return array|bool Array containing name of the folder and UID of the message stored, or bool in case of failure.
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function saveMessage($oAccount, $oMessage, $sDraftFolder, $sDraftUid = '')
	{
		if (!$oAccount || !$oMessage || 0 === strlen($sDraftFolder))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$rMessageStream = \MailSo\Base\ResourceRegistry::CreateMemoryResource();

		$iMessageStreamSize = \MailSo\Base\Utils::MultipleStreamWriter(
			$oMessage->ToStream(), array($rMessageStream), 8192, true, true);

		$mResult = false;
		if (false !== $iMessageStreamSize && is_resource($rMessageStream))
		{
			rewind($rMessageStream);

			$iNewUid = 0;

			$oImapClient->MessageAppendStream(
				$sDraftFolder, $rMessageStream, $iMessageStreamSize, array(
					\MailSo\Imap\Enumerations\MessageFlag::SEEN
				), $iNewUid);

			if (null === $iNewUid || 0 === $iNewUid)
			{
				$sMessageId = $oMessage->MessageId();
				if (0 < strlen($sMessageId))
				{
					$iNewUid = $this->getMessageUid($oAccount, $sDraftFolder, $sMessageId);
				}
			}

			$mResult = true;

			if (0 < strlen($sDraftFolder) && 0 < strlen($sDraftUid))
			{
				$this->deleteMessage($oAccount, $sDraftFolder, array($sDraftUid));
			}

			if (null !== $iNewUid && 0 < $iNewUid)
			{
				$mResult = array(
					'NewFolder' => $sDraftFolder,
					'NewUid' => $iNewUid
				);
			}
		}

		return $mResult;
	}
	
	/**
	 * Appends message from file to a specific folder.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sMessageFileName Path to .eml file.
	 * @param string $sFolderToAppend Folder the message is appended to.
	 *
	 * @return void
	 */
	public function appendMessageFromFile($oAccount, $sMessageFileName, $sFolderToAppend)
	{
		$oImapClient =& $this->_getImapClient($oAccount);
		$oImapClient->MessageAppendFile($sMessageFileName, $sFolderToAppend);
	}	

	/**
	 * Appends message from stream to a specific folder.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param resource $rMessage Resource the message is appended from.
	 * @param string $sFolder Folder the message is appended to.
	 * @param int $iStreamSize Size of stream.
	 *
	 * @return void
	 */
	public function appendMessageFromStream($oAccount, $rMessage, $sFolder, $iStreamSize)
	{
		$oImapClient =& $this->_getImapClient($oAccount);
		$oImapClient->MessageAppendStream($sFolder, $rMessage, $iStreamSize);
	}	

	/**
	 * Sets, removes or toggles flags of one or several messages.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 * @param array $aUids List of message UIDs .
	 * @param string $sFlagString String holding a list of flags to be modified.
	 * @param int $iAction = EMailMessageStoreAction::Add. Flag triggering mode.
	 * @param bool $bSetToAll = false. If **true** flags will be applied to all messages in folder.
	 * @param bool $bSkipNonPermanentsFlags = false. If **true** flags wich is not permanent will be skipped.
	 *
	 * @return true
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagString,
		$iAction = EMailMessageStoreAction::Add, $bSetToAll = false, $bSkipNonPermanentsFlags = false)
	{
		if (0 === strlen($sFolderFullNameRaw) || (!$bSetToAll && (!is_array($aUids) || 0 === count($aUids))))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderSelect($sFolderFullNameRaw);

		$aUids = is_array($aUids) ? $aUids : array();
		$sUids = implode(',', $aUids);

		$oInfo = $oImapClient->FolderCurrentInformation();
		if ($bSetToAll)
		{
			$sUids = '1:*';
			if ($oInfo && 0 === $oInfo->Exists)
			{
				return true;
			}
		}

		$aFlagsOut = array();
		$aFlags = explode(' ', $sFlagString);
		if ($bSkipNonPermanentsFlags && $oInfo)
		{
			if (!\in_array('\\*', $oInfo->PermanentFlags))
			{
				foreach ($aFlags as $sFlag)
				{
					$sFlag = \trim($sFlag);
					if (\in_array($sFlag, $oInfo->PermanentFlags))
					{
						$aFlagsOut[] = $sFlag;
					}
				}
			}
			else
			{
				$aFlagsOut = $aFlags;
			}

			if (0 === \count($aFlagsOut))
			{
				return true;
			}
		}
		else
		{
			$aFlagsOut = $aFlags;
		}

		$sResultAction = \MailSo\Imap\Enumerations\StoreAction::ADD_FLAGS_SILENT;
		switch ($iAction)
		{
			case EMailMessageStoreAction::Add:
				$sResultAction = \MailSo\Imap\Enumerations\StoreAction::ADD_FLAGS_SILENT;
				break;
			case EMailMessageStoreAction::Remove:
				$sResultAction = \MailSo\Imap\Enumerations\StoreAction::REMOVE_FLAGS_SILENT;
				break;
			case EMailMessageStoreAction::Set:
				$sResultAction = \MailSo\Imap\Enumerations\StoreAction::SET_FLAGS_SILENT;
				break;
		}

		$oImapClient->MessageStoreFlag($sUids, $bSetToAll ? false : true, $aFlags, $sResultAction);
		return true;
	}

	/**
	 * Searches for a message with a specific Message-ID value and returns it's uid.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderName Name of the folder to look for message in.
	 * @param string $sMessageId Message-ID value of the message.
	 *
	 * @return int|null Integer message UID if the message was found, null otherwise.
	 */
	public function getMessageUid($oAccount, $sFolderName, $sMessageId)
	{
		if (0 === strlen($sFolderName) || 0 === strlen($sMessageId))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderName);

		$aUids = $oImapClient->MessageSimpleSearch('HEADER Message-ID '.$sMessageId, true);

		return is_array($aUids) && 1 === count($aUids) && is_numeric($aUids[0]) ? (int) $aUids[0] : null;
	}

	/**
	 * Retrieves quota information for the account.
	 * 
	 * @param CAccount $oAccount Account object.
	 *
	 * @return array|bool Array of quota values or bool if the information is unavailable.
	 */
	public function getQuota($oAccount)
	{
		$oImapClient =& $this->_getImapClient($oAccount);
		
		return $oImapClient->Quota();
	}

	/**
	 * Downloads message from IMAP and returns it.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the Folder.
	 * @param int $iUid UID of the message to download.
	 * @param string $sRfc822SubMimeIndex = ''. Index at which a message is taken to parse. Index is used if the message is another message attachment.
	 * @param bool $bParseICalAndVcard = false. If **true** ical and vcard attachments will be parsed.
	 * @param bool $bParseAsc = false. If **true** attachments with extension .asc will be parsed.
	 * @param int $iBodyTextLimit = 0. If **> 0** will be received only part of the message body. If **= 0** the message body is not limited.
	 *
	 * @return CApiMailMessage
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function getMessage($oAccount, $sFolderFullNameRaw, $iUid, $sRfc822SubMimeIndex = '', $bParseICalAndVcard = false, $bParseAsc = false, $iBodyTextLimit = 0)
	{
		if (0 === strlen($sFolderFullNameRaw) || !is_numeric($iUid) || 0 >= (int) $iUid)
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$iUid = (int) $iUid;

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$oMessage = false;

		$sICalMimeIndex = '';
		$sVCardMimeIndex = '';
		$aTextMimeIndexes = array();
		$aAscPartsIds = array();

		$aFetchResponse = $oImapClient->Fetch(array(
			\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE), $iUid, true);

		$oBodyStructure = (0 < count($aFetchResponse)) ? $aFetchResponse[0]->GetFetchBodyStructure($sRfc822SubMimeIndex) : null;
		
		if ($oBodyStructure)
		{
			$aTextParts = $oBodyStructure->SearchHtmlOrPlainParts();
			if (is_array($aTextParts) && 0 < count($aTextParts))
			{
				foreach ($aTextParts as $oPart)
				{
					$aTextMimeIndexes[] = array($oPart->PartID(), $oPart->Size());
				}
			}

			if ($bParseAsc)
			{
				$aAscParts = $oBodyStructure->SearchByCallback(function (/* @var $oPart \MailSo\Imap\BodyStructure */ $oPart) {
					return '.asc' === \strtolower(\substr(\trim($oPart->FileName()), -4));
				});

				if (is_array($aAscParts) && 0 < count($aAscParts))
				{
					foreach ($aAscParts as $oPart)
					{
						$aAscPartsIds[] = $oPart->PartID();
					}
				}
			}
		}

		$aFetchItems = array(
			\MailSo\Imap\Enumerations\FetchType::INDEX,
			\MailSo\Imap\Enumerations\FetchType::UID,
			\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE,
			\MailSo\Imap\Enumerations\FetchType::INTERNALDATE,
			\MailSo\Imap\Enumerations\FetchType::FLAGS,
			0 < strlen($sRfc822SubMimeIndex)
				? \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sRfc822SubMimeIndex.'.HEADER]'
				: \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
		);

		if (0 < count($aTextMimeIndexes))
		{
			if (0 < strlen($sRfc822SubMimeIndex) && is_numeric($sRfc822SubMimeIndex))
			{
				$sLine = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$aTextMimeIndexes[0][0].'.1]';
				if (\is_numeric($iBodyTextLimit) && 0 < $iBodyTextLimit && $iBodyTextLimit < $aTextMimeIndexes[0][1])
				{
					$sLine .= '<0.'.((int) $iBodyTextLimit).'>';
				}

				$aFetchItems[] = $sLine;
			}
			else
			{
				foreach ($aTextMimeIndexes as $aTextMimeIndex)
				{
					$sLine = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$aTextMimeIndex[0].']';
					if (\is_numeric($iBodyTextLimit) && 0 < $iBodyTextLimit && $iBodyTextLimit < $aTextMimeIndex[1])
					{
						$sLine .= '<0.'.((int) $iBodyTextLimit).'>';
					}
					
					$aFetchItems[] = $sLine;
				}
			}
		}
		
		if (0 < strlen($sICalMimeIndex))
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sICalMimeIndex.']';
		}
		
		if (0 < strlen($sVCardMimeIndex))
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sVCardMimeIndex.']';
		}

		if (0 < count($aAscPartsIds))
		{
			foreach ($aAscPartsIds as $sPartID)
			{
				$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sPartID.']';
			}
		}

		if (!$oBodyStructure)
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE;
		}

		$aFetchResponse = $oImapClient->Fetch($aFetchItems, $iUid, true);
		if (0 < count($aFetchResponse))
		{
			$oMessage = CApiMailMessage::createInstance($sFolderFullNameRaw, $aFetchResponse[0], $oBodyStructure, $sRfc822SubMimeIndex, $aAscPartsIds);
		}

		if ($oMessage)
		{
			$sFromEmail = '';
			$oFromCollection = $oMessage->getFrom();
			if ($oFromCollection && 0 < $oFromCollection->Count())
			{
				$oFrom =& $oFromCollection->GetByIndex(0);
				if ($oFrom)
				{
					$sFromEmail = trim($oFrom->GetEmail());
				}
			}

			if (0 < strlen($sFromEmail))
			{
//				$oApiUsersManager = /* @var CApiUsersManager */\Aurora\System\Api::GetSystemManager('users');
				$oSettings =&\Aurora\System\Api::GetSettings();
				$bAlwaysShowImagesInMessage = !!$oSettings->GetConf('WebMail/AlwaysShowImagesInMessage');
				$oMessage->setSafety($bAlwaysShowImagesInMessage ? true : 
						$oApiUsersManager->getSafetySender($oAccount->IdUser, $sFromEmail, true));
			}
		}

		return $oMessage;
	}

	/**
	 * This is universal function for obtaining any MIME data via stream.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param mixed $mCallback This callback accepts the following parameters: $rMessageMimeIndexStream, $sContentType, $sFileName, $sMimeIndex.
	 * @param string $sFolderName Folder the message resides in.
	 * @param int $iUid UID of the message we're working with.
	 * @param string $sMimeIndex = ''. Mime index of message part.
	 *
	 * @return bool
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 * @throws \MailSo\Net\Exceptions\Exception
	 * @throws \MailSo\Imap\Exceptions\Exception
	 */
	public function directMessageToStream($oAccount, $mCallback, $sFolderName, $iUid, $sMimeIndex = '')
	{
		if (!is_callable($mCallback))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderName);

		$sFileName = '';
		$sContentType = '';
		$sMailEncodingName = '';

		$sMimeIndex = trim($sMimeIndex);
		$aFetchResponse = $oImapClient->Fetch(array(
			0 === strlen($sMimeIndex)
				? \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
				: \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sMimeIndex.'.MIME]'
		), $iUid, true);

		if (0 < count($aFetchResponse))
		{
			$sMime = $aFetchResponse[0]->GetFetchValue(
				0 === strlen($sMimeIndex)
					? \MailSo\Imap\Enumerations\FetchType::BODY_HEADER
					: \MailSo\Imap\Enumerations\FetchType::BODY.'['.$sMimeIndex.'.MIME]'
			);

			if (!empty($sMime))
			{
				$oHeaders = \MailSo\Mime\HeaderCollection::NewInstance()->Parse($sMime);

				if (!empty($sMimeIndex))
				{
					$sFileName = $oHeaders->ParameterValue(
						\MailSo\Mime\Enumerations\Header::CONTENT_DISPOSITION,
						\MailSo\Mime\Enumerations\Parameter::FILENAME);

					if (empty($sFileName))
					{
						$sFileName = $oHeaders->ParameterValue(
							\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
							\MailSo\Mime\Enumerations\Parameter::NAME);
					}

					$sMailEncodingName = $oHeaders->ValueByName(
						\MailSo\Mime\Enumerations\Header::CONTENT_TRANSFER_ENCODING);

					$sContentType = $oHeaders->ValueByName(
						\MailSo\Mime\Enumerations\Header::CONTENT_TYPE);
				}
				else
				{
					$sSubject = trim($oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::SUBJECT));
					$sFileName = (empty($sSubject) ? 'message-'.$iUid : trim($sSubject)).'.eml';
					$sFileName = '.eml' === $sFileName ? 'message.eml' : $sFileName;
					$sContentType = 'message/rfc822';
				}
			}
		}

		$aFetchResponse = $oImapClient->Fetch(array(
			array(\MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sMimeIndex.']',
				function ($sParent, $sLiteralAtomUpperCase, $rImapLiteralStream) use ($mCallback, $sMimeIndex, $sMailEncodingName, $sContentType, $sFileName)
				{
					if (!empty($sLiteralAtomUpperCase))
					{
						if (is_resource($rImapLiteralStream) && 'FETCH' === $sParent)
						{
							$rMessageMimeIndexStream = (empty($sMailEncodingName))
								? $rImapLiteralStream
								: \MailSo\Base\StreamWrappers\Binary::CreateStream($rImapLiteralStream,
									\MailSo\Base\StreamWrappers\Binary::GetInlineDecodeOrEncodeFunctionName(
										$sMailEncodingName, true));

							call_user_func($mCallback, $rMessageMimeIndexStream, $sContentType, $sFileName, $sMimeIndex);
						}
					}
				}
			)), $iUid, true);

		return ($aFetchResponse && 1 === count($aFetchResponse));
	}
	
	/**
	 * Escapes quotes in search string.
	 * 
	 * @param string $sSearch Search string for escaping.
	 * @param bool $bDetectGmail = true. If **true** function will use gmail mode for escaping.
	 *
	 * @return string
	 */
	private function _escapeSearchString($oImapClient, $sSearch, $bDetectGmail = true)
	{
		return ($bDetectGmail && 'ssl://imap.gmail.com' === strtolower($oImapClient->GetConnectedHost())) // gmail
			? '{'.strlen($sSearch).'+}'."\r\n".$sSearch
			: $oImapClient->EscapeString($sSearch);
	}

	/**
	 * Converts date from search string to Unix timestamp.
	 * 
	 * @param string $sDate Date in string format.
	 * @param int $iTimeZoneOffset Time zone in which the date string should be parsed.
	 *
	 * @return int
	 */
	private function _convertSearchDateToTimestamp($sDate, $iTimeZoneOffset)
	{
		$iResult = 0;
		
		if (0 < strlen($sDate))
		{
			$oDateTime = \DateTime::createFromFormat('Y.m.d', $sDate, \MailSo\Base\DateTimeHelper::GetUtcTimeZoneObject());
			$iResult = $oDateTime ? $oDateTime->getTimestamp() - $iTimeZoneOffset : 0;
		}

		return $iResult;
	}

	/**
	 * Parses search string to it's parts.
	 * 
	 * @param string $sSearch Search string.
	 *
	 * @return array
	 */
	private function _parseSearchString($sSearch)
	{
		$aResult = array(
			'OTHER' => ''
		);

		$aCache = array();

		$sSearch = \trim(\preg_replace('/[\s]+/', ' ', $sSearch));
		$sSearch = \trim(\preg_replace('/(e?mail|from|to|subject|has|date|text|body): /i', '\\1:', $sSearch));

		$mMatch = array();
		\preg_match_all('/".*?(?<!\\\)"/', $sSearch, $mMatch);
		if (\is_array($mMatch) && isset($mMatch[0]) && \is_array($mMatch[0]) && 0 < \count($mMatch[0]))
		{
			foreach ($mMatch[0] as $sItem)
			{
				do
				{
					$sKey = \md5(\mt_rand(10000, 90000).\microtime(true));
				}
				while (isset($aCache[$sKey]));

				$aCache[$sKey] = \stripcslashes($sItem);
				$sSearch = \str_replace($sItem, $sKey, $sSearch);
			}
		}

		\preg_match_all('/\'.*?(?<!\\\)\'/', $sSearch, $mMatch);
		if (\is_array($mMatch) && isset($mMatch[0]) && \is_array($mMatch[0]) && 0 < \count($mMatch[0]))
		{
			foreach ($mMatch[0] as $sItem)
			{
				do
				{
					$sKey = \md5(\mt_rand(10000, 90000).\microtime(true));
				}
				while (isset($aCache[$sKey]));

				$aCache[$sKey] = \stripcslashes($sItem);
				$sSearch = \str_replace($sItem, $sKey, $sSearch);
			}
		}

		$mMatch = array();
		\preg_match_all('/(e?mail|from|to|subject|has|date|text|body):([^\s]*)/i', $sSearch, $mMatch);
		if (\is_array($mMatch) && isset($mMatch[1]) && \is_array($mMatch[1]) && 0 < \count($mMatch[1]))
		{
			if (\is_array($mMatch[0]))
			{
				foreach ($mMatch[0] as $sToken)
				{
					$sSearch = \str_replace($sToken, '', $sSearch);
				}

				$sSearch = \trim(\preg_replace('/[\s]+/', ' ', $sSearch));
			}

			foreach ($mMatch[1] as $iIndex => $sName)
			{
				if (isset($mMatch[2][$iIndex]) && 0 < \strlen($mMatch[2][$iIndex]))
				{
					$sName = \strtoupper($sName);
					$sValue = $mMatch[2][$iIndex];
					switch ($sName)
					{
						case 'TEXT':
						case 'BODY':
						case 'EMAIL':
						case 'MAIL':
						case 'FROM':
						case 'TO':
						case 'SUBJECT':
						case 'HAS':
						case 'DATE':
							if ('MAIL' === $sName)
							{
								$sName = 'EMAIL';
							}
							$aResult[$sName] = $sValue;
							break;
					}
				}
			}
		}

		$aResult['OTHER'] = $sSearch;
		foreach ($aResult as $sName => $sValue)
		{
			if (isset($aCache[$sValue]))
			{
				$aResult[$sName] = trim($aCache[$sValue], '"\' ');
			}
		}

		return $aResult;
	}

	/**
	 * Prepares search string for searching on IMAP.
	 * 
	 * @param Object $oImapClient ImapClient object.
	 * @param string $sSearch Search string.
	 * @param int $iTimeZoneOffset = 0. Time zone in which the date string should be parsed.
	 * @param array $aFilters = array(). Shows what filters must be considered when searching.
	 *
	 * @return string
	 */
	private function _prepareImapSearchString($oImapClient, $sSearch, $iTimeZoneOffset = 0, $aFilters = array())
	{
		$aImapSearchResult = array();
		$sSearch = trim($sSearch);

		if (0 === strlen($sSearch) && 0 === count($aFilters))
		{
			return 'ALL';
		}
		
		$bFilterFlagged = false;
		$bFilterUnseen = false;

		$bIsGmail = $oImapClient->IsSupported('X-GM-EXT-1');
		$sGmailRawSearch = '';

		if (0 < strlen($sSearch))
		{
			$aLines = $this->_parseSearchString($sSearch);

			if (1 === count($aLines) && isset($aLines['OTHER']))
			{
				if (true) // TODO
				{
					$sValue = $this->_escapeSearchString($oImapClient, $aLines['OTHER']);

					$aImapSearchResult[] = 'OR OR OR';
					$aImapSearchResult[] = 'FROM';
					$aImapSearchResult[] = $sValue;
					$aImapSearchResult[] = 'TO';
					$aImapSearchResult[] = $sValue;
					$aImapSearchResult[] = 'CC';
					$aImapSearchResult[] = $sValue;
					$aImapSearchResult[] = 'SUBJECT';
					$aImapSearchResult[] = $sValue;
				}
				else
				{
					if ($bIsGmail)
					{
						$sGmailRawSearch .= ' '.$aLines['OTHER'];
					}
					else
					{
						$aImapSearchResult[] = 'TEXT';
						$aImapSearchResult[] = $this->_escapeSearchString($oImapClient, $aLines['OTHER']);
					}
				}
			}
			else
			{
				if (isset($aLines['EMAIL']))
				{
					$aEmails = explode(',', $aLines['EMAIL']);

					foreach ($aEmails as $iKey => $sEmail) //or - at least one match in message
					{
						if (strlen(trim($sEmail)) > 0)
						{
							$aImapSearchResult[] = ($iKey === 0 ? 'OR OR OR' : 'OR OR OR OR');
						}
					}

					foreach ($aEmails as $sEmail)
					{
						$sEmail = trim($sEmail);
						if (0 < strlen($sEmail))
						{
							$sValue = $this->_escapeSearchString($oImapClient, $sEmail);

							//$aImapSearchResult[] = 'OR OR OR'; //and - all matches in message
							$aImapSearchResult[] = 'FROM';
							$aImapSearchResult[] = $sValue;
							$aImapSearchResult[] = 'TO';
							$aImapSearchResult[] = $sValue;
							$aImapSearchResult[] = 'CC';
							$aImapSearchResult[] = $sValue;
							$aImapSearchResult[] = 'BCC';
							$aImapSearchResult[] = $sValue;
						}
					}
					
					unset($aLines['EMAIL']);
				}

				if (isset($aLines['TO']))
				{
					$sValue = $this->_escapeSearchString($oImapClient, $aLines['TO']);

					$aImapSearchResult[] = 'OR';
					$aImapSearchResult[] = 'TO';
					$aImapSearchResult[] = $sValue;
					$aImapSearchResult[] = 'CC';
					$aImapSearchResult[] = $sValue;
					
					unset($aLines['TO']);
				}

				$sMainText = '';
				foreach ($aLines as $sName => $sRawValue)
				{
					if ('' === \trim($sRawValue))
					{
						continue;
					}

					$sValue = $this->_escapeSearchString($oImapClient, $sRawValue);
					switch ($sName)
					{
						case 'FROM':
							$aImapSearchResult[] = 'FROM';
							$aImapSearchResult[] = $sValue;
							break;
						case 'SUBJECT':
							$aImapSearchResult[] = 'SUBJECT';
							$aImapSearchResult[] = $sValue;
							break;
						case 'OTHER':
						case 'BODY':
						case 'TEXT':
							if ($bIsGmail)
							{
								$sGmailRawSearch .= ' '.$sRawValue;
							}
							else
							{
								$sMainText .= ' '.$sRawValue;
							}
							break;
						case 'HAS':
							if (false !== strpos($sRawValue, 'attach'))
							{
								if ($bIsGmail)
								{
									$sGmailRawSearch .= ' has:attachment';
								}
								else
								{
									$aImapSearchResult[] = 'OR OR OR';
									$aImapSearchResult[] = 'HEADER Content-Type application/';
									$aImapSearchResult[] = 'HEADER Content-Type multipart/m';
									$aImapSearchResult[] = 'HEADER Content-Type multipart/signed';
									$aImapSearchResult[] = 'HEADER Content-Type multipart/report';
								}
							}
							if (false !== strpos($sRawValue, 'flag'))
							{
								$bFilterFlagged = true;
								$aImapSearchResult[] = 'FLAGGED';
							}
							if (false !== strpos($sRawValue, 'unseen'))
							{
								$bFilterUnseen = true;
								$aImapSearchResult[] = 'UNSEEN';
							}
							break;
						case 'DATE':
							$iDateStampFrom = $iDateStampTo = 0;

							$sDate = $sRawValue;
							$aDate = explode('/', $sDate);

							if (is_array($aDate) && 2 === count($aDate))
							{
								if (0 < strlen($aDate[0]))
								{
									$iDateStampFrom = $this->_convertSearchDateToTimestamp($aDate[0], $iTimeZoneOffset);
								}

								if (0 < strlen($aDate[1]))
								{
									$iDateStampTo = $this->_convertSearchDateToTimestamp($aDate[1], $iTimeZoneOffset);
									$iDateStampTo += 60 * 60 * 24;
								}
							}
							else
							{
								if (0 < strlen($sDate))
								{
									$iDateStampFrom = $this->_convertSearchDateToTimestamp($sDate, $iTimeZoneOffset);
									$iDateStampTo = $iDateStampFrom + 60 * 60 * 24;
								}
							}

							if (0 < $iDateStampFrom)
							{
								$aImapSearchResult[] = 'SINCE';
								$aImapSearchResult[] = gmdate('j-M-Y', $iDateStampFrom);
							}

							if (0 < $iDateStampTo)
							{
								$aImapSearchResult[] = 'BEFORE';
								$aImapSearchResult[] = gmdate('j-M-Y', $iDateStampTo);
							}
							break;
					}
				}

				if ('' !== trim($sMainText))
				{
					$sMainText = trim(trim(preg_replace('/[\s]+/', ' ', $sMainText)), '"\'');
					if ($bIsGmail)
					{
						$sGmailRawSearch .= ' '.$sRawValue;
					}
					else
					{
						$aImapSearchResult[] = 'BODY';
						$aImapSearchResult[] = $this->_escapeSearchString($oImapClient, $sMainText);
					}
				}
			}
		}

		if (0 < count($aFilters))
		{
			foreach ($aFilters as $sFilter)
			{
				if ('flagged' === $sFilter && !$bFilterFlagged)
				{
					$aImapSearchResult[] = 'FLAGGED';
				}
				else if ('unseen' === $sFilter && !$bFilterUnseen)
				{
					$aImapSearchResult[] = 'UNSEEN';
				}
			}
		}

		$sGmailRawSearch = \trim($sGmailRawSearch);
		if ($bIsGmail && 0 < \strlen($sGmailRawSearch))
		{
			$aImapSearchResult[] = 'X-GM-RAW';
			$aImapSearchResult[] = $this->_escapeSearchString($oImapClient, $sGmailRawSearch, false);
		}

		$sImapSearchResult = \trim(\implode(' ', $aImapSearchResult));
		if ('' === $sImapSearchResult)
		{
			$sImapSearchResult = 'ALL';
		}

		return $sImapSearchResult;
	}

	/**
	 * Reverses recursively thread uids and returns simple array.
	 * 
	 * @param array $aThreadUids Hierarchical structure containing thread uids.
	 * 
	 * @return array
	 */
	private function _reverseThreadUids($aThreadUids)
	{
		$aThreadUids = array_reverse($aThreadUids);
		foreach ($aThreadUids as &$mItem)
		{
			if (is_array($mItem))
			{
				$mItem = $this->_reverseThreadUids($mItem);
			}
		}
		return $aThreadUids;
	}

	/**
	 * Maps recursively thread list.
	 * 
	 * @param array $aThreads Thread list.
	 * 
	 * @return array
	 */
	private function _mapThreadList($aThreads)
	{
		$aNew = array();
		foreach ($aThreads as $mItem)
		{
			if (!is_array($mItem))
			{
				$aNew[] = $mItem;
			}
			else
			{
				$mMap = $this->_mapThreadList($mItem);
				if (is_array($mMap) && 0 < count($mMap))
				{
					$aNew = array_merge($aNew, $mMap);
				}
			}
		}

		sort($aNew, SORT_NUMERIC);
		return $aNew;
	}

	/**
	 * Compares items for sorting.
	 * 
	 * @param type $a First item to compare.
	 * @param type $b Second item to compare.
	 * @param type $aSortUidsFlipped Array contains items to compare.
	 * 
	 * @return int
	 */
	public function __sortHelper($a, $b, $aSortUidsFlipped)
	{
		if ($a === $b)
		{
			return 0;
		}

		$iAIndex = $iBIndex = -1;
		if (isset($aSortUidsFlipped[$a]))
		{
			$iAIndex = $aSortUidsFlipped[$a];
		}

		if (isset($aSortUidsFlipped[$b]))
		{
			$iBIndex = $aSortUidsFlipped[$b];
		}

		if ($iAIndex === $iBIndex)
		{
			return 0;
		}

		return ($iAIndex < $iBIndex) ? -1 : 1;
	}

	/**
	 * Sorts array by array.
	 * 
	 * @param array $aInput
	 * @param array $aSortUidsFlipped
	 * 
	 * @return void
	 */
	private function _sortArrayByArray(&$aInput, $aSortUidsFlipped)
	{
		$self = $this;

		\usort($aInput, function ($a, $b) use ($self, $aSortUidsFlipped) {
			return $self->__sortHelper($a, $b, $aSortUidsFlipped);
		});
	}

	/**
	 * Sorts array key by array.
	 * 
	 * @param array $aThreads
	 * @param array $aSortUidsFlipped
	 * 
	 * @return void
	 */
	private function _sortArrayKeyByArray(&$aThreads, $aSortUidsFlipped)
	{
		$self = $this;

		\uksort($aThreads, function ($a, $b) use ($self, $aSortUidsFlipped) {
			return $self->__sortHelper($a, $b, $aSortUidsFlipped);
		});
	}

	/**
	 * Breaks into chunks each thread from the list and returns the list by reference.
	 * 
	 * @param type $aThreads Thread list obtained by reference.
	 */
	private function _chunkThreadList(&$aThreads)
	{
		$iLimit = 200;
		foreach ($aThreads as $iKey => $mData)
		{
			if (is_array($mData) && 1 < count($mData))
			{
				if ($iLimit < count($mData))
				{
					$aChunks = array_chunk($mData, $iLimit);
					foreach ($aChunks as $iIndex => $aPart)
					{
						if (0 === $iIndex)
						{
							$aThreads[$iKey] = $aPart;
						}
						else
						{
							$iFirst = array_shift($aPart);
							if (0 < count($aPart))
							{
								$aThreads[$iFirst] = $aPart;
							}
							else
							{
								$aThreads[$iFirst] = $iFirst;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Resorts thread list.
	 * 
	 * @param array $aThreads Thread list.
	 * @param array $aSortUids Sort Uids.
	 *
	 * @return array
	 */
	private function _resortThreadList($aThreads, $aSortUids)
	{
		$aSortUidsFlipped = array_flip($aSortUids);

		foreach ($aThreads as $iKey => $mData)
		{
			if (is_array($mData) && 1 < count($mData))
			{
				$this->_sortArrayByArray($mData, $aSortUidsFlipped);
				$aThreads[$iKey] = $mData;
			}
		}

		$this->_chunkThreadList($aThreads);

		$this->_sortArrayKeyByArray($aThreads, $aSortUidsFlipped);

		return $aThreads;
	}

	/**
	 * Compiles thread list.
	 * 
	 * @param array $aThreads Thread list.
	 *
	 * @return array
	 */
	private function _compileThreadList($aThreads)
	{
		$aThreads = $this->_reverseThreadUids($aThreads);

		$aResult = array();
		foreach ($aThreads as $mItem)
		{
			if (is_array($mItem))
			{
				$aMap = $this->_mapThreadList($mItem);
				if (is_array($aMap))
				{
					if (1 < count($aMap))
					{
						$iMax = array_pop($aMap);
						rsort($aMap, SORT_NUMERIC);
						$aResult[(int) $iMax] = $aMap;
					}
					else if (0 < count($aMap))
					{
						$aResult[(int) $aMap[0]] = $aMap[0];
					}
				}
			}
			else
			{
				$aResult[(int) $mItem] = $mItem;
			}
		}

		krsort($aResult, SORT_NUMERIC);
		return $aResult;
	}

	/**
	 * Fetches some messages data and returns it in callback.
	 * 
	 * @param Object $oImapClient ImapClient object.
	 * @param string $sIndexRange
	 * @param function $fItemCallback callback wich is used for data returning.
	 * @param bool $bRangeAsUids = false.
	 *
	 * @return array
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	private function _doSpecialSubRequest($oImapClient, $sIndexRange, $fItemCallback, $bRangeAsUids = false)
	{
		$aResult = array();

		$aFetchResponse = $oImapClient->Fetch(array(
			\MailSo\Imap\Enumerations\FetchType::INDEX,
			\MailSo\Imap\Enumerations\FetchType::UID,
			\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE,
			\MailSo\Imap\Enumerations\FetchType::INTERNALDATE,
			\MailSo\Imap\Enumerations\FetchType::FLAGS,
			\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE
		), $sIndexRange, $bRangeAsUids);

		if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
		{
			$oFetchResponseItem = null;
			foreach ($aFetchResponse as /* @var $oFetchResponseItem \MailSo\Imap\FetchResponse */ &$oFetchResponseItem)
			{
				$sUid = $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID);
				$sSize = $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE);
				$sInternalDate = $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::INTERNALDATE);
				$aFlags = $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::FLAGS);
				$aFlagsLower = array_map('strtolower', $aFlags);

				$oBodyStructure = $oFetchResponseItem->GetFetchBodyStructure();

				if ($oBodyStructure)
				{
					if (call_user_func_array($fItemCallback, array(
						$oBodyStructure, $sSize, $sInternalDate, $aFlagsLower, $sUid
					)))
					{
						$aResult[] = $sUid;
					}
				}

				unset($oBodyStructure);
				unset($oFetchResponseItem);
			}
		}

		return $aResult;
	}

	/**
	 * Searches messages and messages data.
	 * 
	 * @param Object $oImapClient
	 * @param function $fItemCallback
	 * @param string $sFolderFullNameRaw
	 *
	 * @return array
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	private function _doSpecialIndexSearch($oImapClient, $fItemCallback, $sFolderFullNameRaw)
	{
		if (0 === strlen($sFolderFullNameRaw) ||
			!is_callable($fItemCallback))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$aResult = array();

		$aList = $this->_getFolderInformation($oImapClient, $sFolderFullNameRaw);
		$iCount = $aList[0];
		
		if (0 < $iCount)
		{
			$iInc = 0;
			$iTopLimit = 100;

			$aIndexes = range(1, $iCount);
			$aRequestIndexes = array();
			
			foreach ($aIndexes as $iIndex)
			{
				$iInc++;
				$aRequestIndexes[] = $iIndex;

				if ($iInc > $iTopLimit)
				{
					$aSubResult = $this->_doSpecialSubRequest($oImapClient,
						implode(',', $aRequestIndexes), $fItemCallback, false);

					$aResult = array_merge($aResult, $aSubResult);

					$aRequestIndexes = array();
					$iInc = 0;
				}
			}

			if (0 < count($aRequestIndexes))
			{
				$aSubResult = $this->_doSpecialSubRequest($oImapClient,
					implode(',', $aRequestIndexes), $fItemCallback, false);

				$aResult = array_merge($aResult, $aSubResult);
			}

			rsort($aResult, SORT_NUMERIC);
		}

		return is_array($aResult) ? $aResult : array();
	}

	/**
	 * Searches messages uids for message list.
	 * 
	 * @param Object $oImapClient
	 * @param function $fItemCallback
	 * @param string $sFolderFullNameRaw
	 * @param array $aUids
	 *
	 * @return array
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	private function _doSpecialUidsSearch($oImapClient, $fItemCallback, $sFolderFullNameRaw, $aUids)
	{
		if (0 === strlen($sFolderFullNameRaw) || !is_callable($fItemCallback) || 
			!is_array($aUids) || 0 === count($aUids))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$aResult = array();

		$iInc = 0;
		$iTopLimit = 100;

		$aRequestUids = array();
		foreach ($aUids as $iUid)
		{
			$iInc++;
			$aRequestUids[] = $iUid;

			if ($iInc > $iTopLimit)
			{
				$aSubResult = $this->_doSpecialSubRequest($oImapClient,
					implode(',', $aRequestUids), $fItemCallback, true);

				$aResult = array_merge($aResult, $aSubResult);

				$aRequestUids = array();
				$iInc = 0;
			}
		}

		if (0 < count($aRequestUids))
		{
			$aSubResult = $this->_doSpecialSubRequest($oImapClient,
				implode(',', $aRequestUids), $fItemCallback, true);

			$aResult = array_merge($aResult, $aSubResult);
		}

		rsort($aResult, SORT_NUMERIC);
		return $aResult;
	}

	/**
	 * Obtains message list with messages data.
	 * 
	 * @param CMailAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 * @param int $iOffset = 0. Offset value for obtaining a partial list.
	 * @param int $iLimit = 20. Limit value for obtaining a partial list.
	 * @param string $sSearch = ''. Search text.
	 * @param bool $bUseThreads = false. If **true**, message list will be returned in threaded mode.
	 * @param array $aFilters = array(). Contains filters for searching of messages.
	 * @param string $sInboxUidnext = ''. Uidnext value of Inbox folder.
	 *
	 * @return CApiMailMessageCollection
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function getMessageList($oAccount, $sFolderFullNameRaw, $iOffset = 0, $iLimit = 20,
		$sSearch = '', $bUseThreads = false, $aFilters = array(), $sInboxUidnext = '')
	{
		if (0 === strlen($sFolderFullNameRaw) || 0 > $iOffset || 0 >= $iLimit || 999 < $iLimit)
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oMessageCollection = false;

		$oSettings =&\Aurora\System\Api::GetSettings();
		$oImapClient =& $this->_getImapClient($oAccount, 20, 60 * 2);

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$aList = $this->_getFolderInformation($oImapClient, $sFolderFullNameRaw);

		$iMessageCount = $aList[0];
		$iRealMessageCount = $aList[0];
		$iMessageUnseenCount = $aList[1];
		$sUidNext = $aList[2];

		$oMessageCollection = CApiMailMessageCollection::createInstance();

		$oMessageCollection->FolderName = $sFolderFullNameRaw;
		$oMessageCollection->Offset = $iOffset;
		$oMessageCollection->Limit = $iLimit;
		$oMessageCollection->Search = $sSearch;
		$oMessageCollection->UidNext = $sUidNext;
		$oMessageCollection->Filters = implode(',', $aFilters);

		$aThreads = array();
		$bUseThreadsIfSupported = $this->GetModule()->getConfig('AllowThreads');
		if ($bUseThreadsIfSupported)
		{
			$bUseThreadsIfSupported = $bUseThreads;
		}

		$oMessageCollection->FolderHash = $aList[3];

		$bSearch = false;
		if (0 < $iRealMessageCount)
		{
			$bIndexAsUid = false;
			$aIndexOrUids = array();

			$bUseSortIfSupported = !!$oSettings->GetConf('WebMail/UseSortImapForDateMode');
			if ($bUseSortIfSupported)
			{
				$bUseSortIfSupported = $oImapClient->IsSupported('SORT');
			}
			
			if ($bUseThreadsIfSupported)
			{
				$bUseThreadsIfSupported = $oImapClient->IsSupported('THREAD=REFS') || $oImapClient->IsSupported('THREAD=REFERENCES') || $oImapClient->IsSupported('THREAD=ORDEREDSUBJECT');
			}

			if (0 < strlen($sSearch) || 0 < count($aFilters))
			{
				$sCutedSearch = $sSearch;

				$sCutedSearch = \preg_replace('/[\s]+/', ' ', $sCutedSearch);
				$sCutedSearch = \preg_replace('/attach[ ]?:[ ]?/i', 'attach:', $sCutedSearch);

				$bSearchAttachments = false;
				$fAttachmentSearchCallback = null;
				$aMatch = array();

				$oMailModule = \Aurora\System\Api::GetModule('Mail'); 
				$bUseBodyStructuresForHasAttachmentsSearch = $oMailModule->getConfig('UseBodyStructuresForHasAttachmentsSearch', false);
				if (($bUseBodyStructuresForHasAttachmentsSearch && \preg_match('/has[ ]?:[ ]?attachments/i', $sSearch)) ||
					\preg_match('/attach:([^\s]+)/i', $sSearch, $aMatch))
				{
					$bSearchAttachments = true;
					$sAttachmentName = isset($aMatch[1]) ? trim($aMatch[1]) : '';
					$sAttachmentRegs = !empty($sAttachmentName) && '*' !== $sAttachmentName ?
						'/[^>]*'.str_replace('\\*', '[^>]*', preg_quote(trim($sAttachmentName, '*'), '/')).'[^>]*/ui' : '';

					if ($bUseBodyStructuresForHasAttachmentsSearch)
					{	
						$sCutedSearch = trim(preg_replace('/has[ ]?:[ ]?attachments/i', '', $sCutedSearch));
					}
					
					$sCutedSearch = trim(preg_replace('/attach:([^\s]+)/', '', $sCutedSearch));

					$fAttachmentSearchCallback = function ($oBodyStructure, $sSize, $sInternalDate, $aFlagsLower, $sUid) use ($sFolderFullNameRaw, $sAttachmentRegs) {

						$bResult = false;
						if ($oBodyStructure)
						{
							$aAttachmentsParts = $oBodyStructure->SearchAttachmentsParts();
							if ($aAttachmentsParts && 0 < count($aAttachmentsParts))
							{
								$oAttachments = CApiMailAttachmentCollection::createInstance();
								foreach ($aAttachmentsParts as /* @var $oAttachmentItem \MailSo\Imap\BodyStructure */ $oAttachmentItem)
								{
									$oAttachments->Add(
										CApiMailAttachment::createInstance($sFolderFullNameRaw, $sUid, $oAttachmentItem)
									);
								}

								$bResult = $oAttachments->hasNotInlineAttachments();
								if ($bResult && !empty($sAttachmentRegs))
								{
									$aList = $oAttachments->FilterList(function ($oAttachment) use ($sAttachmentRegs) {
										if ($oAttachment && !$oAttachment->isInline() && !$oAttachment->getCid())
										{
											return !!preg_match($sAttachmentRegs, $oAttachment->getFileName());
										}

										return false;
									});

									return is_array($aList) ? 0 < count($aList) : false;
								}
							}
						}

						unset($oBodyStructure);

						return $bResult;
					};
				}

				if (0 < strlen($sCutedSearch) || 0 < count($aFilters))
				{
					$bSearch = true;
					$sSearchCriterias = $this->_prepareImapSearchString($oImapClient, $sCutedSearch,
						$oAccount->getDefaultTimeOffset() * 60, $aFilters);

					$bIndexAsUid = true;
					$aIndexOrUids = null;

					if ($bUseSortIfSupported)
					{
						$aIndexOrUids = $oImapClient->MessageSimpleSort(array('REVERSE ARRIVAL'), $sSearchCriterias, $bIndexAsUid);
					}
					else
					{
						if (!\MailSo\Base\Utils::IsAscii($sCutedSearch))
						{
							try
							{
								$aIndexOrUids = $oImapClient->MessageSimpleSearch($sSearchCriterias, $bIndexAsUid, 'UTF-8');
							}
							catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
							{
								// Charset is not supported. Skip and try request without charset.
								$aIndexOrUids = null;
							}
						}

						if (null === $aIndexOrUids)
						{
							$aIndexOrUids = $oImapClient->MessageSimpleSearch($sSearchCriterias, $bIndexAsUid);
						}
					}

					if ($bSearchAttachments && is_array($aIndexOrUids) && 0 < count($aIndexOrUids))
					{
						$aIndexOrUids = $this->_doSpecialUidsSearch(
							$oImapClient, $fAttachmentSearchCallback, $sFolderFullNameRaw, $aIndexOrUids, $iOffset, $iLimit);
					}
				}
				else if ($bSearchAttachments)
				{
					$bIndexAsUid = true;
					$aIndexOrUids = $this->_doSpecialIndexSearch(
						$oImapClient, $fAttachmentSearchCallback, $sFolderFullNameRaw, $iOffset, $iLimit);
				}
			}
			else
			{
				if ($bUseThreadsIfSupported && 1 < $iMessageCount)
				{
					$bIndexAsUid = true;
					$aThreadUids = array();
					try
					{
						$aThreadUids = $oImapClient->MessageSimpleThread();
					}
					catch (\MailSo\Imap\Exceptions\RuntimeException $oException)
					{
						$aThreadUids = array();
					}

					$aThreads = $this->_compileThreadList($aThreadUids);
					if ($bUseSortIfSupported)
					{
						$aThreads = $this->_resortThreadList($aThreads,
							$oImapClient->MessageSimpleSort(array('REVERSE ARRIVAL'), 'ALL', true));
					}
					else
					{
//						$this->chunkThreadArray($aThreads);
					}
					
					$aIndexOrUids = array_keys($aThreads);
					$iMessageCount = count($aIndexOrUids);
				}
				else if ($bUseSortIfSupported && 1 < $iMessageCount)
				{
					$bIndexAsUid = true;
					$aIndexOrUids = $oImapClient->MessageSimpleSort(array('REVERSE ARRIVAL'), 'ALL', $bIndexAsUid);
				}
				else
				{
					$bIndexAsUid = false;
					$aIndexOrUids = array(1);
					if (1 < $iMessageCount)
					{
						$aIndexOrUids = array_reverse(range(1, $iMessageCount));
					}
				}
			}

			if (is_array($aIndexOrUids))
			{
				$oMessageCollection->MessageCount = $iRealMessageCount;
				$oMessageCollection->MessageUnseenCount = $iMessageUnseenCount;
				$oMessageCollection->MessageResultCount = 0 < strlen($sSearch) || 0 < count($aFilters)
					? count($aIndexOrUids) : $iMessageCount;

				if (0 < count($aIndexOrUids))
				{
					$iOffset = (0 > $iOffset) ? 0 : $iOffset;
					$aRequestIndexOrUids = array_slice($aIndexOrUids, $iOffset, $iLimit);

					if ($bIndexAsUid)
					{
						$oMessageCollection->Uids = $aRequestIndexOrUids;
					}

					if (is_array($aRequestIndexOrUids) && 0 < count($aRequestIndexOrUids))
					{
						$aFetchResponse = $oImapClient->Fetch(array(
							\MailSo\Imap\Enumerations\FetchType::INDEX,
							\MailSo\Imap\Enumerations\FetchType::UID,
							\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE,
							\MailSo\Imap\Enumerations\FetchType::INTERNALDATE,
							\MailSo\Imap\Enumerations\FetchType::FLAGS,
							\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE,
							\MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
						), implode(',', $aRequestIndexOrUids), $bIndexAsUid);

						if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
						{
							$aFetchIndexArray = array();
							$oFetchResponseItem = null;
							foreach ($aFetchResponse as /* @var $oFetchResponseItem \MailSo\Imap\FetchResponse */ &$oFetchResponseItem)
							{
								$aFetchIndexArray[($bIndexAsUid)
									? $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID)
									: $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::INDEX)] =& $oFetchResponseItem;

								unset($oFetchResponseItem);
							}

							foreach ($aRequestIndexOrUids as $iFUid)
							{
								if (isset($aFetchIndexArray[$iFUid]))
								{
									$oMailMessage = CApiMailMessage::createInstance(
										$oMessageCollection->FolderName, $aFetchIndexArray[$iFUid]);

									if (!$bIndexAsUid)
									{
										$oMessageCollection->Uids[] = $oMailMessage->getUid();
									}

									$oMessageCollection->Add($oMailMessage);
									unset($oMailMessage);
								}
							}
						}
					}
				}
			}
		}

		if (!$bSearch && $bUseThreadsIfSupported && 0 < count($aThreads))
		{
			$oMessageCollection->ForeachList(function (/* @var $oMessage CApiMailMessage */ $oMessage) use ($aThreads) {
				$iUid = $oMessage->getUid();
				if (isset($aThreads[$iUid]) && is_array($aThreads[$iUid]))
				{
					$oMessage->setThreads($aThreads[$iUid]);
				}
			});
		}

		if (0 < strlen($sInboxUidnext) &&
			'INBOX' === $oMessageCollection->FolderName &&
			$sInboxUidnext !== $oMessageCollection->UidNext)
		{
			$oMessageCollection->New = $this->getNewMessagesInformation($oAccount, 'INBOX', $sInboxUidnext, $oMessageCollection->UidNext);
		}

		return $oMessageCollection;
	}

	/**
	 * Obtains a list of specific messages.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 * @param array $aUids List of message UIDs.
	 *
	 * @return CApiMailMessageCollection
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function getMessageListByUids($oAccount, $sFolderFullNameRaw, $aUids)
	{
		if (0 === strlen($sFolderFullNameRaw) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oMessageCollection = false;

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$aList = $this->_getFolderInformation($oImapClient, $sFolderFullNameRaw);

		$iMessageRealCount = $aList[0];
		$iMessageUnseenCount = $aList[1];
		$sUidNext = $aList[2];

		$oMessageCollection = CApiMailMessageCollection::createInstance();

		$oMessageCollection->FolderName = $sFolderFullNameRaw;
		$oMessageCollection->Offset = 0;
		$oMessageCollection->Limit = 0;
		$oMessageCollection->Search = '';
		$oMessageCollection->UidNext = $sUidNext;

		if (0 < $iMessageRealCount)
		{
			$bIndexAsUid = true;
			$aIndexOrUids = $aUids;
			
			if (is_array($aIndexOrUids))
			{
				$oMessageCollection->MessageCount = $iMessageRealCount;
				$oMessageCollection->MessageUnseenCount = $iMessageUnseenCount;
				$oMessageCollection->MessageSearchCount = $oMessageCollection->MessageCount;
				$oMessageCollection->MessageResultCount = $oMessageCollection->MessageCount;

				if (0 < count($aIndexOrUids))
				{
					$aRequestIndexOrUids = $aIndexOrUids;

					if ($bIndexAsUid)
					{
						$oMessageCollection->Uids = $aRequestIndexOrUids;
					}

					if (is_array($aRequestIndexOrUids) && 0 < count($aRequestIndexOrUids))
					{
						$aFetchResponse = $oImapClient->Fetch(array(
							\MailSo\Imap\Enumerations\FetchType::INDEX,
							\MailSo\Imap\Enumerations\FetchType::UID,
							\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE,
							\MailSo\Imap\Enumerations\FetchType::INTERNALDATE,
							\MailSo\Imap\Enumerations\FetchType::FLAGS,
							\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE,
							\MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
						), implode(',', $aRequestIndexOrUids), $bIndexAsUid);

						if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
						{
							$aFetchIndexArray = array();
							$oFetchResponseItem = null;
							foreach ($aFetchResponse as /* @var $oFetchResponseItem \MailSo\Imap\FetchResponse */ &$oFetchResponseItem)
							{
								$aFetchIndexArray[($bIndexAsUid)
									? $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID)
									: $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::INDEX)] =& $oFetchResponseItem;

								unset($oFetchResponseItem);
							}

							foreach ($aRequestIndexOrUids as $iFUid)
							{
								if (isset($aFetchIndexArray[$iFUid]))
								{
									$oMailMessage = CApiMailMessage::createInstance(
										$oMessageCollection->FolderName, $aFetchIndexArray[$iFUid]);

									if (!$bIndexAsUid)
									{
										$oMessageCollection->Uids[] = $oMailMessage->getUid();
									}

									$oMessageCollection->Add($oMailMessage);
									unset($oMailMessage);
								}
							}
						}
					}
				}
			}
		}

		$oMessageCollection->FolderHash = \Aurora\System\Utils::GenerateFolderHash($sFolderFullNameRaw,
			$oMessageCollection->MessageCount,
			$oMessageCollection->MessageUnseenCount,
			$oMessageCollection->UidNext);

		return $oMessageCollection;
	}

	/**
	 * Obtains list of flags for one or several messages.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sFolderFullNameRaw Raw full name of the folder.
	 * @param array $aUids List of message UIDs.
	 *
	 * @return CApiMailMessageCollection
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function getMessagesFlags($oAccount, $sFolderFullNameRaw, $aUids)
	{
		if (0 === strlen($sFolderFullNameRaw) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$aList = $this->_getFolderInformation($oImapClient, $sFolderFullNameRaw);

		$iMessageRealCount = $aList[0];

		$mResult = array();
		if (0 < $iMessageRealCount)
		{
			$aFetchResponse = $oImapClient->Fetch(array(
				\MailSo\Imap\Enumerations\FetchType::INDEX,
				\MailSo\Imap\Enumerations\FetchType::UID,
				\MailSo\Imap\Enumerations\FetchType::FLAGS
			), implode(',', $aUids), true);

			if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
			{
				$oFetchResponseItem = null;
				foreach ($aFetchResponse as /* @var $oFetchResponseItem \MailSo\Imap\FetchResponse */ &$oFetchResponseItem)
				{
					$sUid = $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID);
					$aFlags = $oFetchResponseItem->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::FLAGS);
					if (is_array($aFlags))
					{
						$mResult[$sUid] = array_map('strtolower', $aFlags);
					}
				}
			}
		}

		return $mResult;
	}

	/**
	 * Obtains messages for helpdesk synchronization with mailbox.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param int $iUidFrom Starts obtaining from this uid.
	 * @param int $iLastUid = 0 Returns last uid by reference.
	 * @param int $iLimit = 5 Messages count for obtaining.
	 *
	 * @return array
	 *
	 * @throws \Aurora\System\Exceptions\InvalidArgumentException
	 */
	public function getMessagesForHelpdeskSynch($oAccount, $iUidFrom, &$iLastUid = 0, $iLimit = 5)
	{
		if (0 > $iUidFrom)
		{
			throw new \Aurora\System\Exceptions\InvalidArgumentException();
		}

		$sFolderFullNameRaw = 'INBOX';
		$oImapClient =& $this->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$aList = $this->_getFolderInformation($oImapClient, $sFolderFullNameRaw);

		$iMessageRealCount = $aList[0];
		$aUidsForRequest = array();
		$aResult = array();

		if (0 === $iUidFrom)
		{
			$iLastUid = (int) $aList[2];
			if (1 < $iLastUid)
			{
				$iLastUid--;
			}
		}
		else if (0 < $iMessageRealCount)
		{
			$aFetchResponse = $oImapClient->Fetch(array(
				\MailSo\Imap\Enumerations\FetchType::INDEX,
				\MailSo\Imap\Enumerations\FetchType::UID,
				\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE,
				\MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
			), $iUidFrom.':*', true);

			if (is_array($aFetchResponse) && 0 < count($aFetchResponse))
			{
				$oFetchResponseItem = null;
				foreach ($aFetchResponse as /* @var $oFetchResponseItem \MailSo\Imap\FetchResponse */ &$oFetchResponseItem)
				{
					$iLimit--;
					if (0 > $iLimit)
					{
						break;
					}

					$oMailMessage = CApiMailMessage::createInstance($sFolderFullNameRaw, $oFetchResponseItem);
					if ($oMailMessage)
					{
						$iCurUid = $oMailMessage->getUid();
						if ($iCurUid && $iCurUid >= $iUidFrom)
						{
							if ($iCurUid > $iLastUid)
							{
								$iLastUid = $iCurUid;
							}

							$aUidsForRequest[] = $iCurUid;
						}
						
						unset($oMailMessage);
					}
				}
			}

			if (0 < count($aUidsForRequest))
			{
				foreach ($aUidsForRequest as $iUid)
				{
					$aResult[] = $this->getMessage($oAccount, $sFolderFullNameRaw, $iUid);
				}
			}
		}

		return $aResult;
	}
}
