<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail;

/**
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	public $oApiMailManager = null;
	public $oApiAccountsManager = null;
	public $oApiServersManager = null;
	public $oApiIdentitiesManager = null;
	public $oApiSieveManager = null;
	
	/* 
	 * @var $oApiFileCache \Aurora\System\Managers\Filecache\Manager 
	 */	
	public $oApiFileCache = null;
	
	/**
	 * Initializes Mail Module.
	 * 
	 * @ignore
	 */
	public function init() 
	{
		$this->incClasses(
			array(
				'account',
				'identity',
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
				'system-folder',
				'sender'
			)
		);
		
		$this->oApiAccountsManager = $this->GetManager('accounts');
		$this->oApiServersManager = $this->GetManager('servers');
		$this->oApiIdentitiesManager = $this->GetManager('identities');
		$this->oApiMailManager = $this->GetManager('main');
		$this->oApiFileCache = \Aurora\System\Api::GetSystemManager('Filecache');
		$this->oApiSieveManager = $this->GetManager('sieve');
		
		$this->extendObject('CUser', array(
				'AllowAutosaveInDrafts'	=> array('bool', (bool)$this->getConfig('AllowAutosaveInDrafts', false)),
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
	
	/***** public functions might be called with web API *****/
	/**
	 * Obtains list of module settings for authenticated user.
	 * @return array
	 */
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$aSettings = array(
			'Accounts' => array(),
			'AllowAddAccounts' => $this->getConfig('AllowAddAccounts', false),
			'AllowAutosaveInDrafts' => (bool)$this->getConfig('AllowAutosaveInDrafts', false),
			'AllowChangeEmailSettings' => $this->getConfig('AllowChangeEmailSettings', false),
			'AllowFetchers' => $this->getConfig('AllowFetchers', false),
			'AllowIdentities' => $this->getConfig('AllowIdentities', false),
			'AllowFilters' => $this->getConfig('AllowFilters', false),
			'AllowForward' => $this->getConfig('AllowForward', false),
			'AllowAutoresponder' => $this->getConfig('AllowAutoresponder', false),
			'AllowInsertImage' => $this->getConfig('AllowInsertImage', false),
			'AllowThreads' => $this->getConfig('AllowThreads', false),
			'AllowZipAttachments' => $this->getConfig('AllowZipAttachments', false),
			'AutoSaveIntervalSeconds' => $this->getConfig('AutoSaveIntervalSeconds', 60),
			'AutosignOutgoingEmails' => $this->getConfig('AutosignOutgoingEmails', false),
			'ImageUploadSizeLimit' => $this->getConfig('ImageUploadSizeLimit', 0),
		);
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser && $oUser->Role === \EUserRole::NormalUser)
		{
			$aAcc = $this->GetAccounts($oUser->EntityId);
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
	
	/**
	 * Updates user settings.
	 * @param boolean $UseThreads Indicates if threads should be used for user.
	 * @param boolean $AllowAutosaveInDrafts Indicates if message should be saved automatically while compose.
	 * @return boolean
	 */
	public function UpdateSettings($UseThreads, $AllowAutosaveInDrafts)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser)
		{
			if ($oUser->Role === \EUserRole::NormalUser)
			{
				$oCoreDecorator = \Aurora\System\Api::GetModuleDecorator('Core');
				$oUser->{$this->GetName().'::UseThreads'} = $UseThreads;
				$oUser->{$this->GetName().'::AllowAutosaveInDrafts'} = $AllowAutosaveInDrafts;
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
	 * Obtains list of mail accounts for user.
	 * @param int $UserId User identifier.
	 * @return array|boolean
	 */
	public function GetAccounts($UserId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiAccountsManager->getUserAccounts($UserId);
	}
	
	/**
	 * Obtains mail account with specified identifier.
	 * @param int $AccountId Identifier of mail account to obtain.
	 * @return \CMailAccount|boolean
	 */
	public function GetAccount($AccountId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountId);
		
		if ($oAccount->IdUser === $oUser->EntityId)
		{
			$mResult = $oAccount;
		}
				
		return $mResult;
	}
	
	
	/**
	 * Creates mail account.
	 * @param int $UserId User identifier.
	 * @param string $FriendlyName Friendly name.
	 * @param string $Email Email.
	 * @param string $IncomingLogin Login for IMAP connection.
	 * @param string $IncomingPassword Password for IMAP connection.
	 * @param array $Server List of settings for IMAP and SMTP connections.
	 * @return \CMailAccount|boolean
	 */
	public function CreateAccount($UserId = 0, $FriendlyName = '', $Email = '', $IncomingLogin = '', 
			$IncomingPassword = '', $Server = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$sDomains = explode('@', $Email)[1];

		if ($Email)
		{
			$bCustomServerCreated = false;
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
					$Server['OutgoingUseAuth'],
					$Server['Domains'] = $sDomains
				);
				
				$bCustomServerCreated = true;
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
			$bAccoutResult = $this->oApiAccountsManager->createAccount($oAccount);

			if ($bAccoutResult)
			{
				return $oAccount;
			}
			else if ($bCustomServerCreated)
			{
				$this->oApiServersManager->deleteServer($iServerId);
			}
		}

		return false;
	}
	
	/**
	 * Updates mail account.
	 * @param int $AccountID Identifier of account to update.
	 * @param boolean $UseToAuthorize Indicates if account can be used to authorize user.
	 * @param string $Email New email.
	 * @param string $FriendlyName New friendly name.
	 * @param string $IncomingLogin New loging for IMAP connection.
	 * @param string $IncomingPassword New password for IMAP connection.
	 * @param array $Server List of settings for IMAP connection.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
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
	 * Deletes mail account.
	 * @param int $AccountID Account idntifier.
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
	
	/**
	 * Obtains list of servers wich contains settings for IMAP and SMTP connections.
	 * @param int $TenantId Identifier of tenant which contains servers to return. If $TenantId is 0 returns server which are belonged to SuperAdmin, not Tenant.
	 * @return array
	 */
	public function GetServers($TenantId = 0)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiServersManager->getServerList($TenantId);
	}
	
	/**
	 * Obtains server with specified server identifier.
	 * @param int $ServerId Server identifier.
	 * @return \CMailServer|boolean
	 */
	public function GetServer($ServerId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiServersManager->getServer($ServerId);
	}
	
	/**
	 * Creates mail server.
	 * @param string $Name Server name.
	 * @param string $IncomingServer IMAP server.
	 * @param int $IncomingPort Port for connection to IMAP server.
	 * @param boolean $IncomingUseSsl Indicates if it is necessary to use ssl while connecting to IMAP server.
	 * @param string $OutgoingServer SMTP server.
	 * @param int $OutgoingPort Port for connection to SMTP server.
	 * @param boolean $OutgoingUseSsl Indicates if it is necessary to use ssl while connecting to SMTP server.
	 * @param boolean $OutgoingUseAuth Indicates if it is necessary to use authentication while connecting to SMTP server.
	 * @param string $Domains List of domains separated by comma.
	 * @param int $TenantId If tenant identifier is specified creates mail server belonged to specified tenant.
	 * @return int|boolean
	 */
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
	
	/**
	 * Updates mail server with specified identifier.
	 * @param int $ServerId Server identifier.
	 * @param string $Name New server name.
	 * @param string $IncomingServer New IMAP server.
	 * @param int $IncomingPort New port for connection to IMAP server.
	 * @param boolean $IncomingUseSsl Indicates if it is necessary to use ssl while connecting to IMAP server.
	 * @param string $OutgoingServer New SMTP server.
	 * @param int $OutgoingPort New port for connection to SMTP server.
	 * @param boolean $OutgoingUseSsl Indicates if it is necessary to use ssl while connecting to SMTP server.
	 * @param boolean $OutgoingUseAuth Indicates if it is necessary to use authentication while connecting to SMTP server.
	 * @param string $Domains New list of domains separated by comma.
	 * @param int $TenantId If tenant identifier is specified updates mail server belonged to specified tenant.
	 * @return boolean
	 */
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
	
	/**
	 * Deletes mail server.
	 * @param int $ServerId Identifier of server to delete.
	 * @param int $TenantId Identifier of tenant that contains mail server.
	 * @return boolean
	 */
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
	 * Obtains list of folders for specified account.
	 * @param int $AccountID Account identifier.
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
	 * Obtains message list for specified account and folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param int $Offset Says to skip that many folders before beginning to return them.
	 * @param int $Limit Limit says to return that many folders in the list.
	 * @param string $Search Search string.
	 * @param string $Filters List of conditions to obtain messages.
	 * @param int $UseThreads Indicates if it is necessary to return messages in threads.
	 * @param string $InboxUidnext UIDNEXT Inbox last value that is known on client side.
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function GetMessages($AccountID, $Folder, $Offset = 0, $Limit = 20, $Search = '', $Filters = '', $UseThreads = false, $InboxUidnext = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$sSearch = \trim((string) $Search);
		
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
			$oAccount, $Folder, $iOffset, $iLimit, $sSearch, $UseThreads, $aFilters, $InboxUidnext);
	}

	/**
	 * Obtains relevant information abount total and unseen messages count in specified folders.
	 * @param int $AccountID Account identifier.
	 * @param array $Folders List of folders' full names.
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 * @throws \MailSo\Net\Exceptions\ConnectionException
	 */
	public function GetRelevantFoldersInformation($AccountID, $Folders)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if (!\is_array($Folders) || 0 === \count($Folders))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$aResult = array();
		$oAccount = null;

		try
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			$aResult = $this->oApiMailManager->getFolderListInformation($oAccount, $Folders);
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
		);
	}	
	
	/**
	 * Obtains mail account quota.
	 * @param int $AccountID Account identifier.
	 * @return array
	 */
	public function GetQuota($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		return $this->oApiMailManager->getQuota($oAccount);
	}

	/**
	 * Obtains full data of specified messages including plain text, html text and attachments.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param array $Uids List of messages' uids.
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
	 * Obtains full data of specified message including plain text, html text and attachments.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param string $Uid Message uid.
	 * @param string $Rfc822MimeIndex  If specified obtains message from attachment of another message.
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
			
			$this->broadcastEvent('ExtendMessageData', $aData, $oMessage);
		}

		if (!($oMessage instanceof \CApiMailMessage))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::CanNotGetMessage);
		}

		return $oMessage;
	}

	/**
	 * Puts on or off seen flag of message.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param string $Uids List of messages' uids.
	 * @param boolean $SetAction Indicates if flag should be set or removed.
	 * @return boolean
	 */
	public function SetMessagesSeen($AccountID, $Folder, $Uids, $SetAction)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::SEEN);
	}	
	
	/**
	 * Puts on or off flagged flag of message.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param string $Uids List of messages' uids.
	 * @param boolean $SetAction Indicates if flag should be set or removed.
	 * @return boolean
	 */
	public function SetMessageFlagged($AccountID, $Folder, $Uids, $SetAction)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::FLAGGED);
	}
	
	/**
	 * Puts on or off seen flag for all messages in folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
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

		return $this->oApiMailManager->setMessageFlag($oAccount, $Folder, array(),
			\MailSo\Imap\Enumerations\MessageFlag::SEEN, \EMailMessageStoreAction::Add, true);
	}

	/**
	 * Moves messages from one folder to another.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Full name of the folder from which messages will be moved.
	 * @param string $ToFolder Full name of the folder to which messages will be moved.
	 * @param string $Uids Uids of messages to move.
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
	 * Deletes messages from folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param string $Uids Uids of messages to delete.
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
	
	/**
	 * Creates folder in mail account.
	 * @param int $AccountID Account identifier.
	 * @param string $FolderNameInUtf8 Name of folder to create.
	 * @param string $FolderParentFullNameRaw Full name of parent folder.
	 * @param string $Delimiter Delimiter that is used if full folder name.
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

		return true;
	}
	
	/**
	 * Renames folder.
	 * @param int $AccountID Account identifier.
	 * @param string $PrevFolderFullNameRaw Full name of folder to rename.
	 * @param string $NewFolderNameInUtf8 New folder name.
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
	 * Deletes folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Full name of folder to delete.
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
	 * Subscribes/unsubscribes folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Full name of folder to subscribe/unsubscribe.
	 * @param boolean $SetAction Indicates if folder should be subscribed or unsubscribed.
	 * @return boolean
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

		$this->oApiMailManager->subscribeFolder($oAccount, $Folder, $SetAction);
		
		return true;
	}	
	
	/**
	 * Updates order of folders.
	 * @param int $AccountID Account identifier.
	 * @param array $FolderList List of folders with new order.
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

		return $this->oApiMailManager->updateFoldersOrder($oAccount, $FolderList);
	}
	
	/**
	 * Removes all messages from folder. Uses for Trash and Spam folders.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
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
	 * Obtains message list for specified messages' uids.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param array $Uids Uids of messages to obtain.
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
	 * Obtains infomation about flagged flags for specified messages.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param array $Uids Uids of messages.
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
	 * Saves message to Drafts folder.
	 * @param int $AccountID Account identifier.
	 * @param string $FetcherID Fetcher identifier.
	 * @param string $IdentityID Identity identifier.
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
	/***** public functions might be called with web API *****/
	
	/***** private functions *****/
	/**
	 * Deletes all mail accounts which are belonged to the specified user.
	 * Called from subscribed event.
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
	 * Attempts to authorize user via mail account with specified credentials.
	 * Called from subscribed event.
	 * @ignore
	 * @param array $aArgs Credentials.
	 * @param array|boolean $mResult List of results values.
	 * @return boolean
	 */
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
						$sEmail, 
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
	
	/**
	 * Puts on or off some flag of message.
	 * @param int $AccountID account identifier.
	 * @param string $sFolderFullNameRaw Folder full name.
	 * @param string $sUids List of messages' uids.
	 * @param boolean $bSetAction Indicates if flag should be set or removed.
	 * @param string $sFlagName Name of message flag.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	private function setMessageFlag($AccountID, $sFolderFullNameRaw, $sUids, $bSetAction, $sFlagName)
	{
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
	 * When using a memory stream and the read
	 * filter "convert.base64-encode" the last 
	 * character is missing from the output if 
	 * the base64 conversion needs padding bytes. 
	 * @param string $sRaw
	 * @return string
	 */
	private function fixBase64EncodeOmitsPaddingBytes($sRaw)
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
	 * Builds message fo further sending or saving.
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
		
		$sUUID = \Aurora\System\Api::getUserUUIDById($oAccount->IdUser);

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
						$sRaw = $this->fixBase64EncodeOmitsPaddingBytes($sRaw);
						
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
	 * @param int $UserId
	 * @param int $AccountID
	 * @param string $FriendlyName
	 * @param string $Email
	 * @return int|bool
	 */
	public function CreateIdentity($UserId, $AccountID, $FriendlyName, $Email)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiIdentitiesManager->createIdentity($UserId, $AccountID, $FriendlyName, $Email);
	}
	
	/**
	 * @param int $UserId
	 * @param int $AccountID
	 * @param int $EntityId
	 * @param string $FriendlyName
	 * @param string $Email
	 * @param bool $Default
	 * @param bool $AccountPart
	 * @return bool
	 */
	public function UpdateIdentity($UserId, $AccountID, $EntityId, $FriendlyName, $Email, $Default = false, $AccountPart = false)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if ($Default)
		{
			$this->oApiIdentitiesManager->resetDefaultIdentity($UserId);
		}
		
		if ($AccountPart)
		{
			return $this->UpdateAccount($AccountID, null, $Email, $FriendlyName);
		}
		else
		{
			return $this->oApiIdentitiesManager->updateIdentity($EntityId, $FriendlyName, $Email, $Default);
		}
	}
	
	/**
	 * @param int $EntityId
	 * @return bool
	 */
	public function DeleteIdentity($EntityId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiIdentitiesManager->deleteIdentity($EntityId);
	}
	
	/**
	 * @param int $UserId
	 * @return array|false
	 */
	public function GetIdentities($UserId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiIdentitiesManager->getIdentities($UserId);
	}
	
	/**
	 * @param int $AccountID
	 * @param bool $UseSignature
	 * @param string $Signature
	 * @param int $IdentityId
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function UpdateSignature($AccountID, $UseSignature = null, $Signature = null, $IdentityId = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		if ($AccountID > 0)
		{
			if ($this->getConfig('AllowIdentities', false) && $IdentityId !== null)
			{
				return $this->oApiIdentitiesManager->updateIdentitySignature($IdentityId, $UseSignature, $Signature);
			}
			else
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
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UserNotAllowed);
		}

		return false;
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
		
		$sUUID = \Aurora\System\Api::getUserUUIDById($UserId);
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
					$aResponse['Attachment'] = \Aurora\System\Utils::GetClientFileResponse($UserId, $sUploadName, $sSavedName, $iSize);
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
	
	public function SaveAttachmentsAsTempFiles($AccountID, $Attachments = array())
	{
		$mResult = false;
		$self = $this;

		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount instanceof \CMailAccount)
		{
			$sUUID = \Aurora\System\Api::getUserUUIDById($oAccount->IdUser);
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
											$sFileName = \Aurora\System\Utils::clearFileName($sFileName, $sContentType, $sMimeIndex);

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
				throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::MailServerError, $oException);
			}
		}

		return $mResult;
	}	
	
	public function SaveMessageAsTempFile($AccountID, $MessageFolder, $MessageUid, $FileName)
	{
		$mResult = false;
		$self = $this;

		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount instanceof \CMailAccount)
		{
			$sUUID = \Aurora\System\Api::getUserUUIDById($oAccount->IdUser);
			try
			{
				$sMimeType = 'message/rfc822';
				$sTempName = md5($MessageFolder.$MessageUid);
				if (!$this->oApiFileCache->isFileExists($sUUID, $sTempName))
				{
					$this->oApiMailManager->directMessageToStream($oAccount,
						function ($rResource, $sContentType, $sFileName) use ($sUUID, $sTempName, &$sMimeType, $self) {
							if (is_resource($rResource))
							{
								$sMimeType = $sContentType;
								$sFileName = \Aurora\System\Utils::clearFileName($sFileName, $sMimeType, '');
								$self->oApiFileCache->putFile($sUUID, $sTempName, $rResource);
							}
						}, $MessageFolder, $MessageUid);
				}

				if ($this->oApiFileCache->isFileExists($sUUID, $sTempName))
				{
					$iSize = $this->oApiFileCache->fileSize($sUUID, $sTempName);
					$mResult = \Aurora\System\Utils::GetClientFileResponse($oAccount->IdUser, $FileName, $sTempName, $iSize);
				}
			}
			catch (\Exception $oException)
			{
				throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::MailServerError, $oException);
			}
		}

		return $mResult;
	}	
	
	/**
	 * 
	 * @param type $AccountID
	 * @param type $Folder
	 * @param type $UploadData
	 * @return string
	 * @throws \ProjectCore\Exceptions\ClientException
	 */
	public function UploadMessage($AccountID, $Folder, $UploadData)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$sError = '';
		$mResult = false;

		$oAccount = $this->oApiAccountsManager->getAccountById((int)$AccountID);
		
		if ($oAccount)
		{
			$sUUID = \Aurora\System\Api::getUserUUIDById($oAccount->IdUser);
			if (is_array($UploadData))
			{
				$sUploadName = $UploadData['name'];
				$bIsEmlExtension  = strtolower(pathinfo($sUploadName, PATHINFO_EXTENSION)) === 'eml';

				if ($bIsEmlExtension) 
				{
					$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

					$sSavedName = 'upload-post-' . md5($UploadData['name'] . $UploadData['tmp_name']);
					if (is_resource($UploadData['tmp_name']))
					{
						$this->oApiFileCache->putFile($sUUID, $sSavedName, $UploadData['tmp_name']);
					}
					else
					{
						$this->oApiFileCache->moveUploadedFile($sUUID, $sSavedName, $UploadData['tmp_name']);
					}
					if ($this->oApiFileCache->isFileExists($sUUID, $sSavedName))
					{
						$sSavedFullName = $this->oApiFileCache->generateFullFilePath($sUUID, $sSavedName);
						$this->oApiMailManager->appendMessageFromFile($oAccount, $sSavedFullName, $Folder);
						$mResult = true;
					} 
					else 
					{
						$sError = 'unknown';
					}
				}
				else
				{
					throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::IncorrectFileExtension);
				}
			}
		}
		else
		{
			$sError = 'auth';
		}

		if (0 < strlen($sError))
		{
			$mResult = array(
				'Error' => $sError
			);
		}
		
		return $mResult;
	}
	/**
	 * This method will trigger some event, subscribers of which perform all change password process
	 * 
	 * @param int $AccountId
	 * @param string $CurrentPassword
	 * @param string $NewPassword
	 * @return boolean
	 */
	public function ChangePassword($AccountId, $CurrentPassword, $NewPassword)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		return $mResult;
	}
	
	public function GetFilters($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->oApiAccountsManager->getAccountById((int) $AccountID);
		
		if ($oAccount)
		{
			$mResult = $this->oApiSieveManager->getSieveFilters($oAccount);
		}
		
		return $mResult;
	}
	
	public function UpdateFilters($AccountID, $Filters)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->oApiAccountsManager->getAccountById((int) $AccountID);

		if ($oAccount)
		{
			$aFilters = array();
			
			if (is_array($Filters))
			{
				foreach ($Filters as $aFilterData)
				{
					$oFilter = $this->oApiSieveManager->createFilterInstance($oAccount, $aFilterData);
						
					if ($oFilter)
					{
						$aFilters[] = $oFilter;
					}
				}
			}
			
			$mResult = $this->oApiSieveManager->updateSieveFilters($oAccount, $aFilters);
		}
		
		return $mResult;
	}
	
	public function GetForward($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->oApiAccountsManager->getAccountById((int) $AccountID);
		
		if ($oAccount)
		{
			$mResult = $this->oApiSieveManager->getForward($oAccount);
		}

		return $mResult;
	}
	
	public function UpdateForward($AccountID, $Enable = "0", $Email = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->oApiAccountsManager->getAccountById((int) $AccountID);

		if ($oAccount && $Email !== "")
		{
			$mResult = $this->oApiSieveManager->setForward($oAccount, $Email, !!$Enable);
		}
		
		return $mResult;
	}
	
	public function GetAutoresponder($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->oApiAccountsManager->getAccountById((int) $AccountID);
		
		if ($oAccount)
		{
			$mResult = $this->oApiSieveManager->getAutoresponder($oAccount);
		}

		return $mResult;
	}
	
	public function UpdateAutoresponder($AccountID, $Enable = "0", $Subject = "", $Message = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->oApiAccountsManager->getAccountById((int) $AccountID);

		if ($oAccount && ($Subject !== "" || $Message !== ""))
		{
			$mResult = $this->oApiSieveManager->setAutoresponder($oAccount, $Subject, $Message, !!$Enable);
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
	 */
	public function getRaw($sHash, $sAction = '')
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
		
		$aValues = \Aurora\System\Api::DecodeKeyValues($sHash);
		
		$sFolder = '';
		$iUid = 0;
		$sMimeIndex = '';

		$oAccount = null;

		$iUserId = (isset($aValues['UserId'])) ? $aValues['UserId'] : 0;
		$sUUID = \Aurora\System\Api::getUserUUIDById($iUserId);

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
		
		$bCache = true;
		if ($bCache && 0 < \strlen($sFolder) && 0 < $iUid)
		{
			$this->verifyCacheByKey($sHash);
		}
		
		return $this->oApiMailManager->directMessageToStream($oAccount,
			function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($self, $sUUID, $sHash, $bCache, $sContentTypeIn, $sFileNameIn, $bThumbnail, $bDownload) {
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

					$sFileNameOut = \Aurora\System\Utils::clearFileName($sFileNameOut, $sContentType, $sMimeIndex);

					if ($bCache)
					{
						$self->cacheByKey($sHash);
					}

					\Aurora\System\Utils::OutputFileResource($sUUID, $sContentType, $sFileName, $rResource, $bThumbnail, $bDownload);
				}
			}, $sFolder, $iUid, $sMimeIndex);
	}	
	/***** private functions *****/
}
