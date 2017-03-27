<?php

namespace Aurora\Modules\Mail;

class Module extends \Aurora\System\Module\AbstractModule
{
	public $oApiMailManager = null;
	public $oApiAccountsManager = null;
	public $oApiServersManager = null;
	
	/* 
	 * @var $oApiFileCache \Aurora\System\Managers\Filecache\Manager 
	 */	
	public $oApiFileCache = null;
	
	public function init() 
	{
		$this->incClasses(
			array(
				'account',
				'fetcher',
				'enum',
				'folder',
				'folder-collection',
				'message',
				'message-collection',
				'attachment',
				'attachment-collection',
				'ics',
				'vcard',
				'server',
				'sieve-enum',
				'filter',
				'databyref',
				'system-folder',
				'sender'
			)
		);
		
		$this->oApiAccountsManager = $this->GetManager('accounts');
		$this->oApiServersManager = $this->GetManager('servers');
		$this->oApiMailManager = $this->GetManager('main');
		$this->oApiFileCache = \Aurora\System\Api::GetSystemManager('Filecache');
		
		$this->extendObject('CUser', array(
				'AllowAutosaveInDrafts'	=> array('bool', true),
				'UseThreads'			=> array('bool', true),
			)
		);

		$this->AddEntries(array(
				'autodiscover' => 'EntryAutodiscover',
				'message-newtab' => 'EntryMessageNewtab',
				'mail-attachment' => 'EntryDownloadAttachment'
			)
		);
		
		$this->subscribeEvent('Login', array($this, 'onLogin'));
		$this->subscribeEvent('Core::AfterDeleteUser', array($this, 'onAfterDeleteUser'));
	}
	
	/**
	 * Deletes all mail accounts which are owened by the specified user.
	 * 
	 * @ignore
	 * @param array $aArgs
	 * @param int $iUserId User identifier.
	 */
	public function onAfterDeleteUser($aArgs, &$iUserId)
	{
		$mResult = $this->oApiAccountsManager->getUserAccounts($iUserId);
		
		if (\is_array($mResult))
		{
			foreach($mResult as $oItem)
			{
				$this->DeleteAccount($oItem->EntityId);
			}
		}
	}
	
	/**
	 * Obtains list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$aSettings = array(
			'Accounts' => array(),
			'AllowAddNewAccounts' => $this->getConfig('AllowAddNewAccounts', false),
			'AllowAutosaveInDrafts' => $this->getConfig('AllowAutosaveInDrafts', false),
			'AllowChangeEmailSettings' => $this->getConfig('AllowChangeEmailSettings', false),
			'AllowFetchers' => $this->getConfig('AllowFetchers', false),
			'AllowIdentities' => $this->getConfig('AllowIdentities', false),
			'AllowInsertImage' => $this->getConfig('AllowInsertImage', false),
			'AllowSaveMessageAsPdf' => $this->getConfig('AllowSaveMessageAsPdf', false),
			'AllowThreads' => $this->getConfig('AllowThreads', false),
			'AllowZipAttachments' => $this->getConfig('AllowZipAttachments', false),
			'AutoSaveIntervalSeconds' => $this->getConfig('AutoSaveIntervalSeconds', 60),
			'AutosignOutgoingEmails' => $this->getConfig('AutosignOutgoingEmails', false),
			'ImageUploadSizeLimit' => $this->getConfig('ImageUploadSizeLimit', 0),
		);
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser && $oUser->Role === \EUserRole::NormalUser)
		{
			$aAcc = $this->oApiAccountsManager->getUserAccounts($oUser->EntityId);
			$aResponseAcc = [];
			foreach($aAcc as $oAccount)
			{
				$aResponseAcc[] = $oAccount->toResponseArray();
			}
			$aSettings['Accounts'] = $aResponseAcc;
			if (isset($oUser->{$this->GetName().'::AllowAutosaveInDrafts'}))
			{
				$aSettings['AllowAutosaveInDrafts'] = $oUser->{$this->GetName().'::AllowAutosaveInDrafts'};
			}
			if (isset($oUser->{$this->GetName().'::UseThreads'}))
			{
				$aSettings['UseThreads'] = $oUser->{$this->GetName().'::UseThreads'};
			}
		}
		
		return $aSettings;
	}
	
	public function UpdateSettings($UseThreads)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser)
		{
			if ($oUser->Role === \EUserRole::NormalUser)
			{
				$oCoreDecorator = \Aurora\System\Api::GetModuleDecorator('Core');
				$oUser->{$this->GetName().'::UseThreads'} = $UseThreads;
				return $oCoreDecorator->UpdateUserObject($oUser);
			}
			if ($oUser->Role === \EUserRole::SuperAdmin)
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function CreateAccount($UserId = 0, $FriendlyName = '', $Email = '', $IncomingLogin = '', 
			$IncomingPassword = '', $Server = null)
	{
//		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$iServerId = $Server['ServerId'];
		if ($Server !== null && $iServerId === 0)
		{
			$iServerId = $this->oApiServersManager->createServer(
				$Server['IncomingServer'], 
				$Server['IncomingServer'], 
				$Server['IncomingPort'], 
				$Server['IncomingUseSsl'],
				$Server['OutgoingServer'], 
				$Server['OutgoingPort'], 
				$Server['OutgoingUseSsl'], 
				$Server['OutgoingUseAuth']
			);
		}

		$oAccount = new \CMailAccount($this->GetName());

		$oAccount->IdUser = $UserId;
		$oAccount->FriendlyName = $FriendlyName;
		$oAccount->Email = $Email;
		$oAccount->IncomingLogin = $IncomingLogin;
		$oAccount->IncomingPassword = $IncomingPassword;
		$oAccount->ServerId = $iServerId;
		
		$oUser = null;
		$oCoreDecorator = \Aurora\System\Api::GetModuleDecorator('Core');
		if ($oCoreDecorator)
		{
			$oUser = $oCoreDecorator->GetUser($UserId);
			if ($oUser instanceof \CUser && $oUser->PublicId === $Email && !$this->oApiAccountsManager->useToAuthorizeAccountExists($Email))
			{
				$oAccount->UseToAuthorize = true;
			}
		}

		if ($this->oApiAccountsManager->createAccount($oAccount))
		{
			return $oAccount;
		}
		else
		{
			$this->oApiServersManager->deleteServer($iServerId);
		}
		
		return false;
	}
	
	public function UpdateAccount($AccountID, $UseToAuthorize = null, $Email = null, $FriendlyName = null, $IncomingLogin = null, 
			$IncomingPassword = null, $Server = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if ($AccountID > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			
			if ($oAccount)
			{
				if (!empty($Email))
				{
					$oAccount->Email = $Email;
				}
				if ($UseToAuthorize === false || $UseToAuthorize === true && !$this->oApiAccountsManager->useToAuthorizeAccountExists($oAccount->Email, $oAccount->EntityId))
				{
					$oAccount->UseToAuthorize = $UseToAuthorize;
				}
				if ($FriendlyName !== null)
				{
					$oAccount->FriendlyName = $FriendlyName;
				}
				if (!empty($IncomingLogin))
				{
					$oAccount->IncomingLogin = $IncomingLogin;
				}
				if (!empty($IncomingPassword))
				{
					$oAccount->IncomingPassword = $IncomingPassword;
				}
				if ($Server !== null)
				{
					if ($Server['ServerId'] === 0)
					{
						$iNewServerId = $this->oApiServersManager->createServer($Server['IncomingServer'], $Server['IncomingServer'], 
								$Server['IncomingPort'], $Server['IncomingUseSsl'], $Server['OutgoingServer'], 
								$Server['OutgoingPort'], $Server['OutgoingUseSsl'], $Server['OutgoingUseAuth'], 
								\EMailServerOwnerType::Account, 0);
						$oAccount->updateServer($iNewServerId);
					}
					elseif ($oAccount->ServerId === $Server['ServerId'])
					{
						$oAccServer = $oAccount->getServer();
						if ($oAccServer && $oAccServer->OwnerType === \EMailServerOwnerType::Account)
						{
							$this->oApiServersManager->updateServer($Server['ServerId'], $Server['IncomingServer'], 
									$Server['IncomingServer'], $Server['IncomingPort'], $Server['IncomingUseSsl'], 
									$Server['OutgoingServer'], $Server['OutgoingPort'], $Server['OutgoingUseSsl'], 
									$Server['OutgoingUseAuth'], 0);
						}
					}
					else
					{
						$oAccServer = $oAccount->getServer();
						if ($oAccServer && $oAccServer->OwnerType === \EMailServerOwnerType::Account)
						{
							$this->oApiServersManager->deleteServer($oAccServer->EntityId);
						}
						$oAccount->updateServer($Server['ServerId']);
					}
				}
				
				if ($this->oApiAccountsManager->updateAccount($oAccount))
				{
					return $oAccount;
				}
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UserNotAllowed);
		}

		return false;
	}
	
	/**
	 * @param int $AccountID
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function DeleteAccount($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$bResult = false;

		if ($AccountID > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			
			if ($oAccount)
			{
				$bServerRemoved = true;
				$oServer = $oAccount->getServer();
				if ($oServer->OwnerType === \EMailServerOwnerType::Account)
				{
					$bServerRemoved = $this->oApiServersManager->deleteServer($oServer->EntityId);
				}
				if ($bServerRemoved)
				{
					$bResult = $this->oApiAccountsManager->deleteAccount($oAccount);
				}
			}
			
			return $bResult;
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UserNotAllowed);
		}
	}
	
	public function onLogin($aArgs, &$mResult)
	{
		$bResult = false;
		$oServer = null;
		
		$oAccount = $this->oApiAccountsManager->getUseToAuthorizeAccount(
			$aArgs['Login'], 
			$aArgs['Password']
		);

		if (!$oAccount)
		{
			$sEmail = $aArgs['Login'];
			$sDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
			$oServer = $this->oApiServersManager->GetServerByDomain(strtolower($sDomain));
			if ($oServer)
			{
				$oAccount = \Aurora\System\EAV\Entity::createInstance('CMailAccount', $this->GetName());
				$oAccount->Email = $aArgs['Login'];
				$oAccount->IncomingLogin = $aArgs['Login'];
				$oAccount->IncomingPassword = $aArgs['Password'];
				$oAccount->ServerId = $oServer->EntityId;
			}
		}
		if ($oAccount instanceof \CMailAccount)
		{
			try
			{
				$this->oApiMailManager->validateAccountConnection($oAccount);
				
				$bResult =  true;

				$bAllowNewUsersRegister = $this->getConfig('AllowNewUsersRegister', false);
				
				if ($oServer && $bAllowNewUsersRegister)
				{
					$oAccount = $this->GetDecorator()->CreateAccount(
						0, 
						'', 
						$sEmail, 
						$aArgs['Login'],
						$aArgs['Password'], 
						array('ServerId' => $oServer->EntityId)
					);
					if ($oAccount)
					{
						$oAccount->UseToAuthorize = true;
						$this->oApiAccountsManager->UpdateAccount($oAccount);
					}
					else
					{
						$bResult = false;
					}
				}
				
				$mResult = array(
					'token' => 'auth',
					'sign-me' => $aArgs['SignMe'],
					'id' => $oAccount->IdUser,
					'account' => $oAccount->EntityId
				);
			}
			catch (\Exception $oEx) {}
		}			

		return $bResult;
	}
	
	/**** Ajax methods ****/
	public function GetServers($TenantId = 0)
	{
//		if ($TenantId === 0)
//		{
//			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
//		}
//		else
//		{
//			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
//		}
		
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiServersManager->getServerList($TenantId);
	}
	
	public function CreateServer($Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $Domains, $TenantId = 0)
	{
		$sOwnerType = ($TenantId === 0) ? \EMailServerOwnerType::SuperAdmin : \EMailServerOwnerType::Tenant;
		
		if ($TenantId === 0)
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		}
		
		return $this->oApiServersManager->createServer($Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $Domains, $sOwnerType, $TenantId);
	}
	
	public function UpdateServer($ServerId, $Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $Domains, $TenantId = 0)
	{
		if ($TenantId === 0)
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		}
		
		return $this->oApiServersManager->updateServer($ServerId, $Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $Domains, $TenantId);
	}
	
	public function DeleteServer($ServerId, $TenantId = 0)
	{
		if ($TenantId === 0)
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		}
		
		return $this->oApiServersManager->deleteServer($ServerId, $TenantId);
	}
	
	/**
	 * @param int $AccountID
	 * @return array | boolean
	 */
	public function GetExtensions($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount)
		{
			$mResult = array();
			$mResult['Extensions'] = array();

			// extensions
//			if ($oAccount->isExtensionEnabled(\CMailAccount::IgnoreSubscribeStatus) &&
//				!$oAccount->isExtensionEnabled(\CMailAccount::DisableManageSubscribe))
//			{
//				$oAccount->enableExtension(\CMailAccount::DisableManageSubscribe);
//			}
//
//			$aExtensions = $oAccount->getExtensionList();
//			foreach ($aExtensions as $sExtensionName)
//			{
//				if ($oAccount->isExtensionEnabled($sExtensionName))
//				{
//					$mResult['Extensions'][] = $sExtensionName;
//				}
//			}
		}

		return $mResult;
	}
	
	/**
	 * @param int $AccountID
	 * @return array
	 */
	public function GetFolders($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		$oFolderCollection = $this->oApiMailManager->getFolders($oAccount);
		return array(
			'Folders' => $oFolderCollection, 
			'Namespace' => $oFolderCollection->GetNamespace()
		);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param int $Offset
	 * @param int $Limit
	 * @param string $Search
	 * @param string $Filters
	 * @param int $UseThreads
	 * @param string $InboxUidnext
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function GetMessages($AccountID, $Folder, $Offset = 0, $Limit = 20, $Search = '', $Filters = '', $UseThreads = false, $InboxUidnext = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$sSearch = \trim((string) $Search);
		$sInboxUidnext = $InboxUidnext;
		
		$aFilters = array();
		$sFilters = \strtolower(\trim((string) $Filters));
		if (0 < \strlen($sFilters))
		{
			$aFilters = \array_filter(\explode(',', $sFilters), function ($sValue) {
				return '' !== trim($sValue);
			});
		}

		$iOffset = (int) $Offset;
		$iLimit = (int) $Limit;

		if (0 === \strlen(trim($Folder)) || 0 > $iOffset || 0 >= $iLimit || 200 < $iLimit)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessageList(
			$oAccount, $Folder, $iOffset, $iLimit, $sSearch, $UseThreads, $aFilters, $sInboxUidnext);
	}

	/**
	 * @param int $AccountID
	 * @param array $Folders
	 * @param string $InboxUidnext
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 * @throws \MailSo\Net\Exceptions\ConnectionException
	 */
	public function GetRelevantFoldersInformation($AccountID, $Folders, $InboxUidnext = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (!\is_array($Folders) || 0 === \count($Folders))
		{
			throw new \Aurora\System\Exceptions\ApiException(\ProjectSystem\Notifications::InvalidInputParameter);
		}

		$aResult = array();
		$oAccount = null;

		try
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			$oReturnInboxNewData = \DataByRef::createInstance(array());
			$aResult = $this->oApiMailManager->getFolderListInformation($oAccount, $Folders, $InboxUidnext, $oReturnInboxNewData);
		}
		catch (\MailSo\Net\Exceptions\ConnectionException $oException)
		{
			throw $oException;
		}
		catch (\MailSo\Imap\Exceptions\LoginException $oException)
		{
			throw $oException;
		}
		catch (\Exception $oException)
		{
			\Aurora\System\Api::Log((string) $oException);
		}

		return array(
			'Counts' => $aResult,
			'New' => isset($oReturnInboxNewData) ? $oReturnInboxNewData->GetData() : ''
		);
	}	
	
	/**
	 * @param int $AccountID
	 * @return array
	 */
	public function GetQuota($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		return $this->oApiMailManager->getQuota($oAccount);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function GetMessagesBodies($AccountID, $Folder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)) || !\is_array($Uids) || 0 === \count($Uids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$aList = array();
		foreach ($Uids as $iUid)
		{
			if (\is_numeric($iUid))
			{
				$oMessage = $this->GetMessage($AccountID, $Folder, (string) $iUid);
				if ($oMessage instanceof \CApiMailMessage)
				{
					$aList[] = $oMessage;
				}

				unset($oMessage);
			}
		}

		return $aList;
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uid
	 * @param string $Rfc822MimeIndex
	 * @return \CApiMailMessage
	 * @throws \Aurora\System\Exceptions\ApiException
	 * @throws CApiInvalidArgumentException
	 */
	public function GetMessage($AccountID, $Folder, $Uid, $Rfc822MimeIndex = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$iBodyTextLimit = 600000;
		
		$iUid = 0 < \strlen($Uid) && \is_numeric($Uid) ? (int) $Uid : 0;

		if (0 === \strlen(\trim($Folder)) || 0 >= $iUid)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (0 === \strlen($Folder) || !\is_numeric($iUid) || 0 >= (int) $iUid)
		{
			throw new \CApiInvalidArgumentException();
		}

		$oImapClient =& $this->oApiMailManager->_getImapClient($oAccount);

		$oImapClient->FolderExamine($Folder);

		$oMessage = false;

		$aTextMimeIndexes = array();
		$aAscPartsIds = array();

		$aFetchResponse = $oImapClient->Fetch(array(
			\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE), $iUid, true);

		$oBodyStructure = (0 < \count($aFetchResponse)) ? $aFetchResponse[0]->GetFetchBodyStructure($Rfc822MimeIndex) : null;
		
		$aCustomParts = array();
		if ($oBodyStructure)
		{
			$aTextParts = $oBodyStructure->SearchHtmlOrPlainParts();
			if (\is_array($aTextParts) && 0 < \count($aTextParts))
			{
				foreach ($aTextParts as $oPart)
				{
					$aTextMimeIndexes[] = array($oPart->PartID(), $oPart->Size());
				}
			}

			$aParts = $oBodyStructure->GetAllParts();
			
			$this->broadcastEvent(
				'GetBodyStructureParts', 
				$aParts, 
				$aCustomParts
			);
			
			$bParseAsc = true;
			if ($bParseAsc)
			{
				$aAscParts = $oBodyStructure->SearchByCallback(function (/* @var $oPart \MailSo\Imap\BodyStructure */ $oPart) {
					return '.asc' === \strtolower(\substr(\trim($oPart->FileName()), -4));
				});

				if (\is_array($aAscParts) && 0 < \count($aAscParts))
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
			0 < strlen($Rfc822MimeIndex)
				? \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$Rfc822MimeIndex.'.HEADER]'
				: \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
		);

		if (0 < \count($aTextMimeIndexes))
		{
			if (0 < \strlen($Rfc822MimeIndex) && \is_numeric($Rfc822MimeIndex))
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
		
		foreach ($aCustomParts as $oCustomPart)
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$oCustomPart->PartID().']';
		}

		if (0 < \count($aAscPartsIds))
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
		if (0 < \count($aFetchResponse))
		{
			$oMessage = \CApiMailMessage::createInstance($Folder, $aFetchResponse[0], $oBodyStructure, $Rfc822MimeIndex, $aAscPartsIds);
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

			if (0 < \strlen($sFromEmail))
			{
				$bAlwaysShowImagesInMessage = !!\Aurora\System\Api::GetSettings()->GetConf('WebMail/AlwaysShowImagesInMessage');

				$oMessage->setSafety($bAlwaysShowImagesInMessage ? true : 
						$this->oApiMailManager->isSafetySender($oAccount->IdUser, $sFromEmail));
			}
			
			$aData = array();
			foreach ($aCustomParts as $oCustomPart)
			{
				$sData = $aFetchResponse[0]->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::BODY.'['.$oCustomPart->PartID().']');
				if (!empty($sData))
				{
					$sData = \MailSo\Base\Utils::DecodeEncodingValue($sData, $oCustomPart->MailEncodingName());
					$sData = \MailSo\Base\Utils::ConvertEncoding($sData,
						\MailSo\Base\Utils::NormalizeCharset($oCustomPart->Charset(), true),
						\MailSo\Base\Enumerations\Charset::UTF_8);
				}
				$aData[] = array(
					'Data' => $sData,
					'Part' => $oCustomPart
				);
			}
			
			/* TODO: broadcast of events doesn't work */
//			$this->broadcastEvent(
//				'ExtendMessageData', 
//				array($oAccount, &$oMessage, $aData
//				)
//			);
		}

		if (!($oMessage instanceof \CApiMailMessage))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::CanNotGetMessage);
		}

		return $oMessage;
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uids
	 * @param int $SetAction
	 * @return boolean
	 */
	public function SetMessagesSeen($AccountID, $Folder, $Uids, $SetAction)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::SEEN);
	}	
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uids
	 * @param int $SetAction
	 * @return boolean
	 */
	public function SetMessageFlagged($AccountID, $Folder, $Uids, $SetAction)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::FLAGGED);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $sFolderFullNameRaw
	 * @param string $sUids
	 * @param int $iSetAction
	 * @param string $sFlagName
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	private function setMessageFlag($AccountID, $sFolderFullNameRaw, $sUids, $iSetAction, $sFlagName)
	{
		$bSetAction = 1 === $iSetAction;
		$aUids = \Aurora\System\Utils::ExplodeIntUids((string) $sUids);

		if (0 === \strlen(\trim($sFolderFullNameRaw)) || !\is_array($aUids) || 0 === \count($aUids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagName,
			$bSetAction ? \EMailMessageStoreAction::Add : \EMailMessageStoreAction::Remove);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SetAllMessagesSeen($AccountID, $Folder)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->setMessageFlag($oAccount, $Folder, array('1'),
			\MailSo\Imap\Enumerations\MessageFlag::SEEN, \EMailMessageStoreAction::Add, true);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $ToFolder
	 * @param string $Uids
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function MoveMessages($AccountID, $Folder, $ToFolder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$aUids = \Aurora\System\Utils::ExplodeIntUids((string) $Uids);

		if (0 === \strlen(\trim($Folder)) || 0 === \strlen(\trim($ToFolder)) || !\is_array($aUids) || 0 === \count($aUids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		try
		{
			$this->oApiMailManager->moveMessage($oAccount, $Folder, $ToFolder, $aUids);
		}
		catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::CanNotMoveMessageQuota, $oException,
				$oResponse instanceof \MailSo\Imap\Response ? $oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '');
		}
		catch (\Exception $oException)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::CanNotMoveMessage, $oException,
				$oException->getMessage());
		}

		return true;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uids
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function DeleteMessages($AccountID, $Folder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$aUids = \Aurora\System\Utils::ExplodeIntUids((string) $Uids);

		if (0 === \strlen(\trim($Folder)) || !\is_array($aUids) || 0 === \count($aUids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->deleteMessage($oAccount, $Folder, $aUids);

		return true;
	}
	
	/**** functions below have not been tested ***/
	/**
	 * @param int $AccountID
	 * @param string $FolderNameInUtf8
	 * @param string $FolderParentFullNameRaw
	 * @param string $Delimiter
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function CreateFolder($AccountID, $FolderNameInUtf8, $FolderParentFullNameRaw, $Delimiter)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen($FolderNameInUtf8) || 1 !== \strlen($Delimiter))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->createFolder($oAccount, $FolderNameInUtf8, $Delimiter, $FolderParentFullNameRaw);

		if (!$oAccount->isExtensionEnabled(\CMailAccount::DisableFoldersManualSort))
		{
			$aFoldersOrderList = $this->oApiMailManager->getFoldersOrder($oAccount);
			if (\is_array($aFoldersOrderList) && 0 < \count($aFoldersOrderList))
			{
				$aFoldersOrderListNew = $aFoldersOrderList;

				$sFolderNameInUtf7Imap = \MailSo\Base\Utils::ConvertEncoding($FolderNameInUtf8,
					\MailSo\Base\Enumerations\Charset::UTF_8,
					\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

				$sFolderFullNameRaw = (0 < \strlen($FolderParentFullNameRaw) ? $FolderParentFullNameRaw.$Delimiter : '').
					$sFolderNameInUtf7Imap;

				$sFolderFullNameUtf8 = \MailSo\Base\Utils::ConvertEncoding($sFolderFullNameRaw,
					\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
					\MailSo\Base\Enumerations\Charset::UTF_8);

				$aFoldersOrderListNew[] = $sFolderFullNameRaw;

				$aFoldersOrderListUtf8 = \array_map(function ($sValue) {
					return \MailSo\Base\Utils::ConvertEncoding($sValue,
						\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
						\MailSo\Base\Enumerations\Charset::UTF_8);
				}, $aFoldersOrderListNew);

				\usort($aFoldersOrderListUtf8, 'strnatcasecmp');
				
				$iKey = \array_search($sFolderFullNameUtf8, $aFoldersOrderListUtf8, true);
				if (\is_int($iKey) && 0 < $iKey && isset($aFoldersOrderListUtf8[$iKey - 1]))
				{
					$sUpperName = $aFoldersOrderListUtf8[$iKey - 1];

					$iUpperKey = \array_search(\MailSo\Base\Utils::ConvertEncoding($sUpperName,
						\MailSo\Base\Enumerations\Charset::UTF_8,
						\MailSo\Base\Enumerations\Charset::UTF_7_IMAP), $aFoldersOrderList, true);

					if (\is_int($iUpperKey) && isset($aFoldersOrderList[$iUpperKey]))
					{
						\Aurora\System\Api::Log('insert order index:'.$iUpperKey);
						\array_splice($aFoldersOrderList, $iUpperKey + 1, 0, $sFolderFullNameRaw);
						$this->oApiMailManager->updateFoldersOrder($oAccount, $aFoldersOrderList);
					}
				}
			}
		}

		return true;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $PrevFolderFullNameRaw
	 * @param string $NewFolderNameInUtf8
	 * @return array | boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function RenameFolder($AccountID, $PrevFolderFullNameRaw, $NewFolderNameInUtf8)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen($PrevFolderFullNameRaw) || 0 === \strlen($NewFolderNameInUtf8))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$mResult = $this->oApiMailManager->renameFolder($oAccount, $PrevFolderFullNameRaw, $NewFolderNameInUtf8);

		return (0 < \strlen($mResult) ? array(
			'FullName' => $mResult,
			'FullNameHash' => \md5($mResult)
		) : false);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function DeleteFolder($AccountID, $Folder)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->deleteFolder($oAccount, $Folder);

		return true;
	}	

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param bool $SetAction
	 * @return int
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SubscribeFolder($AccountID, $Folder, $SetAction)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (!$oAccount->isExtensionEnabled(\CMailAccount::DisableManageSubscribe))
		{
			$this->oApiMailManager->subscribeFolder($oAccount, $Folder, $SetAction);
			return true;
		}

		return false;
	}	
	
	/**
	 * @param int $AccountID
	 * @param array $FolderList
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function UpdateFoldersOrder($AccountID, $FolderList)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (!\is_array($FolderList))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if (!$oAccount->isExtensionEnabled(\CMailAccount::DisableFoldersManualSort))
		{
			return false;
		}

		return $this->oApiMailManager->updateFoldersOrder($oAccount, $FolderList);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function ClearFolder($AccountID, $Folder)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->clearFolder($oAccount, $Folder);

		return true;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function GetMessagesByUids($AccountID, $Folder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(trim($Folder)) || !\is_array($Uids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessageListByUids($oAccount, $Folder, $Uids);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function GetMessagesFlags($AccountID, $Folder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)) || !\is_array($Uids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessagesFlags($oAccount, $Folder, $Uids);
	}
	
	/**
	 * When using a memory stream and the read
	 * filter "convert.base64-encode" the last 
	 * character is missing from the output if 
	 * the base64 conversion needs padding bytes. 
	 * 
	 * @return bool
	 */
	private function FixBase64EncodeOmitsPaddingBytes($sRaw)
	{
		$rStream = \fopen('php://memory','r+');
		\fwrite($rStream, '0');
		\rewind($rStream);
		$rFilter = \stream_filter_append($rStream, 'convert.base64-encode');
		
		if (0 === \strlen(\stream_get_contents($rStream)))
		{
			$iFileSize = \strlen($sRaw);
			$sRaw = \str_pad($sRaw, $iFileSize + ($iFileSize % 3));
		}
		
		return $sRaw;
	}	
	
	/**
	 * 
	 * @param \CAccount $oAccount
	 * @param string $sTo
	 * @param string $sCc
	 * @param string $sBcc
	 * @param string $sSubject
	 * @param bool $bTextIsHtml
	 * @param string $sText
	 * @param array $aAttachments
	 * @param array $aDraftInfo
	 * @param string $sInReplyTo
	 * @param string $sReferences
	 * @param string $sImportance
	 * @param string $sSensitivity
	 * @param bool $bSendReadingConfirmation
	 * @param \CFetcher $oFetcher
	 * @param bool $bWithDraftInfo
	 * @param \CIdentity $oIdentity
	 * @return \MailSo\Mime\Message
	 */
	private function buildMessage($oAccount, $sTo = '', $sCc = '', $sBcc = '', 
			$sSubject = '', $bTextIsHtml = false, $sText = '', $aAttachments = null, 
			$aDraftInfo = null, $sInReplyTo = '', $sReferences = '', $sImportance = '',
			$sSensitivity = '', $bSendReadingConfirmation = false,
			$oFetcher = null, $bWithDraftInfo = true, $oIdentity = null)
	{
		$oMessage = \MailSo\Mime\Message::NewInstance();
		$oMessage->RegenerateMessageId();
		
		$sUUID = $this->getUUIDById($oAccount->IdUser);

		$sXMailer = $this->getConfig('XMailerValue', '');
		if (0 < \strlen($sXMailer))
		{
			$oMessage->SetXMailer($sXMailer);
		}

		if ($oIdentity)
		{
			$oFrom = \MailSo\Mime\Email::NewInstance($oIdentity->Email, $oIdentity->FriendlyName);
		}
		else
		{
			$oFrom = $oFetcher
				? \MailSo\Mime\Email::NewInstance($oFetcher->Email, $oFetcher->Name)
				: \MailSo\Mime\Email::NewInstance($oAccount->Email, $oAccount->FriendlyName);
		}

		$oMessage
			->SetFrom($oFrom)
			->SetSubject($sSubject)
		;

		$oToEmails = \MailSo\Mime\EmailCollection::NewInstance($sTo);
		if ($oToEmails && $oToEmails->Count())
		{
			$oMessage->SetTo($oToEmails);
		}

		$oCcEmails = \MailSo\Mime\EmailCollection::NewInstance($sCc);
		if ($oCcEmails && $oCcEmails->Count())
		{
			$oMessage->SetCc($oCcEmails);
		}

		$oBccEmails = \MailSo\Mime\EmailCollection::NewInstance($sBcc);
		if ($oBccEmails && $oBccEmails->Count())
		{
			$oMessage->SetBcc($oBccEmails);
		}

		if ($bWithDraftInfo && \is_array($aDraftInfo) && !empty($aDraftInfo[0]) && !empty($aDraftInfo[1]) && !empty($aDraftInfo[2]))
		{
			$oMessage->SetDraftInfo($aDraftInfo[0], $aDraftInfo[1], $aDraftInfo[2]);
		}

		if (0 < \strlen($sInReplyTo))
		{
			$oMessage->SetInReplyTo($sInReplyTo);
		}

		if (0 < \strlen($sReferences))
		{
			$oMessage->SetReferences($sReferences);
		}
		
		if (0 < \strlen($sImportance) && \in_array((int) $sImportance, array(
			\MailSo\Mime\Enumerations\MessagePriority::HIGH,
			\MailSo\Mime\Enumerations\MessagePriority::NORMAL,
			\MailSo\Mime\Enumerations\MessagePriority::LOW
		)))
		{
			$oMessage->SetPriority((int) $sImportance);
		}

		if (0 < \strlen($sSensitivity) && \in_array((int) $sSensitivity, array(
			\MailSo\Mime\Enumerations\Sensitivity::NOTHING,
			\MailSo\Mime\Enumerations\Sensitivity::CONFIDENTIAL,
			\MailSo\Mime\Enumerations\Sensitivity::PRIVATE_,
			\MailSo\Mime\Enumerations\Sensitivity::PERSONAL,
		)))
		{
			$oMessage->SetSensitivity((int) $sSensitivity);
		}

		if ($bSendReadingConfirmation)
		{
			$oMessage->SetReadConfirmation($oFetcher ? $oFetcher->Email : $oAccount->Email);
		}

		$aFoundCids = array();

		if ($bTextIsHtml)
		{
			$sTextConverted = \MailSo\Base\HtmlUtils::ConvertHtmlToPlain($sText);
			$oMessage->AddText($sTextConverted, false);
		}

		$mFoundDataURL = array();
		$aFoundedContentLocationUrls = array();

		$sTextConverted = $bTextIsHtml ? 
			\MailSo\Base\HtmlUtils::BuildHtml($sText, $aFoundCids, $mFoundDataURL, $aFoundedContentLocationUrls) : $sText;
		
		$oMessage->AddText($sTextConverted, $bTextIsHtml);

		if (\is_array($aAttachments))
		{
			foreach ($aAttachments as $sTempName => $aData)
			{
				if (\is_array($aData) && isset($aData[0], $aData[1], $aData[2], $aData[3]))
				{
					$sFileName = (string) $aData[0];
					$sCID = (string) $aData[1];
					$bIsInline = '1' === (string) $aData[2];
					$bIsLinked = '1' === (string) $aData[3];
					$sContentLocation = isset($aData[4]) ? (string) $aData[4] : '';

					$rResource = $this->oApiFileCache->getFile($sUUID, $sTempName);
					if (\is_resource($rResource))
					{
						$iFileSize = $this->oApiFileCache->fileSize($sUUID, $sTempName);

						$sCID = \trim(\trim($sCID), '<>');
						$bIsFounded = 0 < \strlen($sCID) ? \in_array($sCID, $aFoundCids) : false;

						if (!$bIsLinked || $bIsFounded)
						{
							$oMessage->Attachments()->Add(
								\MailSo\Mime\Attachment::NewInstance($rResource, $sFileName, $iFileSize, $bIsInline,
									$bIsLinked, $bIsLinked ? '<'.$sCID.'>' : '', array(), $sContentLocation)
							);
						}
					}
				}
			}
		}

		if ($mFoundDataURL && \is_array($mFoundDataURL) && 0 < \count($mFoundDataURL))
		{
			foreach ($mFoundDataURL as $sCidHash => $sDataUrlString)
			{
				$aMatch = array();
				$sCID = '<'.$sCidHash.'>';
				if (\preg_match('/^data:(image\/[a-zA-Z0-9]+\+?[a-zA-Z0-9]+);base64,(.+)$/i', $sDataUrlString, $aMatch) &&
					!empty($aMatch[1]) && !empty($aMatch[2]))
				{
					$sRaw = \MailSo\Base\Utils::Base64Decode($aMatch[2]);
					$iFileSize = \strlen($sRaw);
					if (0 < $iFileSize)
					{
						$sFileName = \preg_replace('/[^a-z0-9]+/i', '.', \MailSo\Base\Utils::NormalizeContentType($aMatch[1]));
						
						// fix bug #68532 php < 5.5.21 or php < 5.6.5
						$sRaw = $this->FixBase64EncodeOmitsPaddingBytes($sRaw);
						
						$rResource = \MailSo\Base\ResourceRegistry::CreateMemoryResourceFromString($sRaw);

						$sRaw = '';
						unset($sRaw);
						unset($aMatch);

						$oMessage->Attachments()->Add(
							\MailSo\Mime\Attachment::NewInstance($rResource, $sFileName, $iFileSize, true, true, $sCID)
						);
					}
				}
			}
		}

		return $oMessage;
	}	
	
	/**
	 * 
	 * @param int $AccountID
	 * @param string $FetcherID
	 * @param string $IdentityID
	 * @param array $DraftInfo
	 * @param string $DraftUid
	 * @param string $To
	 * @param string $Cc
	 * @param string $Bcc
	 * @param string $Subject
	 * @param string $Text
	 * @param bool $IsHtml
	 * @param int $Importance
	 * @param bool $SendReadingConfirmation
	 * @param array $Attachments
	 * @param string $InReplyTo
	 * @param string $References
	 * @param int $Sensitivity
	 * @param string $DraftFolder
	 * @return bool
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SaveMessage($AccountID, $FetcherID = "", $IdentityID = "", 
			$DraftInfo = [], $DraftUid = "", $To = "", $Cc = "", $Bcc = "", 
			$Subject = "", $Text = "", $IsHtml = false, $Importance = 1, 
			$SendReadingConfirmation = false, $Attachments = array(), $InReplyTo = "", 
			$References = "", $Sensitivity = 0, $DraftFolder = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (0 === \strlen($DraftFolder))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oFetcher = null;
		if (!empty($FetcherID) && \is_numeric($FetcherID) && 0 < (int) $FetcherID)
		{
			$iFetcherID = (int) $FetcherID;

			$oApiFetchers = $this->GetManager('fetchers');
			$aFetchers = $oApiFetchers->getFetchers($oAccount);
			if (\is_array($aFetchers) && 0 < \count($aFetchers))
			{
				foreach ($aFetchers as /* @var $oFetcherItem \CFetcher */ $oFetcherItem)
				{
					if ($oFetcherItem && $iFetcherID === $oFetcherItem->IdFetcher && $oAccount->IdUser === $oFetcherItem->IdUser)
					{
						$oFetcher = $oFetcherItem;
						break;
					}
				}
			}
		}

		$oIdentity = null;
//		if (!empty($IdentityID) && \is_numeric($IdentityID) && 0 < (int) $IdentityID)
//		{
//			$oApiUsers = \Aurora\System\Api::GetSystemManager('users');
//			$oIdentity = $oApiUsers->getIdentity((int) $IdentityID);
//		}

		$oMessage = $this->buildMessage($oAccount, $To, $Cc, $Bcc, 
			$Subject, $IsHtml, $Text, $Attachments, $DraftInfo, $InReplyTo, $References, $Importance,
			$Sensitivity, $SendReadingConfirmation, $oFetcher, true, $oIdentity);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->saveMessage($oAccount, $oMessage, $DraftFolder, $DraftUid);
			}
			catch (\Aurora\System\Exceptions\ManagerException $oException)
			{
				$iCode = \Aurora\System\Notifications::CanNotSaveMessage;
				throw new \Aurora\System\Exceptions\ApiException($iCode, $oException);
			}
		}

		return $mResult;
	}	
	
	/**
	 * 
	 * @param int $AccountID
	 * @param string $FetcherID
	 * @param string $IdentityID
	 * @param array $DraftInfo
	 * @param string $DraftUid
	 * @param string $To
	 * @param string $Cc
	 * @param string $Bcc
	 * @param string $Subject
	 * @param string $Text
	 * @param bool $IsHtml
	 * @param int $Importance
	 * @param bool $SendReadingConfirmation
	 * @param array $Attachments
	 * @param string $InReplyTo
	 * @param string $References
	 * @param int $Sensitivity
	 * @param string $SentFolder
	 * @param string $DraftFolder
	 * @return bool
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SendMessage($AccountID, $FetcherID = "", $IdentityID = "", 
			$DraftInfo = [], $DraftUid = "", $To = "", $Cc = "", $Bcc = "", 
			$Subject = "", $Text = "", $IsHtml = false, $Importance = 1, 
			$SendReadingConfirmation = false, $Attachments = array(), $InReplyTo = "", 
			$References = "", $Sensitivity = 0, $SentFolder = "", $DraftFolder = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$oFetcher = null;
		if (!empty($FetcherID) && \is_numeric($FetcherID) && 0 < (int) $FetcherID)
		{
			$iFetcherID = (int) $FetcherID;

			$aFetchers = $this->oApiFetchersManager->getFetchers($oAccount);
			if (\is_array($aFetchers) && 0 < count($aFetchers))
			{
				foreach ($aFetchers as /* @var $oFetcherItem \CFetcher */ $oFetcherItem)
				{
					if ($oFetcherItem && $iFetcherID === $oFetcherItem->IdFetcher && $oAccount->IdUser === $oFetcherItem->IdUser)
					{
						$oFetcher = $oFetcherItem;
						break;
					}
				}
			}
		}

		$oIdentity = null;
//		$oApiUsers = \Aurora\System\Api::GetSystemManager('users');
//		if ($oApiUsers && !empty($IdentityID) && \is_numeric($IdentityID) && 0 < (int) $IdentityID)
//		{
//			$oIdentity = $oApiUsers->getIdentity((int) $IdentityID);
//		}

		$oMessage = $this->buildMessage($oAccount, $To, $Cc, $Bcc, 
			$Subject, $IsHtml, $Text, $Attachments, $DraftInfo, $InReplyTo, $References, $Importance,
			$Sensitivity, $SendReadingConfirmation, $oFetcher, false, $oIdentity);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage, $oFetcher, $SentFolder, $DraftFolder, $DraftUid);
			}
			catch (\Aurora\System\Exceptions\ManagerException $oException)
			{
				$iCode = \Aurora\System\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \Aurora\System\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \Aurora\System\Notifications::CanNotSendMessage;
						break;
					case \Errs::Mail_CannotSaveMessageInSentItems:
						$iCode = \Aurora\System\Notifications::CannotSaveMessageInSentItems;
						break;
					case \Errs::Mail_MailboxUnavailable:
						$iCode = \Aurora\System\Notifications::MailboxUnavailable;
						break;
				}

				throw new \Aurora\System\Exceptions\ApiException($iCode, $oException, $oException->GetPreviousMessage(), $oException->GetObjectParams());
			}

			if ($mResult)
			{
				$aCollection = $oMessage->GetRcpt();

				$aEmails = array();
				$aCollection->ForeachList(function ($oEmail) use (&$aEmails) {
					$aEmails[strtolower($oEmail->GetEmail())] = trim($oEmail->GetDisplayName());
				});

				if (\is_array($aEmails))
				{
					$aArgs = ['Emails' => $aEmails];
					$this->broadcastEvent('AfterUseEmails', $aArgs);
				}
			}

			if (\is_array($DraftInfo) && 3 === \count($DraftInfo))
			{
				$sDraftInfoType = $DraftInfo[0];
				$sDraftInfoUid = $DraftInfo[1];
				$sDraftInfoFolder = $DraftInfo[2];

				try
				{
					switch (\strtolower($sDraftInfoType))
					{
						case 'reply':
						case 'reply-all':
							$this->oApiMailManager->setMessageFlag($oAccount,
								$sDraftInfoFolder, array($sDraftInfoUid),
								\MailSo\Imap\Enumerations\MessageFlag::ANSWERED,
								\EMailMessageStoreAction::Add);
							break;
						case 'forward':
							$this->oApiMailManager->setMessageFlag($oAccount,
								$sDraftInfoFolder, array($sDraftInfoUid),
								'$Forwarded',
								\EMailMessageStoreAction::Add);
							break;
					}
				}
				catch (\Exception $oException) {}
			}
		}

		\Aurora\System\Api::LogEvent('message-send: ' . $oAccount->Email, $this->GetName());
		return $mResult;
	}
	
	/**
	 * @param \CAccount $oAccount
	 * @param string $sConfirmationAddressee
	 * @param string $sSubject
	 * @param string $sText
	 * @return \MailSo\Mime\Message
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	private function buildConfirmationMessage($oAccount, $sConfirmationAddressee, $sSubject, $sText)
	{
		if (0 === strlen($sConfirmationAddressee) || 0 === strlen($sSubject) || 0 === strlen($sText))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$oMessage = \MailSo\Mime\Message::NewInstance();
		$oMessage->RegenerateMessageId();

		$sXMailer = $this->getConfig('XMailerValue', '');
		if (0 < strlen($sXMailer))
		{
			$oMessage->SetXMailer($sXMailer);
		}

		$oTo = \MailSo\Mime\EmailCollection::Parse($sConfirmationAddressee);
		if (!$oTo || 0 === $oTo->Count())
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$sFrom = 0 < strlen($oAccount->FriendlyName) ? '"'.$oAccount->FriendlyName.'"' : '';
		if (0 < strlen($sFrom))
		{
			$sFrom .= ' <'.$oAccount->Email.'>';
		}
		else
		{
			$sFrom .= $oAccount->Email;
		}
		
		$oMessage
			->SetFrom(\MailSo\Mime\Email::NewInstance($sFrom))
			->SetTo($oTo)
			->SetSubject($sSubject)
		;

		$oMessage->AddText($sText, false);

		return $oMessage;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $ConfirmFolder
	 * @param string $ConfirmUid
	 * @param string $ConfirmationAddressee
	 * @param string $Subject
	 * @param string $Text
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SendConfirmationMessage($AccountID, $ConfirmFolder, $ConfirmUid, $ConfirmationAddressee, $Subject, $Text)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$oMessage = $this->buildConfirmationMessage($oAccount, $ConfirmationAddressee, $Subject, $Text);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage);
			}
			catch (\Aurora\System\Exceptions\ManagerException $oException)
			{
				$iCode = \Aurora\System\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \Aurora\System\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \Aurora\System\Notifications::CanNotSendMessage;
						break;
				}

				throw new \Aurora\System\Exceptions\ApiException($iCode, $oException);
			}

			if (0 < \strlen($ConfirmFolder) && 0 < \strlen($ConfirmUid))
			{
				try
				{
					$mResult = $this->oApiMailManager->setMessageFlag($oAccount, $ConfirmFolder, array($ConfirmUid), '$ReadConfirm', 
						\EMailMessageStoreAction::Add, false, true);
				}
				catch (\Exception $oException) {}
			}
		}

		return $mResult;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Sent
	 * @param string $Drafts
	 * @param string $Trash
	 * @param string $Spam
	 * @return array
	 */
	public function SetupSystemFolders($AccountID, $Sent, $Drafts, $Trash, $Spam)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$aData = array();
		if (0 < \strlen(\trim($Sent)))
		{
			$aData[$Sent] = \EFolderType::Sent;
		}
		if (0 < \strlen(\trim($Drafts)))
		{
			$aData[$Drafts] = \EFolderType::Drafts;
		}
		if (0 < \strlen(\trim($Trash)))
		{
			$aData[$Trash] = \EFolderType::Trash;
		}
		if (0 < \strlen(\trim($Spam)))
		{
			$aData[$Spam] = \EFolderType::Spam;
		}

		return $this->oApiMailManager->setSystemFolderNames($oAccount, $aData);
	}	
	
	/**
	 * @param int $AccountID
	 * @param string $FileName
	 * @param string $Html
	 * @return boolean
	 */
	public function GeneratePdfFile($AccountID, $FileName, $Html)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount)
		{
			$sFileName = $FileName.'.pdf';
			$sMimeType = 'application/pdf';

			$sSavedName = 'pdf-'.$oAccount->EntityId.'-'.md5($sFileName.microtime(true)).'.pdf';
			
			include_once AURORA_APP_ROOT_PATH.'vendors/other/CssToInlineStyles.php';

			$oCssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($Html);
			$oCssToInlineStyles->setEncoding('utf-8');
			$oCssToInlineStyles->setUseInlineStylesBlock(true);

			$sExec = \Aurora\System\Api::DataPath().'/system/wkhtmltopdf/linux/wkhtmltopdf';
			if (!\file_exists($sExec))
			{
				$sExec = \Aurora\System\Api::DataPath().'/system/wkhtmltopdf/win/wkhtmltopdf.exe';
				if (!\file_exists($sExec))
				{
					$sExec = '';
				}
			}

			if (0 < \strlen($sExec))
			{
				$oSnappy = new \Knp\Snappy\Pdf($sExec);
				$oSnappy->setOption('quiet', true);
				$oSnappy->setOption('disable-javascript', true);

				$oSnappy->generateFromHtml($oCssToInlineStyles->convert(),
					$this->oApiFileCache->generateFullFilePath($oAccount->UUID, $sSavedName), array(), true);

				return array(
					'Name' => $sFileName,
					'TempName' => $sSavedName,
					'MimeType' => $sMimeType,
					'Size' =>  (int) $this->oApiFileCache->fileSize($oAccount->UUID, $sSavedName),
					'Hash' => \Aurora\System\Api::EncodeKeyValues(array(
						'TempFile' => true,
						'AccountID' => $oAccount->EntityId,
						'Name' => $sFileName,
						'TempName' => $sSavedName
					))
				);
			}
		}

		return false;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Email
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SetEmailSafety($AccountID, $Email)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === \strlen(\trim($Email)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->setSafetySender($oAccount->IdUser, $Email);

		return true;
	}	
	
//	public function GetFetchers()
//	{
//		$oAccount = $this->getParamValue('Account', null);
//		return $this->oApiFetchersManager->getFetchers($oAccount);
//	}
	
	/**
	 * 
	 * @param int $AccountID
	 * @return array | boolean
	 */
	public function GetIdentities($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return null;
		
//		$mResult = false;
//		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
//		if ($oAccount)
//		{
//			$oApiUsersManager = \Aurora\System\Api::GetSystemManager('users');
//			$mResult = $oApiUsersManager->getUserIdentities($oAccount->IdUser);
//		}
//		
//		return $mResult;
	}
	
	public function UpdateSignature($AccountID, $UseSignature = null, $Signature = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if ($AccountID > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			
			if ($oAccount)
			{
				if ($UseSignature !== null)
				{
					$oAccount->UseSignature = $UseSignature;
				}
				if ($Signature !== null)
				{
					$oAccount->Signature = $Signature;
				}
				
				return $this->oApiAccountsManager->updateAccount($oAccount);
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UserNotAllowed);
		}

		return false;
	}
	
	/**
	 * @param int $iUserId
	 * @return string
	 */
	private function getUUIDById($iUserId)
	{
		$sUUID = '';
		
		if (\is_numeric($iUserId))
		{
			$oManagerApi = \Aurora\System\Api::GetSystemManager('eav', 'db');
			$oEntity = $oManagerApi->getEntity((int) \Aurora\System\Api::getAuthenticatedUserId());
			if ($oEntity instanceof \Aurora\System\EAV\Entity)
			{
				$sUUID = $oEntity->UUID;
			}
		}
		
		return $sUUID;
	}
	
	/**
	 * @param int $UserId
	 * @param int $AccountID
	 * @param array $UploadData
	 * @return array
	 */
	public function UploadAttachment($UserId, $AccountID, $UploadData)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$sUUID = $this->getUUIDById($UserId);
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$sError = '';
		$aResponse = array();

		if ($oAccount instanceof \CMailAccount)
		{
			if (\is_array($UploadData))
			{
				$sSavedName = 'upload-post-'.\md5($UploadData['name'].$UploadData['tmp_name']);
				$rData = false;
				if (\is_resource($UploadData['tmp_name']))
				{
					$rData = $UploadData['tmp_name'];
				}
				else
				{
					if ($this->oApiFileCache->moveUploadedFile($sUUID, $sSavedName, $UploadData['tmp_name']))
					{
						$rData = $this->oApiFileCache->getFile($sUUID, $sSavedName);
					}
				}
				if ($rData)
				{
					$sUploadName = $UploadData['name'];
					$iSize = $UploadData['size'];
					$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

					$bIframed = \Aurora\System\Api::isIframedMimeTypeSupported($sMimeType, $sUploadName);
					$sHash = \Aurora\System\Api::EncodeKeyValues(array(
						'TempFile' => true,
						'AccountID' => $AccountID,
						'Iframed' => $bIframed,
						'Name' => $sUploadName,
						'TempName' => $sSavedName
					));
					$aActions = array(
						'view' => array(
							'url' => '?mail-attachment/' . $sHash .'/view'
						),
						'download' => array(
							'url' => '?mail-attachment/' . $sHash
						)
					);
					$aResponse['Attachment'] = array(
						'Name' => $sUploadName,
						'TempName' => $sSavedName,
						'MimeType' => $sMimeType,
						'Size' =>  (int) $iSize,
						'Iframed' => $bIframed,
						'Hash' => $sHash,
						'Actions' => $aActions,
						'ThumbnailUrl' => '?mail-attachment/' . $sHash .'/thumb',
					);
				}
				else
				{
					$sError = 'unknown';
				}
			}
			else
			{
				$sError = 'unknown';
			}
		}
		else
		{
			$sError = 'auth';
		}

		if (0 < strlen($sError))
		{
			$aResponse['Error'] = $sError;
		}

		return $aResponse;
	}
	
	public function UploadMessageAttachments($AccountID, $Attachments = array())
	{
		$mResult = false;
		$self = $this;

		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount instanceof \CMailAccount)
		{
			$sUUID = $this->getUUIDById($oAccount->IdUser);
			try
			{
				if (is_array($Attachments) && 0 < count($Attachments))
				{
					$mResult = array();
					foreach ($Attachments as $sAttachment)
					{
						$aValues = \Aurora\System\Api::DecodeKeyValues($sAttachment);
						if (is_array($aValues))
						{
							$sFolder = isset($aValues['Folder']) ? $aValues['Folder'] : '';
							$iUid = (int) isset($aValues['Uid']) ? $aValues['Uid'] : 0;
							$sMimeIndex = (string) isset($aValues['MimeIndex']) ? $aValues['MimeIndex'] : '';

							$sTempName = md5($sAttachment);
							if (!$this->oApiFileCache->isFileExists($sUUID, $sTempName))
							{
								$this->oApiMailManager->directMessageToStream($oAccount,
									function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($sUUID, &$mResult, $sTempName, $sAttachment, $self) {
										if (is_resource($rResource))
										{
											$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
											$sFileName = $self->clearFileName($sFileName, $sContentType, $sMimeIndex);

											if ($self->oApiFileCache->putFile($sUUID, $sTempName, $rResource))
											{
												$mResult[$sTempName] = $sAttachment;
											}
										}
									}, $sFolder, $iUid, $sMimeIndex);
							}
							else
							{
								$mResult[$sTempName] = $sAttachment;
							}
						}
					}
				}
			}
			catch (\Exception $oException)
			{
				throw new \Aurora\System\Exceptions\ApiException(\ProjectCore\Notifications::MailServerError, $oException);
			}
		}

		return $mResult;
	}	
	
	public function EntryAutodiscover()
	{
		$sInput = \file_get_contents('php://input');

		\Aurora\System\Api::Log('#autodiscover:');
		\Aurora\System\Api::LogObject($sInput);

		$aMatches = array();
		$aEmailAddress = array();
		\preg_match("/\<AcceptableResponseSchema\>(.*?)\<\/AcceptableResponseSchema\>/i", $sInput, $aMatches);
		\preg_match("/\<EMailAddress\>(.*?)\<\/EMailAddress\>/", $sInput, $aEmailAddress);
		if (!empty($aMatches[1]) && !empty($aEmailAddress[1]))
		{
			$sIncomingServer = \trim(\Aurora\System\Api::GetSettings()->GetConf('WebMail/ExternalHostNameOfLocalImap'));
			$sOutgoingServer = \trim(\Aurora\System\Api::GetSettings()->GetConf('WebMail/ExternalHostNameOfLocalSmtp'));

			if (0 < \strlen($sIncomingServer) && 0 < \strlen($sOutgoingServer))
			{
				$iIncomingPort = 143;
				$iOutgoingPort = 25;

				$aMatch = array();
				if (\preg_match('/:([\d]+)$/', $sIncomingServer, $aMatch) && !empty($aMatch[1]) && \is_numeric($aMatch[1]))
				{
					$sIncomingServer = \preg_replace('/:[\d]+$/', $sIncomingServer, '');
					$iIncomingPort = (int) $aMatch[1];
				}

				$aMatch = array();
				if (\preg_match('/:([\d]+)$/', $sOutgoingServer, $aMatch) && !empty($aMatch[1]) && \is_numeric($aMatch[1]))
				{
					$sOutgoingServer = \preg_replace('/:[\d]+$/', $sOutgoingServer, '');
					$iOutgoingPort = (int) $aMatch[1];
				}

				$sResult = \implode("\n", array(
'<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">',
'	<Response xmlns="'.$aMatches[1].'">',
'		<Account>',
'			<AccountType>email</AccountType>',
'			<Action>settings</Action>',
'			<Protocol>',
'				<Type>IMAP</Type>',
'				<Server>'.$sIncomingServer.'</Server>',
'				<LoginName>'.$aEmailAddress[1].'</LoginName>',
'				<Port>'.$iIncomingPort.'</Port>',
'				<SSL>'.(993 === $iIncomingPort ? 'on' : 'off').'</SSL>',
'				<SPA>off</SPA>',
'				<AuthRequired>on</AuthRequired>',
'			</Protocol>',
'			<Protocol>',
'				<Type>SMTP</Type>',
'				<Server>'.$sOutgoingServer.'</Server>',
'				<LoginName>'.$aEmailAddress[1].'</LoginName>',
'				<Port>'.$iOutgoingPort.'</Port>',
'				<SSL>'.(465 === $iOutgoingPort ? 'on' : 'off').'</SSL>',
'				<SPA>off</SPA>',
'				<AuthRequired>on</AuthRequired>',
'			</Protocol>',
'		</Account>',
'	</Response>',
'</Autodiscover>'));
			}
		}

		if (empty($sResult))
		{
			$usec = $sec = 0;
			list($usec, $sec) = \explode(' ', \microtime());
			$sResult = \implode("\n", array('<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">',
(empty($aMatches[1]) ?
'	<Response>' :
'	<Response xmlns="'.$aMatches[1].'">'
),
'		<Error Time="'.\gmdate('H:i:s', $sec).\substr($usec, 0, \strlen($usec) - 2).'" Id="2477272013">',
'			<ErrorCode>600</ErrorCode>',
'			<Message>Invalid Request</Message>',
'			<DebugData />',
'		</Error>',
'	</Response>',
'</Autodiscover>'));
		}

		\header('Content-Type: text/xml');
		$sResult = '<'.'?xml version="1.0" encoding="utf-8"?'.'>'."\n".$sResult;

		\Aurora\System\Api::Log('');
		\Aurora\System\Api::Log($sResult);		
	}
	
	public function EntryMessageNewtab()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::Anonymous);

		$oApiIntegrator = \Aurora\System\Api::GetSystemManager('integrator');

		if ($oApiIntegrator)
		{
			$aConfig = array(
				'new_tab' => true,
				'modules_list' => array(
					'MailWebclient', 
					'ContactsWebclient', 
					'CalendarWebclient', 
					'MailSensitivityWebclientPlugin', 
					'OpenPgpWebclient'
				)
			);

			$oCoreWebclientModule = \Aurora\System\Api::GetModule('CoreWebclient');
			if ($oCoreWebclientModule instanceof \Aurora\System\Module\AbstractModule) 
			{
				$sResult = \file_get_contents($oCoreWebclientModule->GetPath().'/templates/Index.html');
				if (\is_string($sResult)) 
				{
					return strtr($sResult, array(
						'{{AppVersion}}' => AURORA_APP_VERSION,
						'{{IntegratorDir}}' => $oApiIntegrator->isRtl() ? 'rtl' : 'ltr',
						'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink(),
						'{{IntegratorBody}}' => $oApiIntegrator->buildBody($aConfig)
					));
				}
			}
		}
	}
	
	public function EntryDownloadAttachment()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$this->getRaw(
			(string) \Aurora\System\Application::GetPathItemByIndex(1, ''),
			(string) \Aurora\System\Application::GetPathItemByIndex(2, '')
		);		
	}	

	/**
	 * @param string $sKey
	 *
	 * @return void
	 */
	public function cacheByKey($sKey)
	{
		if (!empty($sKey))
		{
			$iUtcTimeStamp = \time();
			$iExpireTime = 3600 * 24 * 5;

			\header('Cache-Control: private', true);
			\header('Pragma: private', true);
			\header('Etag: '.\md5('Etag:'.\md5($sKey)), true);
			\header('Last-Modified: '.\gmdate('D, d M Y H:i:s', $iUtcTimeStamp - $iExpireTime).' UTC', true);
			\header('Expires: '.\gmdate('D, j M Y H:i:s', $iUtcTimeStamp + $iExpireTime).' UTC', true);
		}
	}

	/**
	 * @param string $sKey
	 *
	 * @return void
	 */
	public function verifyCacheByKey($sKey)
	{
		if (!empty($sKey))
		{
			$sIfModifiedSince = $this->oHttp->GetHeader('If-Modified-Since', '');
			if (!empty($sIfModifiedSince))
			{
				$this->oHttp->StatusHeader(304);
				$this->cacheByKey($sKey);
				exit();
			}
		}
	}	
	
	/**
	 * @param string $sFileName
	 * @param string $sContentType
	 * @param string $sMimeIndex = ''
	 *
	 * @return string
	 */
	public function clearFileName($sFileName, $sContentType, $sMimeIndex = '')
	{
		$sFileName = 0 === \strlen($sFileName) ? \preg_replace('/[^a-zA-Z0-9]/', '.', (empty($sMimeIndex) ? '' : $sMimeIndex.'.').$sContentType) : $sFileName;
		$sClearedFileName = \preg_replace('/[\s]+/', ' ', \preg_replace('/[\.]+/', '.', $sFileName));
		$sExt = \MailSo\Base\Utils::GetFileExtension($sClearedFileName);

		$iSize = 100;
		if ($iSize < \strlen($sClearedFileName) - \strlen($sExt))
		{
			$sClearedFileName = \substr($sClearedFileName, 0, $iSize).(empty($sExt) ? '' : '.'.$sExt);
		}

		return \MailSo\Base\Utils::ClearFileName(\MailSo\Base\Utils::Utf8Clear($sClearedFileName));
	}	
	
	/**
	 * @param bool $bDownload
	 * @param string $sContentType
	 * @param string $sFileName
	 *
	 * @return bool
	 */
	public function RawOutputHeaders($bDownload, $sContentType, $sFileName)
	{
		if ($bDownload)
		{
			\header('Content-Type: '.$sContentType, true);
		}
		else
		{
			$aParts = \explode('/', $sContentType, 2);
			if (\in_array(\strtolower($aParts[0]), array('image', 'video', 'audio')) ||
				\in_array(\strtolower($sContentType), array('application/pdf', 'application/x-pdf', 'text/html')))
			{
				\header('Content-Type: '.$sContentType, true);
			}
			else
			{
				\header('Content-Type: text/plain', true);
			}
		}

		\header('Content-Disposition: '.($bDownload ? 'attachment' : 'inline' ).'; '.
			\trim(\MailSo\Base\Utils::EncodeHeaderUtf8AttributeValue('filename', $sFileName)), true);
		
		\header('Accept-Ranges: none', true);
		\header('Content-Transfer-Encoding: binary');
	}
	
	public function thumbResource($oAccount, $rResource, $sFileName)
	{
		$sMd5Hash = \md5(\rand(1000, 9999));
		
		$this->oApiFileCache->putFile($oAccount->UUID, 'Raw/Thumbnail/'.$sMd5Hash, $rResource, '_'.$sFileName);
		if ($this->oApiFileCache->isFileExists($oAccount->UUID, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName))
		{
			$sFullFilePath = $this->oApiFileCache->generateFullFilePath($oAccount->UUID, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName);
			$iRotateAngle = 0;
			if (\function_exists('exif_read_data')) 
			{ 
				if ($exif_data = @\exif_read_data($sFullFilePath, 'IFD0')) 
				{ 
					switch (@$exif_data['Orientation']) 
					{ 
						case 1: 
							$iRotateAngle = 0; 
							break; 
						case 3: 
							$iRotateAngle = 180; 
							break; 
						case 6: 
							$iRotateAngle = 270; 
							break; 
						case 8: 
							$iRotateAngle = 90; 
							break; 
					}
				}
			}
			
			try
			{
				$oThumb = new \PHPThumb\GD(
					$sFullFilePath
				);
				if ($iRotateAngle > 0)
				{
					$oThumb->rotateImageNDegrees($iRotateAngle);
				}
				
				$oThumb->adaptiveResize(120, 100)->show();
			}
			catch (\Exception $oE) {}
		}

		$this->oApiFileCache->clear($oAccount->UUID, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName);
	}	
	
	/**
	 * @return bool
	 */
	private function rawCallback($sRawKey, $fCallback, $bCache = true, &$oAccount = null)
	{
		$aValues = \Aurora\System\Api::DecodeKeyValues($sRawKey);
		
		$sFolder = '';
		$iUid = 0;
		$sMimeIndex = '';

		$oAccount = null;

		if (isset($aValues['AccountID']))
		{
			$oAccount = $this->oApiAccountsManager->getAccountById((int) $aValues['AccountID']);
			
			if (!$oAccount || \Aurora\System\Api::getAuthenticatedUserId() !== $oAccount->IdUser)
			{
				return false;
			}
		}

		$sFolder = isset($aValues['Folder']) ? $aValues['Folder'] : '';
		$iUid = (int) (isset($aValues['Uid']) ? $aValues['Uid'] : 0);
		$sMimeIndex = (string) (isset($aValues['MimeIndex']) ? $aValues['MimeIndex'] : '');
		$sContentTypeIn = (string) (isset($aValues['MimeType']) ? $aValues['MimeType'] : '');
		$sFileNameIn = (string) (isset($aValues['FileName']) ? $aValues['FileName'] : '');

		if ($bCache && 0 < \strlen($sFolder) && 0 < $iUid)
		{
			$this->verifyCacheByKey($sRawKey);
		}
		
		if (isset($aValues['TempFile'], $aValues['TempName'], $aValues['Name']) && $oAccount)
		{
			if ($bCache)
			{
				$this->verifyCacheByKey($sRawKey);
			}

			$bResult = false;
			$sUUID = $this->getUUIDById($oAccount->IdUser);
			$mResult = $this->oApiFileCache->getFile($sUUID, $aValues['TempName']);

			if (is_resource($mResult))
			{
				if ($bCache)
				{
					$this->cacheByKey($sRawKey);
				}

				$bResult = true;
				$sFileName = $aValues['Name'];
				$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
				$sFileName = $this->clearFileName($sFileName, $sContentType);

				call_user_func_array($fCallback, array(
					$oAccount, $sContentType, $sFileName, $mResult
				));
			}

			return $bResult;
		}

		$self = $this;
		return $this->oApiMailManager->directMessageToStream($oAccount,
			function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($self, $oAccount, $fCallback, $sRawKey, $bCache, $sContentTypeIn, $sFileNameIn) {
				if (\is_resource($rResource))
				{
					$sContentTypeOut = $sContentTypeIn;
					if (empty($sContentTypeOut))
					{
						$sContentTypeOut = $sContentType;
						if (empty($sContentTypeOut))
						{
							$sContentTypeOut = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
						}
					}

					$sFileNameOut = $sFileNameIn;
					if (empty($sFileNameOut) || '.' === $sFileNameOut{0})
					{
						$sFileNameOut = $sFileName;
					}

					$sFileNameOut = $self->clearFileName($sFileNameOut, $sContentType, $sMimeIndex);

					if ($bCache)
					{
						$self->cacheByKey($sRawKey);
					}

					\call_user_func_array($fCallback, array(
						$oAccount, $sContentTypeOut, $sFileNameOut, $rResource
					));
				}
			}, $sFolder, $iUid, $sMimeIndex);
	}
	
	
	/**
	 */
	private function getRaw($sHash, $sAction = '')
	{
		$self = $this;
		$bDownload = true;
		$bThumbnail = false;
		
		switch ($sAction)
		{
			case 'view':
				$bDownload = false;
				$bThumbnail = false;
			break;
			case 'thumb':
				$bDownload = false;
				$bThumbnail = true;
			break;
			default:
				$bDownload = true;
				$bThumbnail = false;
			break;
		}
		
		return $this->rawCallback($sHash, 
				function ($oAccount, $sContentType, $sFileName, $rResource) use ($self, $bDownload, $bThumbnail) {
			
			$self->RawOutputHeaders($bDownload, $sContentType, $sFileName);

			if (!$bDownload && 'text/html' === $sContentType)
			{
				$sHtml = \stream_get_contents($rResource);
				if ($sHtml)
				{
					$sCharset = '';
					$aMacth = array();
					if (\preg_match('/charset[\s]?=[\s]?([^\s"\']+)/i', $sHtml, $aMacth) && !empty($aMacth[1]))
					{
						$sCharset = $aMacth[1];
					}

					if ('' !== $sCharset && \MailSo\Base\Enumerations\Charset::UTF_8 !== $sCharset)
					{
						$sHtml = \MailSo\Base\Utils::ConvertEncoding($sHtml,
							\MailSo\Base\Utils::NormalizeCharset($sCharset, true), \MailSo\Base\Enumerations\Charset::UTF_8);
					}

					$oCssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($sHtml);
					$oCssToInlineStyles->setEncoding('utf-8');
					$oCssToInlineStyles->setUseInlineStylesBlock(true);

					echo '<html><head></head><body>'.
						\MailSo\Base\HtmlUtils::ClearHtmlSimple($oCssToInlineStyles->convert(), true, true).
						'</body></html>';
				}
			}
			else
			{
				if ($bThumbnail && !$bDownload)
				{
					$self->thumbResource($oAccount, $rResource, $sFileName);
				}
				else
				{
					\MailSo\Base\Utils::FpassthruWithTimeLimitReset($rResource);
				}
			}
			
		}, !$bDownload);
	}	
}
