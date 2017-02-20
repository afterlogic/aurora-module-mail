<?php

class MailModule extends AApiModule
{
	public $oApiMailManager = null;
	public $oApiAccountsManager = null;
	public $oApiServersManager = null;
	
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
		
		$this->extendObject('CUser', array(
				'AllowAutosaveInDrafts'		=> array('bool', true),
				'AllowChangeInputDirection'	=> array('bool', false),
				'MailsPerPage'				=> array('int', 20),
				'SaveRepliesToCurrFolder'	=> array('bool', false),
				'UseThreads'				=> array('bool', true),
			)
		);

		$this->AddEntries(array(
				'autodiscover' => 'EntryAutodiscover',
				'message-newtab' => 'EntryMessageNewtab'
			)
		);
		
		$this->subscribeEvent('Login', array($this, 'onLogin'));
	}
	
	/**
	 * Obtains list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetSettings()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$aSettings = array(
			'Accounts' => array(),
			'AllowAddNewAccounts' => $this->getConfig('AllowAddNewAccounts', false),
			'AllowAppRegisterMailto' => $this->getConfig('AllowAppRegisterMailto', false),
			'AllowAutosaveInDrafts' => $this->getConfig('AllowAutosaveInDrafts', false),
			'AllowChangeEmailSettings' => $this->getConfig('AllowChangeEmailSettings', false),
			'AllowChangeInputDirection' => $this->getConfig('AllowChangeInputDirection', false),
			'AllowExpandFolders' => $this->getConfig('AllowExpandFolders', false),
			'AllowFetchers' => $this->getConfig('AllowFetchers', false),
			'AllowIdentities' => $this->getConfig('AllowIdentities', false),
			'AllowInsertImage' => $this->getConfig('AllowInsertImage', false),
			'AllowSaveMessageAsPdf' => $this->getConfig('AllowSaveMessageAsPdf', false),
			'AllowThreads' => $this->getConfig('AllowThreads', false),
			'AllowZipAttachments' => $this->getConfig('AllowZipAttachments', false),
			'AutoSave' => $this->getConfig('AutoSave', false),
			'AutoSaveIntervalSeconds' => $this->getConfig('AutoSaveIntervalSeconds', 60),
			'AutosignOutgoingEmails' => $this->getConfig('AutosignOutgoingEmails', false),
			'ComposeToolbarOrder' => $this->getConfig('ComposeToolbarOrder', array()),
			'DefaultFontName' => $this->getConfig('DefaultFontName', 'Tahoma'),
			'DefaultFontSize' => $this->getConfig('DefaultFontSize', 3),
			'ImageUploadSizeLimit' => $this->getConfig('ImageUploadSizeLimit', 0),
			'JoinReplyPrefixes' => $this->getConfig('JoinReplyPrefixes', false),
			'MailsPerPage' => $this->getConfig('MailsPerPage', 20),
			'MaxMessagesBodiesSizeToPrefetch' => $this->getConfig('MaxMessagesBodiesSizeToPrefetch', 50000),
			'SaveRepliesToCurrFolder' => $this->getConfig('SaveRepliesToCurrFolder', false),
			'UseThreads' => $this->getConfig('UseThreads', true)
		);

		
		$oUser = \CApi::getAuthenticatedUser();
		if ($oUser && $oUser->Role !== \EUserRole::SuperAdmin)
		{
			$aAcc = $this->oApiAccountsManager->getUserAccounts($oUser->EntityId);
			$aResponseAcc = [];
			foreach($aAcc as $oAccount)
			{
				$oAccount->getServer();
				$aResponseAcc[] = $oAccount->toResponseArray();
			}
			$aSettings['Accounts'] = $aResponseAcc;
			$aSettings['AllowAutosaveInDrafts'] = $oUser->{$this->GetName().'::AllowAutosaveInDrafts'};
			$aSettings['AllowChangeInputDirection'] = $oUser->{$this->GetName().'::AllowChangeInputDirection'};
			$aSettings['MailsPerPage'] = $oUser->{$this->GetName().'::MailsPerPage'};
			$aSettings['SaveRepliesToCurrFolder'] = $oUser->{$this->GetName().'::SaveRepliesToCurrFolder'};
			$aSettings['UseThreads'] = $oUser->{$this->GetName().'::UseThreads'};
		}
		
		return $aSettings;
	}
	
	public function UpdateSettings($MailsPerPage, $UseThreads, $SaveRepliesToCurrFolder, $AllowChangeInputDirection)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oUser = \CApi::getAuthenticatedUser();
		if ($oUser)
		{
			if ($oUser->Role === \EUserRole::NormalUser)
			{
				$oCoreDecorator = \CApi::GetModuleDecorator('Core');
				$oUser->{$this->GetName().'::MailsPerPage'} = $MailsPerPage;
				$oUser->{$this->GetName().'::UseThreads'} = $UseThreads;
				$oUser->{$this->GetName().'::SaveRepliesToCurrFolder'} = $SaveRepliesToCurrFolder;
				$oUser->{$this->GetName().'::AllowChangeInputDirection'} = $AllowChangeInputDirection;
				return $oCoreDecorator->UpdateUserObject($oUser);
			}
			if ($oUser->Role === \EUserRole::SuperAdmin)
			{
				$oSettings =& CApi::GetSettings();
				$oSettings->SetConf('MailsPerPage', $MailsPerPage);
				$oSettings->SetConf('UseThreads', $UseThreads);
				$oSettings->SetConf('SaveRepliesToCurrFolder', $SaveRepliesToCurrFolder);
				$oSettings->SetConf('AllowChangeInputDirection', $AllowChangeInputDirection);
				return $oSettings->Save();
			}
		}
		
		return false;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function CreateAccount($iUserId = 0, $FriendlyName = '', $Email = '', $IncomingLogin = '', 
			$IncomingPassword = '', $Server = null)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if ($iUserId === 0)
		{
			$iUserId = \CApi::getAuthenticatedUserId();
		}

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

		$oAccount = \CMailAccount::createInstance();

		$oAccount->IdUser = $iUserId;
		$oAccount->FriendlyName = $FriendlyName;
		$oAccount->Email = $Email;
		$oAccount->IncomingLogin = $IncomingLogin;
		$oAccount->IncomingPassword = $IncomingPassword;
		$oAccount->ServerId = $iServerId;
		
		$oUser = null;
		$oCoreDecorator = \CApi::GetModuleDecorator('Core');
		if ($oCoreDecorator)
		{
			$oUser = $oCoreDecorator->GetUser($iUserId);
			if ($oUser instanceof \CUser && $oUser->PublicId === $Email && !$this->oApiAccountsManager->useToAuthorizeAccountExists($Email))
			{
				$oAccount->UseToAuthorize = true;
			}
		}

		if ($this->oApiAccountsManager->createAccount($oAccount))
		{
			return $oAccount;
		}
		
		return false;
	}
	
	public function UpdateAccount($AccountID, $UseToAuthorize = null, $Email = null, $FriendlyName = null, $IncomingLogin = null, 
			$IncomingPassword = null, $Server = null)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
				if ($Server !== null && !empty($Server['ServerId']))
				{
					if ($oAccount->ServerId === $Server['ServerId'])
					{
						$oAccServer = $oAccount->getServer();
						if ($oAccServer && $oAccServer->OwnerType === \EMailServerOwnerType::Account)
						{
							$this->oApiServersManager->updateServer($Server['ServerId'], $Server['IncomingServer'], $Server['IncomingServer'], 
								$Server['IncomingPort'], $Server['IncomingUseSsl'], $Server['OutgoingServer'], 
								$Server['OutgoingPort'], $Server['OutgoingUseAuth'], $Server['OutgoingUseSsl'], 0);
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
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::UserNotAllowed);
		}

		return false;
	}
	
	/**
	 * @param int $AccountID
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function DeleteAccount($AccountID)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::UserNotAllowed);
		}
	}
	
	public function onLogin($aArgs, &$mResult)
	{
		$oAccount = $this->oApiAccountsManager->getUseToAuthorizeAccount(
			$aArgs['Login'], 
			$aArgs['Password']
		);

		if ($oAccount)
		{
			try
			{
				$this->oApiMailManager->validateAccountConnection($oAccount);
				$mResult = array(
					'token' => 'auth',
					'sign-me' => $aArgs['SignMe'],
					'id' => $oAccount->IdUser,
					'account' => $oAccount->EntityId
				);
			}
			catch (\Exception $oEx) {}
		}
	}
	
	/**** Ajax methods ****/
	public function GetServers($TenantId = 0)
	{
//		if ($TenantId === 0)
//		{
//			\CApi::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
//		}
//		else
//		{
//			\CApi::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
//		}
		
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiServersManager->getServerList($TenantId);
	}
	
	public function CreateServer($Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $TenantId = 0)
	{
		$sOwnerType = ($TenantId === 0) ? \EMailServerOwnerType::SuperAdmin : \EMailServerOwnerType::Tenant;
		
		if ($TenantId === 0)
		{
			\CApi::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
		}
		else
		{
			\CApi::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		}
		
		return $this->oApiServersManager->createServer($Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $sOwnerType, $TenantId);
	}
	
	public function UpdateServer($ServerId, $Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $TenantId = 0)
	{
		if ($TenantId === 0)
		{
			\CApi::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
		}
		else
		{
			\CApi::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		}
		
		return $this->oApiServersManager->updateServer($ServerId, $Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth, $TenantId);
	}
	
	public function DeleteServer($ServerId, $TenantId = 0)
	{
		if ($TenantId === 0)
		{
			\CApi::checkUserRoleIsAtLeast(\EUserRole::SuperAdmin);
		}
		else
		{
			\CApi::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		}
		
		return $this->oApiServersManager->deleteServer($ServerId, $TenantId);
	}
	
	/**
	 * @param int $AccountID
	 * @return array | boolean
	 */
	public function GetExtensions($AccountID)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function GetMessages($AccountID, $Folder, $Offset = 0, $Limit = 20, $Search = '', $Filters = '', $UseThreads = 0, $InboxUidnext = '')
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$sOffset = trim((string) $Offset);
		$sLimit = trim((string) $Limit);
		$sSearch = trim((string) $Search);
		$bUseThreads = '1' === trim((string) $UseThreads);
		$sInboxUidnext = $InboxUidnext;
		
		$aFilters = array();
		$sFilters = strtolower(trim((string) $Filters));
		if (0 < strlen($sFilters))
		{
			$aFilters = array_filter(explode(',', $sFilters), function ($sValue) {
				return '' !== trim($sValue);
			});
		}

		$iOffset = 0 < strlen($sOffset) && is_numeric($sOffset) ? (int) $sOffset : 0;
		$iLimit = 0 < strlen($sLimit) && is_numeric($sLimit) ? (int) $sLimit : 0;

		if (0 === strlen(trim($Folder)) || 0 > $iOffset || 0 >= $iLimit || 200 < $sLimit)
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessageList(
			$oAccount, $Folder, $iOffset, $iLimit, $sSearch, $bUseThreads, $aFilters, $sInboxUidnext);
	}

	/**
	 * @param int $AccountID
	 * @param array $Folders
	 * @param string $InboxUidnext
	 * @return array
	 * @throws \System\Exceptions\AuroraApiException
	 * @throws \MailSo\Net\Exceptions\ConnectionException
	 */
	public function GetRelevantFoldersInformation($AccountID, $Folders, $InboxUidnext = '')
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (!is_array($Folders) || 0 === count($Folders))
		{
			throw new \System\Exceptions\AuroraApiException(\ProjectSystem\Notifications::InvalidInputParameter);
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
			\CApi::Log((string) $oException);
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		return $this->oApiMailManager->getQuota($oAccount);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function GetMessagesBodies($AccountID, $Folder, $Uids)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Folder)) || !is_array($Uids) || 0 === count($Uids))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$aList = array();
		foreach ($Uids as $iUid)
		{
			if (is_numeric($iUid))
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
	 * @throws \System\Exceptions\AuroraApiException
	 * @throws CApiInvalidArgumentException
	 */
	public function GetMessage($AccountID, $Folder, $Uid, $Rfc822MimeIndex = '')
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$iBodyTextLimit = 600000;
		
		$iUid = 0 < strlen($Uid) && is_numeric($Uid) ? (int) $Uid : 0;

		if (0 === strlen(trim($Folder)) || 0 >= $iUid)
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (0 === strlen($Folder) || !is_numeric($iUid) || 0 >= (int) $iUid)
		{
			throw new CApiInvalidArgumentException();
		}

		$oImapClient =& $this->oApiMailManager->_getImapClient($oAccount);

		$oImapClient->FolderExamine($Folder);

		$oMessage = false;

		$aTextMimeIndexes = array();
		$aAscPartsIds = array();

		$aFetchResponse = $oImapClient->Fetch(array(
			\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE), $iUid, true);

		$oBodyStructure = (0 < count($aFetchResponse)) ? $aFetchResponse[0]->GetFetchBodyStructure($Rfc822MimeIndex) : null;
		
		$aCustomParts = array();
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
			0 < strlen($Rfc822MimeIndex)
				? \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$Rfc822MimeIndex.'.HEADER]'
				: \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
		);

		if (0 < count($aTextMimeIndexes))
		{
			if (0 < strlen($Rfc822MimeIndex) && is_numeric($Rfc822MimeIndex))
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
			$oMessage = CApiMailMessage::createInstance($Folder, $aFetchResponse[0], $oBodyStructure, $Rfc822MimeIndex, $aAscPartsIds);
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
				$bAlwaysShowImagesInMessage = !!\CApi::GetSettingsConf('WebMail/AlwaysShowImagesInMessage');

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
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::CanNotGetMessage);
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::FLAGGED);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $sFolderFullNameRaw
	 * @param string $sUids
	 * @param int $iSetAction
	 * @param string $sFlagName
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	private function setMessageFlag($AccountID, $sFolderFullNameRaw, $sUids, $iSetAction, $sFlagName)
	{
		$bSetAction = 1 === $iSetAction;
		$aUids = \api_Utils::ExplodeIntUids((string) $sUids);

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagName,
			$bSetAction ? \EMailMessageStoreAction::Add : \EMailMessageStoreAction::Remove);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SetAllMessagesSeen($AccountID, $Folder)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function MoveMessages($AccountID, $Folder, $ToFolder, $Uids)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$aUids = \api_Utils::ExplodeIntUids((string) $Uids);

		if (0 === strlen(trim($Folder)) || 0 === strlen(trim($ToFolder)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		try
		{
			$this->oApiMailManager->moveMessage($oAccount, $Folder, $ToFolder, $aUids);
		}
		catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::CanNotMoveMessageQuota, $oException,
				$oResponse instanceof \MailSo\Imap\Response ? $oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '');
		}
		catch (\Exception $oException)
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::CanNotMoveMessage, $oException,
				$oException->getMessage());
		}

		return true;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uids
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function DeleteMessages($AccountID, $Folder, $Uids)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$aUids = \api_Utils::ExplodeIntUids((string) $Uids);

		if (0 === strlen(trim($Folder)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function CreateFolder($AccountID, $FolderNameInUtf8, $FolderParentFullNameRaw, $Delimiter)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen($FolderNameInUtf8) || 1 !== strlen($Delimiter))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->createFolder($oAccount, $FolderNameInUtf8, $Delimiter, $FolderParentFullNameRaw);

		if (!$oAccount->isExtensionEnabled(\CMailAccount::DisableFoldersManualSort))
		{
			$aFoldersOrderList = $this->oApiMailManager->getFoldersOrder($oAccount);
			if (is_array($aFoldersOrderList) && 0 < count($aFoldersOrderList))
			{
				$aFoldersOrderListNew = $aFoldersOrderList;

				$sFolderNameInUtf7Imap = \MailSo\Base\Utils::ConvertEncoding($FolderNameInUtf8,
					\MailSo\Base\Enumerations\Charset::UTF_8,
					\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

				$sFolderFullNameRaw = (0 < strlen($FolderParentFullNameRaw) ? $FolderParentFullNameRaw.$Delimiter : '').
					$sFolderNameInUtf7Imap;

				$sFolderFullNameUtf8 = \MailSo\Base\Utils::ConvertEncoding($sFolderFullNameRaw,
					\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
					\MailSo\Base\Enumerations\Charset::UTF_8);

				$aFoldersOrderListNew[] = $sFolderFullNameRaw;

				$aFoldersOrderListUtf8 = array_map(function ($sValue) {
					return \MailSo\Base\Utils::ConvertEncoding($sValue,
						\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
						\MailSo\Base\Enumerations\Charset::UTF_8);
				}, $aFoldersOrderListNew);

				usort($aFoldersOrderListUtf8, 'strnatcasecmp');
				
				$iKey = array_search($sFolderFullNameUtf8, $aFoldersOrderListUtf8, true);
				if (is_int($iKey) && 0 < $iKey && isset($aFoldersOrderListUtf8[$iKey - 1]))
				{
					$sUpperName = $aFoldersOrderListUtf8[$iKey - 1];

					$iUpperKey = array_search(\MailSo\Base\Utils::ConvertEncoding($sUpperName,
						\MailSo\Base\Enumerations\Charset::UTF_8,
						\MailSo\Base\Enumerations\Charset::UTF_7_IMAP), $aFoldersOrderList, true);

					if (is_int($iUpperKey) && isset($aFoldersOrderList[$iUpperKey]))
					{
						\CApi::Log('insert order index:'.$iUpperKey);
						array_splice($aFoldersOrderList, $iUpperKey + 1, 0, $sFolderFullNameRaw);
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function RenameFolder($AccountID, $PrevFolderFullNameRaw, $NewFolderNameInUtf8)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen($PrevFolderFullNameRaw) || 0 === strlen($NewFolderNameInUtf8))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$mResult = $this->oApiMailManager->renameFolder($oAccount, $PrevFolderFullNameRaw, $NewFolderNameInUtf8);

		return (0 < strlen($mResult) ? array(
			'FullName' => $mResult,
			'FullNameHash' => md5($mResult)
		) : false);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function DeleteFolder($AccountID, $Folder)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SubscribeFolder($AccountID, $Folder, $SetAction)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function UpdateFoldersOrder($AccountID, $FolderList)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (!is_array($FolderList))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount->isExtensionEnabled(\CMailAccount::DisableFoldersManualSort))
		{
			return false;
		}

		return $this->oApiMailManager->updateFoldersOrder($oAccount, $FolderList);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function ClearFolder($AccountID, $Folder)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function GetMessagesByUids($AccountID, $Folder, $Uids)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Folder)) || !is_array($Uids))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessageListByUids($oAccount, $Folder, $Uids);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function GetMessagesFlags($AccountID, $Folder, $Uids)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Folder)) || !is_array($Uids))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
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
		$rStream = fopen('php://memory','r+');
		fwrite($rStream, '0');
		rewind($rStream);
		$rFilter = stream_filter_append($rStream, 'convert.base64-encode');
		
		if (0 === strlen(stream_get_contents($rStream)))
		{
			$iFileSize = \strlen($sRaw);
			$sRaw = str_pad($sRaw, $iFileSize + ($iFileSize % 3));
		}
		
		return $sRaw;
	}	
	
	/**
	 * @param \CAccount $oAccount
	 * @param \CFetcher $oFetcher = null
	 * @param bool $bWithDraftInfo = true
	 * @param \CIdentity $oIdentity = null
	 *
	 * @return \MailSo\Mime\Message
	 */
	private function buildMessage($oAccount, $sTo = '', $sCc = '', $sBcc = '', 
			$sSubject = '', $bTextIsHtml = false, $sText = '', $aAttachments = null, 
			$aDraftInfo = null, $sInReplyTo = '', $sReferences = '', $sImportance = '',
			$sSensitivity = '', $bReadingConfirmation = false,
			$oFetcher = null, $bWithDraftInfo = true, $oIdentity = null)
	{
		$oMessage = \MailSo\Mime\Message::NewInstance();
		$oMessage->RegenerateMessageId();

		$sXMailer = \CApi::GetConf('webmail.xmailer-value', '');
		if (0 < strlen($sXMailer))
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

		if ($bWithDraftInfo && is_array($aDraftInfo) && !empty($aDraftInfo[0]) && !empty($aDraftInfo[1]) && !empty($aDraftInfo[2]))
		{
			$oMessage->SetDraftInfo($aDraftInfo[0], $aDraftInfo[1], $aDraftInfo[2]);
		}

		if (0 < strlen($sInReplyTo))
		{
			$oMessage->SetInReplyTo($sInReplyTo);
		}

		if (0 < strlen($sReferences))
		{
			$oMessage->SetReferences($sReferences);
		}
		
		if (0 < strlen($sImportance) && in_array((int) $sImportance, array(
			\MailSo\Mime\Enumerations\MessagePriority::HIGH,
			\MailSo\Mime\Enumerations\MessagePriority::NORMAL,
			\MailSo\Mime\Enumerations\MessagePriority::LOW
		)))
		{
			$oMessage->SetPriority((int) $sImportance);
		}

		if (0 < strlen($sSensitivity) && in_array((int) $sSensitivity, array(
			\MailSo\Mime\Enumerations\Sensitivity::NOTHING,
			\MailSo\Mime\Enumerations\Sensitivity::CONFIDENTIAL,
			\MailSo\Mime\Enumerations\Sensitivity::PRIVATE_,
			\MailSo\Mime\Enumerations\Sensitivity::PERSONAL,
		)))
		{
			$oMessage->SetSensitivity((int) $sSensitivity);
		}

		if ($bReadingConfirmation)
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

		if (is_array($aAttachments))
		{
			foreach ($aAttachments as $sTempName => $aData)
			{
				if (is_array($aData) && isset($aData[0], $aData[1], $aData[2], $aData[3]))
				{
					$sFileName = (string) $aData[0];
					$sCID = (string) $aData[1];
					$bIsInline = '1' === (string) $aData[2];
					$bIsLinked = '1' === (string) $aData[3];
					$sContentLocation = isset($aData[4]) ? (string) $aData[4] : '';

					$oApiFileCache = /* @var $oApiFileCache \CApiFilecacheManager */ \CApi::GetSystemManager('filecache');
					$rResource = $oApiFileCache->getFile($oAccount->UUID, $sTempName);
					if (is_resource($rResource))
					{
						$iFileSize = $oApiFileCache->fileSize($oAccount->UUID, $sTempName);

						$sCID = trim(trim($sCID), '<>');
						$bIsFounded = 0 < strlen($sCID) ? in_array($sCID, $aFoundCids) : false;

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
	 * @param int $AccountID
	 * @param string $DraftFolder
	 * @param string $DraftUid
	 * @param string $FetcherID
	 * @param string $IdentityID
	 * @return array
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SaveMessage($AccountID, $FetcherID = "", $IdentityID = "", 
			$DraftInfo = [], $DraftUid = "", $To = "", $Cc = "", $Bcc = "", 
			$Subject = "", $Text = "", $IsHtml = false, $Importance = 1, 
			$ReadingConfirmation = 0, $Attachments = array(), $InReplyTo = "", 
			$References = "", $Sensitivity = 0, $ShowReport = true, $SentFolder = "", $DraftFolder = "")
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (0 === strlen($DraftFolder))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
		}

		$oFetcher = null;
		if (!empty($FetcherID) && is_numeric($FetcherID) && 0 < (int) $FetcherID)
		{
			$iFetcherID = (int) $FetcherID;

			$oApiFetchers = $this->GetManager('fetchers');
			$aFetchers = $oApiFetchers->getFetchers($oAccount);
			if (is_array($aFetchers) && 0 < count($aFetchers))
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
		if (!empty($IdentityID) && is_numeric($IdentityID) && 0 < (int) $IdentityID)
		{
			$oApiUsers = \CApi::GetSystemManager('users');
			$oIdentity = $oApiUsers->getIdentity((int) $IdentityID);
		}

		$oMessage = $this->buildMessage($oAccount, $To, $Cc, $Bcc, 
			$Subject, $IsHtml, $Text, $Attachments, $DraftInfo, $InReplyTo, $References, $Importance,
			$Sensitivity, $ReadingConfirmation, $oFetcher, true, $oIdentity);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->saveMessage($oAccount, $oMessage, $DraftFolder, $DraftUid);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \System\Notifications::CanNotSaveMessage;
				throw new \System\Exceptions\AuroraApiException($iCode, $oException);
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
//	public function SendMessageObject()
//	{
//		$oAccount = $this->getParamValue('Account', null);
//		$oMessage = $this->getParamValue('Message', null);
//		
//		return $this->oApiMailManager->sendMessage($oAccount, $oMessage);
//	}
	
	/**
	 * 
	 * @param int $AccountID
	 * @param string $SentFolder
	 * @param string $DraftFolder
	 * @param string $DraftUid
	 * @param array $DraftInfo
	 * @param string $FetcherID
	 * @param string $IdentityID
	 * @return array
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SendMessage($AccountID, $FetcherID = "", $IdentityID = "", 
			$DraftInfo = [], $DraftUid = "", $To = "", $Cc = "", $Bcc = "", 
			$Subject = "", $Text = "", $IsHtml = false, $Importance = 1, 
			$ReadingConfirmation = 0, $Attachments = array(), $InReplyTo = "", 
			$References = "", $Sensitivity = 0, $ShowReport = true, $SentFolder = "", $DraftFolder = "")
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$oFetcher = null;
		if (!empty($FetcherID) && is_numeric($FetcherID) && 0 < (int) $FetcherID)
		{
			$iFetcherID = (int) $FetcherID;

			$aFetchers = $this->oApiFetchersManager->getFetchers($oAccount);
			if (is_array($aFetchers) && 0 < count($aFetchers))
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
		$oApiUsers = CApi::GetSystemManager('users');
		if ($oApiUsers && !empty($IdentityID) && is_numeric($IdentityID) && 0 < (int) $IdentityID)
		{
			$oIdentity = $oApiUsers->getIdentity((int) $IdentityID);
		}

		$oMessage = $this->buildMessage($oAccount, $To, $Cc, $Bcc, 
			$Subject, $IsHtml, $Text, $Attachments, $DraftInfo, $InReplyTo, $References, $Importance,
			$Sensitivity, $ReadingConfirmation, $oFetcher, false, $oIdentity);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage, $oFetcher, $SentFolder, $DraftFolder, $DraftUid);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \System\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \System\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \System\Notifications::CanNotSendMessage;
						break;
					case \Errs::Mail_CannotSaveMessageInSentItems:
						$iCode = \System\Notifications::CannotSaveMessageInSentItems;
						break;
					case \Errs::Mail_MailboxUnavailable:
						$iCode = \System\Notifications::MailboxUnavailable;
						break;
				}

				throw new \System\Exceptions\AuroraApiException($iCode, $oException, $oException->GetPreviousMessage(), $oException->GetObjectParams());
			}

			if ($mResult)
			{
				$aCollection = $oMessage->GetRcpt();

				$aEmails = array();
				$aCollection->ForeachList(function ($oEmail) use (&$aEmails) {
					$aEmails[strtolower($oEmail->GetEmail())] = trim($oEmail->GetDisplayName());
				});

				if (is_array($aEmails))
				{
					\CApi::ExecuteMethod('Contacs::updateSuggestTable', array('Emails' => $aEmails));
				}
			}

			if (is_array($DraftInfo) && 3 === count($DraftInfo))
			{
				$sDraftInfoType = $DraftInfo[0];
				$sDraftInfoUid = $DraftInfo[1];
				$sDraftInfoFolder = $DraftInfo[2];

				try
				{
					switch (strtolower($sDraftInfoType))
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

		\CApi::LogEvent(\EEvents::MessageSend, $oAccount);
		return $mResult;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $ConfirmFolder
	 * @param string $ConfirmUid
	 * @return array
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SendConfirmationMessage($AccountID, $ConfirmFolder, $ConfirmUid)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$oMessage = $this->buildConfirmationMessage($oAccount);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \System\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \System\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \System\Notifications::CanNotSendMessage;
						break;
				}

				throw new \System\Exceptions\AuroraApiException($iCode, $oException);
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$aData = array();
		if (0 < strlen(trim($Sent)))
		{
			$aData[$Sent] = \EFolderType::Sent;
		}
		if (0 < strlen(trim($Drafts)))
		{
			$aData[$Drafts] = \EFolderType::Drafts;
		}
		if (0 < strlen(trim($Trash)))
		{
			$aData[$Trash] = \EFolderType::Trash;
		}
		if (0 < strlen(trim($Spam)))
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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

			$sExec = \CApi::DataPath().'/system/wkhtmltopdf/linux/wkhtmltopdf';
			if (!file_exists($sExec))
			{
				$sExec = \CApi::DataPath().'/system/wkhtmltopdf/win/wkhtmltopdf.exe';
				if (!file_exists($sExec))
				{
					$sExec = '';
				}
			}

			if (0 < strlen($sExec))
			{
				$oSnappy = new \Knp\Snappy\Pdf($sExec);
				$oSnappy->setOption('quiet', true);
				$oSnappy->setOption('disable-javascript', true);

				$oApiFileCache = \CApi::GetSystemManager('filecache');
				$oSnappy->generateFromHtml($oCssToInlineStyles->convert(),
					$oApiFileCache->generateFullFilePath($oAccount, $sSavedName), array(), true);

				return array(
					'Name' => $sFileName,
					'TempName' => $sSavedName,
					'MimeType' => $sMimeType,
					'Size' =>  (int) $oApiFileCache->fileSize($oAccount, $sSavedName),
					'Hash' => \CApi::EncodeKeyValues(array(
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SetEmailSafety($AccountID, $Email)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (0 === strlen(trim($Email)))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount)
		{
			$oApiUsersManager = \CApi::GetSystemManager('users');
			$mResult = $oApiUsersManager->getUserIdentities($oAccount->IdUser);
		}
		
		return $mResult;
	}
	
	public function UpdateSignature($AccountID, $UseSignature = null, $Signature = null)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::UserNotAllowed);
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
		
		if (is_numeric($iUserId))
		{
			$oManagerApi = \CApi::GetSystemManager('eav', 'db');
			$oEntity = $oManagerApi->getEntity((int) \CApi::getAuthenticatedUserId());
			if ($oEntity instanceof \CEntity)
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$sUUID = $this->getUUIDById($AccountID);
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$sError = '';
		$aResponse = array();

		if ($oAccount instanceof \CMailAccount)
		{
			$sUUID = $oAccount->UUID;
			if (is_array($UploadData))
			{
				$sSavedName = 'upload-post-'.md5($UploadData['name'].$UploadData['tmp_name']);
				$rData = false;
				if (is_resource($UploadData['tmp_name']))
				{
					$rData = $UploadData['tmp_name'];
				}
				else
				{
					$oApiFileCacheManager = \CApi::GetSystemManager('filecache');
					if ($oApiFileCacheManager->moveUploadedFile($sUUID, $sSavedName, $UploadData['tmp_name']))
					{
						$rData = $oApiFileCacheManager->getFile($sUUID, $sSavedName);
					}
				}
				if ($rData)
				{
					$sUploadName = $UploadData['name'];
					$iSize = $UploadData['size'];
					$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

					$bIframed = \CApi::isIframedMimeTypeSupported($sMimeType, $sUploadName);
					$aResponse['Attachment'] = array(
						'Name' => $sUploadName,
						'TempName' => $sSavedName,
						'MimeType' => $sMimeType,
						'Size' =>  (int) $iSize,
						'Iframed' => $bIframed,
						'Hash' => \CApi::EncodeKeyValues(array(
							'TempFile' => true,
							'AccountID' => $AccountID,
							'Iframed' => $bIframed,
							'Name' => $sUploadName,
							'TempName' => $sSavedName
						))
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
	
	public function EntryAutodiscover()
	{
		$sInput = \file_get_contents('php://input');

		\CApi::Log('#autodiscover:');
		\CApi::LogObject($sInput);

		$aMatches = array();
		$aEmailAddress = array();
		\preg_match("/\<AcceptableResponseSchema\>(.*?)\<\/AcceptableResponseSchema\>/i", $sInput, $aMatches);
		\preg_match("/\<EMailAddress\>(.*?)\<\/EMailAddress\>/", $sInput, $aEmailAddress);
		if (!empty($aMatches[1]) && !empty($aEmailAddress[1]))
		{
			$sIncomingServer = trim(\CApi::GetSettingsConf('WebMail/ExternalHostNameOfLocalImap'));
			$sOutgoingServer = trim(\CApi::GetSettingsConf('WebMail/ExternalHostNameOfLocalSmtp'));

			if (0 < \strlen($sIncomingServer) && 0 < \strlen($sOutgoingServer))
			{
				$iIncomingPort = 143;
				$iOutgoingPort = 25;

				$aMatch = array();
				if (\preg_match('/:([\d]+)$/', $sIncomingServer, $aMatch) && !empty($aMatch[1]) && is_numeric($aMatch[1]))
				{
					$sIncomingServer = preg_replace('/:[\d]+$/', $sIncomingServer, '');
					$iIncomingPort = (int) $aMatch[1];
				}

				$aMatch = array();
				if (\preg_match('/:([\d]+)$/', $sOutgoingServer, $aMatch) && !empty($aMatch[1]) && is_numeric($aMatch[1]))
				{
					$sOutgoingServer = preg_replace('/:[\d]+$/', $sOutgoingServer, '');
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
			list($usec, $sec) = \explode(' ', microtime());
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

		header('Content-Type: text/xml');
		$sResult = '<'.'?xml version="1.0" encoding="utf-8"?'.'>'."\n".$sResult;

		\CApi::Log('');
		\CApi::Log($sResult);		
	}
	
	public function EntryMessageNewtab()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);

		$oApiIntegrator = \CApi::GetSystemManager('integrator');

		if ($oApiIntegrator)
		{
			$aConfig = array(
				'new_tab' => true,
				'modules_list' => array('MailWebclient', 'ContactsWebclient', 'CalendarWebclient', 'MailSensitivityWebclientPlugin', 'OpenPgpWebclient')
			);

			$oCoreWebclientModule = \CApi::GetModule('CoreWebclient');
			if ($oCoreWebclientModule instanceof \AApiModule) 
			{
				$sResult = file_get_contents($oCoreWebclientModule->GetPath().'/templates/Index.html');
				if (is_string($sResult)) 
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
}
