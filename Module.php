<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	/* 
	 * @var $oApiMailManager Managers\Main
	 */	
	protected $oApiMailManager = null;

	/* 
	 * @var $oApiAccountsManager Managers\Accounts
	 */	
	protected $oApiAccountsManager = null;

	/* 
	 * @var $oApiServersManager Managers\Servers
	 */	
	protected $oApiServersManager = null;
	
	/* 
	 * @var $oApiIdentitiesManager Managers\Identities
	 */	
	protected $oApiIdentitiesManager = null;

	/* 
	 * @var $oApiSieveManager Managers\Sieve
	 */	
	protected $oApiSieveManager = null;
	
	/* 
	 * @var $oApiFilecacheManager \Aurora\System\Managers\Filecache 
	 */	
	protected $oApiFilecacheManager = null;
	
	/**
	 * Initializes Mail Module.
	 * 
	 * @ignore
	 */
	public function init() 
	{
		$this->aErrors = [
			Enums\ErrorCodes::CannotConnectToMailServer				=> $this->i18N('ERROR_CONNECT_TO_MAIL_SERVER'),
			Enums\ErrorCodes::CannotLoginCredentialsIncorrect		=> $this->i18N('ERROR_CREDENTIALS_INCORRECT'),
			Enums\ErrorCodes::FolderAlreadyExists					=> $this->i18N('ERROR_FOLDER_EXISTS'),
			Enums\ErrorCodes::FolderNameContainsDelimiter			=> $this->i18N('ERROR_FOLDER_NAME_CONTAINS_DELIMITER'),
			Enums\ErrorCodes::CannotRenameNonExistenFolder			=> $this->i18N('ERROR_RENAME_NONEXISTEN_FOLDER'),
			Enums\ErrorCodes::CannotGetMessage						=> $this->i18N('ERROR_GET_MESSAGE'),
			Enums\ErrorCodes::CannotMoveMessage						=> $this->i18N('ERROR_MOVE_MESSAGE'),
			Enums\ErrorCodes::CannotSendMessageInvalidRecipients	=> $this->i18N('ERROR_SEND_MESSAGE_INVALID_RECIPIENTS'),
			Enums\ErrorCodes::CannotSendMessageToRecipients			=> $this->i18N('ERROR_SEND_MESSAGE_TO_RECIPIENTS'),
			Enums\ErrorCodes::CannotSendMessageToExternalRecipients	=> $this->i18N('ERROR_SEND_MESSAGE_TO_EXTERNAL_RECIPIENTS'),
			Enums\ErrorCodes::CannotSaveMessageToSentItems			=> $this->i18N('ERROR_SEND_MESSAGE_NOT_SAVED'),
			Enums\ErrorCodes::CannotUploadMessage					=> $this->i18N('ERROR_UPLOAD_MESSAGE'),
			Enums\ErrorCodes::CannotUploadMessageFileNotEml			=> $this->i18N('ERROR_UPLOAD_MESSAGE_FILE_NOT_EML'),
			Enums\ErrorCodes::DomainIsNotAllowedForLoggingIn		=> $this->i18N('DOMAIN_IS_NOT_ALLOWED_FOR_LOGGING_IN'),
		];
		
		\Aurora\Modules\Core\Classes\User::extend(
			self::GetName(),
			[
				'AllowAutosaveInDrafts'	=> ['bool', (bool) $this->getConfig('AllowAutosaveInDrafts', false)],
			]
		);		

		$this->AddEntries(array(
				'message-newtab' => 'EntryMessageNewtab',
				'mail-attachment' => 'EntryDownloadAttachment'
			)
		);
		
		$this->subscribeEvent('Login', array($this, 'onLogin'));
		$this->subscribeEvent('Core::AfterDeleteUser', array($this, 'onAfterDeleteUser'));
		$this->subscribeEvent('Core::GetAccounts', array($this, 'onGetAccounts'));
		$this->subscribeEvent('Autodiscover::GetAutodiscover::after', array($this, 'onAfterGetAutodiscover'));
		$this->subscribeEvent('Core::DeleteTenant::after', array($this, 'onAfterDeleteTenant'));

		\MailSo\Config::$PreferStartTlsIfAutoDetect = !!$this->getConfig('PreferStarttls', true);
	}

	public function getAccountsManager()
	{
		if ($this->oApiAccountsManager === null)
		{
			$this->oApiAccountsManager = new Managers\Accounts\Manager($this);
		}

		return $this->oApiAccountsManager;
	}

	public function setAccountsManager($oManager)
	{
		$this->oApiAccountsManager = $oManager;
	}

	public function getServersManager()
	{
		if ($this->oApiServersManager === null)
		{
			$this->oApiServersManager = new Managers\Servers\Manager($this);
		}

		return $this->oApiServersManager;
	}
	
	public function getIdentitiesManager()
	{
		if ($this->oApiIdentitiesManager === null)
		{
			$this->oApiIdentitiesManager = new Managers\Identities\Manager($this);
		}

		return $this->oApiIdentitiesManager;
	}

	public function getMailManager()
	{
		if ($this->oApiMailManager === null)
		{
			$this->oApiMailManager = new Managers\Main\Manager($this);
		}

		return $this->oApiMailManager;
	}

	public function getSieveManager()
	{
		if ($this->oApiSieveManager === null)
		{
			$this->oApiSieveManager = new Managers\Sieve\Manager($this);
		}

		return $this->oApiSieveManager;
	}	

	public function getFilecacheManager()
	{
		if ($this->oApiFilecacheManager === null)
		{
			$this->oApiFilecacheManager = new \Aurora\System\Managers\Filecache();
		}

		return $this->oApiFilecacheManager;
	}

	/***** public functions might be called with web API *****/
	/**
	 * @apiDefine Mail Mail Module
	 * Main Mail module. It provides PHP and Web APIs for managing mail accounts, folders and messages.
	 */
	
	/**
	 * @api {post} ?/Api/ GetSettings
	 * @apiName GetSettings
	 * @apiGroup Mail
	 * @apiDescription Obtains list of module settings for authenticated user.
	 * 
	 * @apiHeader {string} [Authorization] "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetSettings} Method Method name
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetSettings'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List of module settings in case of success, otherwise **false**.
	 * 
	 * @apiSuccess {array} Result.Result.Accounts="[]" List of accounts.
	 * @apiSuccess {boolean} Result.Result.AllowAddAccounts=false Indicates if adding of new account is allowed.
	 * @apiSuccess {boolean} Result.Result.AllowAutosaveInDrafts=false Indicates if autosave in Drafts folder on compose is allowed.
	 * @apiSuccess {boolean} Result.Result.AllowDefaultAccountForUser=false Indicates if default account is allowed.
	 * @apiSuccess {boolean} Result.Result.AllowIdentities=false Indicates if identities are allowed.
	 * @apiSuccess {boolean} Result.Result.AllowFilters=false Indicates if filters are allowed.
	 * @apiSuccess {boolean} Result.Result.AllowForward=false Indicates if forward is allowed.
	 * @apiSuccess {boolean} Result.Result.AllowAutoresponder=false Indicates if autoresponder is allowed.
	 * @apiSuccess {boolean} Result.Result.AllowInsertImage=false Indicates if insert of images in composed message body is allowed.
	 * @apiSuccess {int} Result.Result.AutoSaveIntervalSeconds=60 Interval for autosave of message on compose in seconds.
	 * @apiSuccess {int} Result.Result.ImageUploadSizeLimit=0 Max size of upload image in message text in bytes.
	 * 
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetSettings',
	 *	Result: { Accounts: [], AllowAddAccounts: true, AllowAutosaveInDrafts: true,
	 * AllowDefaultAccountForUser: true, AllowIdentities: true,
	 * AllowFilters: false, AllowForward: false, AllowAutoresponder: false, AllowInsertImage: true,
	 * AutoSaveIntervalSeconds: 60, ImageUploadSizeLimit: 0 }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetSettings',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains list of module settings for authenticated user.
	 * @return array
	 */
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
		$aSettings = array(
			'Accounts' => array(),
			'AllowAddAccounts' => $this->getConfig('AllowAddAccounts', false),
			'AllowAutosaveInDrafts' => (bool)$this->getConfig('AllowAutosaveInDrafts', false),
			'AllowDefaultAccountForUser' => $this->getConfig('AllowDefaultAccountForUser', false),
			'AllowIdentities' => $this->getConfig('AllowIdentities', false),
			'AllowFilters' => $this->getConfig('AllowFilters', false),
			'AllowForward' => $this->getConfig('AllowForward', false),
			'AllowAutoresponder' => $this->getConfig('AllowAutoresponder', false),
			'AllowInsertImage' => $this->getConfig('AllowInsertImage', false),
			'AutoSaveIntervalSeconds' => $this->getConfig('AutoSaveIntervalSeconds', 60),
			'AllowTemplateFolders' => $this->getConfig('AllowTemplateFolders', false),
			'IgnoreImapSubscription' => $this->getConfig('IgnoreImapSubscription', false),
			'ImageUploadSizeLimit' => $this->getConfig('ImageUploadSizeLimit', 0),
			'SmtpAuthType' => (new \Aurora\Modules\Mail\Enums\SmtpAuthType)->getMap(),
		);
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser && $oUser->Role === \Aurora\System\Enums\UserRole::NormalUser)
		{
			$aAcc = $this->GetAccounts($oUser->EntityId);
			$aResponseAcc = [];
			foreach($aAcc as $oAccount)
			{
				$aResponseAcc[] = $oAccount->toResponseArray();
			}
			$aSettings['Accounts'] = $aResponseAcc;
			
			if (isset($oUser->{self::GetName().'::AllowAutosaveInDrafts'}))
			{
				$aSettings['AllowAutosaveInDrafts'] = $oUser->{self::GetName().'::AllowAutosaveInDrafts'};
			}
		}
		
		return $aSettings;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateSettings
	 * @apiName UpdateSettings
	 * @apiGroup Mail
	 * @apiDescription Updates module's per user settings.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Files} Module Module name
	 * @apiParam {string=UpdateSettings} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AllowAutosaveInDrafts** *boolean* Indicates if message should be saved automatically while compose.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateSettings',
	 *	Parameters: '{ AllowAutosaveInDrafts: false }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name
	 * @apiSuccess {string} Result.Method Method name
	 * @apiSuccess {boolean} Result.Result Indicates if settings were updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateSettings',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Files',
	 *	Method: 'UpdateSettings',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates module's per user settings.
	 * @param boolean $AllowAutosaveInDrafts Indicates if message should be saved automatically while compose.
	 * @return boolean
	 */
	public function UpdateSettings($AllowAutosaveInDrafts)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser)
		{
			if ($oUser->Role === \Aurora\System\Enums\UserRole::NormalUser)
			{
				$oCoreDecorator = \Aurora\Modules\Core\Module::Decorator();
				$oUser->{self::GetName().'::AllowAutosaveInDrafts'} = $AllowAutosaveInDrafts;
				return $oCoreDecorator->UpdateUserObject($oUser);
			}
			if ($oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin)
			{
				$this->setConfig('AllowAutosaveInDrafts', $AllowAutosaveInDrafts);
				return $this->saveModuleConfig();
			}
		}
		
		return false;
	}
	
	/**
	 * @api {post} ?/Api/ GetAccounts
	 * @apiName GetAccounts
	 * @apiGroup Mail
	 * @apiDescription Obtains list of mail accounts for user.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetAccounts} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **UserId** *int* (optional) User identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAccounts'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List of mail accounts in case of success, otherwise **false**. Description of account properties are placed in GetAccount method description.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAccounts',
	 *	Result: [ { "AccountID": 12, "UUID": "uuid_value", "UseToAuthorize": true, "Email": "test@email", 
	 * "FriendlyName": "", "IncomingLogin": "test@email", "UseSignature": false, "Signature": "", 
	 * "ServerId": 10, "Server": { "EntityId": 10, "UUID": "uuid_value", "TenantId": 0, "Name": "Mail server", 
	 * "IncomingServer": "mail.email", "IncomingPort": 143, "IncomingUseSsl": false, "OutgoingServer": "mail.email", 
	 * "OutgoingPort": 25, "OutgoingUseSsl": false, "SmtpAuthType": "0", "OwnerType": "superadmin", 
	 * "Domains": "", "ServerId": 10 }, "CanBeUsedToAuthorize": true, "UseThreading": true } ]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAccounts',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains list of mail accounts for user.
	 * @param int $UserId User identifier.
	 * @return array|boolean
	 */
	public function GetAccounts($UserId)
	{
		$mResult = false;
		
		$oAuthenticatedUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oAuthenticatedUser && $oAuthenticatedUser->EntityId === $UserId)
		{
			// If $UserId is equal to identifier of authenticated user, it is a situation when normal user is logged in.
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		}
		else
		{
			// Otherwise it is a super administrator or some code that is executed after \Aurora\System\Api::skipCheckUserRole method was called.
			// If it is a second case user identifier shouldn't be checked. It could be even an anonymous user.
			// There is no $oAuthenticatedUser for anonymous user.
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		}
		
		$aAccounts = $this->getAccountsManager()->getUserAccounts($UserId);
		if (is_array($aAccounts))
		{
			$mResult = [];
			foreach ($aAccounts as $oAccount)
			{
				if ($oAuthenticatedUser && $oAccount->IncomingLogin === $oAuthenticatedUser->PublicId)
				{
					array_unshift($mResult, $oAccount);
				}
				else
				{
					$mResult[] = $oAccount;
				}
			}
		}
		
		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ GetAccount
	 * @apiName GetAccount
	 * @apiGroup Mail
	 * @apiDescription Obtains mail account with specified identifier.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetAccount} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountId** *int* Identifier of mail account to obtain.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAccount',
	 *	Parameters: '{ "AccountId": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Mail account properties in case of success, otherwise **false**.
	 * @apiSuccess {int} Result.Result.AccountID Account identifier.
	 * @apiSuccess {string} Result.Result.UUID Account UUID.
	 * @apiSuccess {boolean} Result.Result.UseToAuthorize Indicates if account is used for authentication.
	 * @apiSuccess {string} Result.Result.Email Account email.
	 * @apiSuccess {string} Result.Result.FriendlyName Account friendly name.
	 * @apiSuccess {string} Result.Result.IncomingLogin Login for connection to IMAP server.
	 * @apiSuccess {boolean} Result.Result.UseSignature Indicates if signature should be used in outgoing messages.
	 * @apiSuccess {string} Result.Result.Signature Signature in outgoing messages.
	 * @apiSuccess {int} Result.Result.ServerId Server identifier.
	 * @apiSuccess {object} Result.Result.Server Server properties that are used for connection to IMAP and SMTP servers.
	 * @apiSuccess {boolean} Result.Result.CanBeUsedToAuthorize Indicates if account can be used for authentication. It is forbidden to use account for authentication if another user has account with the same credentials and it is allowed to authenticate.
	 * @apiSuccess {boolean} Result.Result.UseThreading Indicates if account uses mail threading.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAccount',
	 *	Result: { "AccountID": 12, "UUID": "uuid_value", "UseToAuthorize": true, "Email": "test@email", 
	 * "FriendlyName": "", "IncomingLogin": "test@email", "UseSignature": false, "Signature": "", 
	 * "ServerId": 10, "Server": { "EntityId": 10, "UUID": "uuid_value", "TenantId": 0, "Name": "Mail server", 
	 * "IncomingServer": "mail.email", "IncomingPort": 143, "IncomingUseSsl": false, "OutgoingServer": "mail.email", 
	 * "OutgoingPort": 25, "OutgoingUseSsl": false, "SmtpAuthType": "0", "OwnerType": "superadmin", 
	 * "Domains": "", "ServerId": 10 }, "CanBeUsedToAuthorize": true, "UseThreading": true }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains mail account with specified identifier.
	 * @param int $AccountId Identifier of mail account to obtain.
	 * @return \Aurora\Modules\Mail\Classes\Account|boolean
	 */
	public function GetAccount($AccountId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		$oAccount = $this->getAccountsManager()->getAccountById($AccountId);
		
		if ($oAccount && ($oAccount->IdUser === $oUser->EntityId || $oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin))
		{
			$mResult = $oAccount;
		}
				
		return $mResult;
	}
	
	public function GetAccountByEmail($Email)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		$oAccount = $this->getAccountsManager()->getAccountByEmail($Email);
		
		if ($oAccount && ($oAccount->IdUser === $oUser->EntityId || $oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin))
		{
			$mResult = $oAccount;
		}
				
		return $mResult;
	}	
	
	/**
	 * @api {post} ?/Api/ CreateAccount
	 * @apiName CreateAccount
	 * @apiGroup Mail
	 * @apiDescription Creates mail account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=CreateAccount} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **UserId** *int* (optional) User identifier.<br>
	 * &emsp; **FriendlyName** *string* (optional) Friendly name.<br>
	 * &emsp; **Email** *string* Email.<br>
	 * &emsp; **IncomingLogin** *string* Login for IMAP connection.<br>
	 * &emsp; **IncomingPassword** *string* Password for IMAP connection.<br>
	 * &emsp; **Server** *object* List of settings for IMAP and SMTP connections.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateAccount',
	 *	Parameters: '{ "Email": "test@email", "IncomingLogin": "test@email", "IncomingPassword": "pass_value",
	 *				"Server": { "ServerId": 10 } }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Mail account properties in case of success, otherwise **false**.
	 * @apiSuccess {int} Result.Result.AccountID Created account identifier.
	 * @apiSuccess {string} Result.Result.UUID Created account UUID.
	 * @apiSuccess {boolean} Result.Result.UseToAuthorize Indicates if account is used for authentication.
	 * @apiSuccess {string} Result.Result.Email Account email.
	 * @apiSuccess {string} Result.Result.FriendlyName Account friendly name.
	 * @apiSuccess {string} Result.Result.IncomingLogin Login for connection to IMAP server.
	 * @apiSuccess {boolean} Result.Result.UseSignature Indicates if signature should be used in outgoing messages.
	 * @apiSuccess {string} Result.Result.Signature Signature in outgoing messages.
	 * @apiSuccess {int} Result.Result.ServerId Server identifier.
	 * @apiSuccess {object} Result.Result.Server Server properties that are used for connection to IMAP and SMTP servers.
	 * @apiSuccess {boolean} Result.Result.CanBeUsedToAuthorize Indicates if account can be used for authentication. It is forbidden to use account for authentication if another user has account with the same credentials and it is allowed to authenticate.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateAccount',
	 *	Result: { "AccountID": 12, "UUID": "uuid_value", "UseToAuthorize": true, "Email": "test@email",
	 * "FriendlyName": "", "IncomingLogin": "test@email", "UseSignature": false, "Signature": "",
	 * "ServerId": 10, "Server": { "ServerId": 10, "Name": "Mail server", "IncomingServer": "mail.server",
	 * "IncomingPort": 143, "IncomingUseSsl": false, "OutgoingServer": "mail.server", "OutgoingPort": 25,
	 * "OutgoingUseSsl": false, "SmtpAuthType": "0", "Domains": "" }, "CanBeUsedToAuthorize": true }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Creates mail account.
	 * @param int $UserId User identifier.
	 * @param string $FriendlyName Friendly name.
	 * @param string $Email Email.
	 * @param string $IncomingLogin Login for IMAP connection.
	 * @param string $IncomingPassword Password for IMAP connection.
	 * @param array $Server List of settings for IMAP and SMTP connections.
	 * @return \Aurora\Modules\Mail\Classes\Account|boolean
	 */
	public function CreateAccount($UserId = 0, $FriendlyName = '', $Email = '', $IncomingLogin = '', 
			$IncomingPassword = '', $Server = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		$bPrevState = \Aurora\System\Api::skipCheckUserRole(true);
		$oAccount = $this->GetAccountByEmail($Email);
		\Aurora\System\Api::skipCheckUserRole($bPrevState);
		$bDoImapLoginOnAccountCreate = $this->getConfig('DoImapLoginOnAccountCreate', true);
		if (!$oAccount)
		{
			$sDomain = preg_match('/.+@(.+)$/',  $Email, $aMatches) && $aMatches[1] ? $aMatches[1] : '';
			
			if ($Email)
			{
				$bCustomServerCreated = false;
				$iServerId = $Server['ServerId'];
				if ($Server !== null && $iServerId === 0)
				{
					$oNewServer = new \Aurora\Modules\Mail\Classes\Server(self::GetName());
					$oNewServer->Name = $Server['IncomingServer'];
					$oNewServer->IncomingServer = $Server['IncomingServer'];
					$oNewServer->IncomingPort = $Server['IncomingPort'];
					$oNewServer->IncomingUseSsl = $Server['IncomingUseSsl'];
					$oNewServer->OutgoingServer = $Server['OutgoingServer'];
					$oNewServer->OutgoingPort = $Server['OutgoingPort'];
					$oNewServer->OutgoingUseSsl = $Server['OutgoingUseSsl'];
					$oNewServer->SmtpAuthType = $Server['SmtpAuthType'];
					$oNewServer->Domains = $sDomain;
					$oNewServer->EnableThreading = $Server['EnableThreading'];
					$iServerId = $this->getServersManager()->createServer($oNewServer);
					$bCustomServerCreated = true;
				}
				
				if ($Server === null)
				{
					$oServer = $this->getServersManager()->getServerByDomain($sDomain);
					if ($oServer)
					{
						$iServerId = $oServer->EntityId;
					}
				}

				$oAccount = new \Aurora\Modules\Mail\Classes\Account(self::GetName());

				$oAccount->IdUser = $UserId;
				$oAccount->FriendlyName = $FriendlyName;
				$oAccount->Email = $Email;
				$oAccount->IncomingLogin = $IncomingLogin;
				$oAccount->setPassword($IncomingPassword);
				$oAccount->ServerId = $iServerId;
				$oServer = $oAccount->getServer();
				if ($oServer)
				{
					$oAccount->UseThreading = $oServer->EnableThreading;
				}

				$bAccoutResult = false;
				if ($bDoImapLoginOnAccountCreate)
				{
					$oResException = $this->getMailManager()->validateAccountConnection($oAccount, false);
				}
				if ($oResException === null)
				{
					$oCoreDecorator = \Aurora\Modules\Core\Module::Decorator();
					$oUser = $oCoreDecorator ? $oCoreDecorator->GetUser($UserId) : null;
					if ($oUser instanceof \Aurora\Modules\Core\Classes\User && $oUser->PublicId === $Email && 
						!$this->getAccountsManager()->useToAuthorizeAccountExists($Email))
					{
						$oAccount->UseToAuthorize = true;
					}
					$bAccoutResult = $this->getAccountsManager()->createAccount($oAccount);
				}

				if ($bAccoutResult)
				{
					return $oAccount;
				}
				else if ($bCustomServerCreated)
				{
					$this->getServersManager()->deleteServer($iServerId);
				}
				
				if ($oResException !== null)
				{
					throw $oResException;
				}
			}
		}
		return false;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateAccount
	 * @apiName UpdateAccount
	 * @apiGroup Mail
	 * @apiDescription Updates mail account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateAccount} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Identifier of account to update.<br>
	 * &emsp; **FriendlyName** *string* New friendly name.<br>
	 * &emsp; **Email** *string* New email.<br>
	 * &emsp; **IncomingLogin** *string* New loging for IMAP connection.<br>
	 * &emsp; **IncomingPassword** *string* New password for IMAP connection.<br>
	 * &emsp; **Server** *object* List of settings for IMAP and SMTP connections.<br>
	 * &emsp; **UseThreading** *boolean* Indicates if account uses mail threading.<br>
	 * &emsp; **SaveRepliesToCurrFolder** *boolean* Indicates if replies should be saved to current folder (not Sent Items).<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateAccount',
	 *	Parameters: '{ "Email": "test@email", "IncomingLogin": "test@email",
	 *		"IncomingPassword": "pass_value", "Server": { "ServerId": 10 }, "UseThreading": true,
	 *		"SaveRepliesToCurrFolder": false }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if account was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateAccount',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates mail account.
	 * @param int $AccountID Identifier of account to update.
	 * @param boolean $UseToAuthorize Indicates if account can be used to authorize user.
	 * @param string $Email New email.
	 * @param string $FriendlyName New friendly name.
	 * @param string $IncomingLogin New login for IMAP connection.
	 * @param string $IncomingPassword New password for IMAP connection.
	 * @param array $Server List of settings for IMAP and SMTP connections.
	 * @param boolean $UseThreading Indicates if account uses mail threading.
	 * @param boolean $SaveRepliesToCurrFolder Indicates if replies should be saved to current folder (not Sent Items)
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function UpdateAccount($AccountID, $UseToAuthorize = null, $Email = null, $FriendlyName = null, $IncomingLogin = null, 
			$IncomingPassword = null, $Server = null, $UseThreading = null, $SaveRepliesToCurrFolder = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($AccountID > 0)
		{
			$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
			
			if ($oAccount)
			{
				if (!empty($Email))
				{
					$oAccount->Email = $Email;
				}
				if ($UseToAuthorize === false || $UseToAuthorize === true && !$this->getAccountsManager()->useToAuthorizeAccountExists($oAccount->Email, $oAccount->EntityId))
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
					$oAccount->setPassword($IncomingPassword);
				}
				if ($Server !== null)
				{
					if ($Server['ServerId'] === 0)
					{
						$sDomains = explode('@', $oAccount->Email)[1];
						$oNewServer = new \Aurora\Modules\Mail\Classes\Server(self::GetName());
						$oNewServer->Name = $Server['IncomingServer'];
						$oNewServer->IncomingServer = $Server['IncomingServer'];
						$oNewServer->IncomingPort = $Server['IncomingPort'];
						$oNewServer->IncomingUseSsl = $Server['IncomingUseSsl'];
						$oNewServer->OutgoingServer = $Server['OutgoingServer'];
						$oNewServer->OutgoingPort = $Server['OutgoingPort'];
						$oNewServer->OutgoingUseSsl = $Server['OutgoingUseSsl'];
						$oNewServer->SmtpAuthType = $Server['SmtpAuthType'];
						$oNewServer->Domains = $sDomains;
						$oNewServer->EnableThreading = $Server['EnableThreading'];
						$iNewServerId = $this->getServersManager()->createServer($oNewServer);
						$oAccount->updateServer($iNewServerId);
					}
					elseif ($oAccount->ServerId === $Server['ServerId'])
					{
						$oAccServer = $oAccount->getServer();
						if ($oAccServer && $oAccServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::Account)
						{
							$oAccServer->Name = $Server['IncomingServer'];
							$oAccServer->IncomingServer = $Server['IncomingServer'];
							$oAccServer->IncomingPort = $Server['IncomingPort'];
							$oAccServer->IncomingUseSsl = $Server['IncomingUseSsl'];
							$oAccServer->OutgoingServer = $Server['OutgoingServer'];
							$oAccServer->OutgoingPort = $Server['OutgoingPort'];
							$oAccServer->OutgoingUseSsl = $Server['OutgoingUseSsl'];
							$oAccServer->SmtpAuthType = $Server['SmtpAuthType'];
							
							$this->getServersManager()->updateServer($oAccServer);		
						}
					}
					else
					{
						$oAccServer = $oAccount->getServer();
						if ($oAccServer && $oAccServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::Account)
						{
							$this->getServersManager()->deleteServer($oAccServer->EntityId);
						}
						$oAccount->updateServer($Server['ServerId']);
					}
				}
				
				if ($UseThreading !== null)
				{
					$oAccount->UseThreading = $UseThreading;
				}
				
				if ($SaveRepliesToCurrFolder !== null)
				{
					$oAccount->SaveRepliesToCurrFolder = $SaveRepliesToCurrFolder;
				}
				
				if ($this->getAccountsManager()->updateAccount($oAccount))
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
	 * @api {post} ?/Api/ DeleteAccount
	 * @apiName DeleteAccount
	 * @apiGroup Mail
	 * @apiDescription Deletes mail account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=DeleteAccount} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Identifier of account to delete.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteAccount',
	 *	Parameters: '{ "AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if account was deleted successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteAccount',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Deletes mail account.
	 * @param int $AccountID Account identifier.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function DeleteAccount($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$bResult = false;

		if ($AccountID > 0)
		{
			$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
			
			if ($oAccount)
			{
				$this->getIdentitiesManager()->deleteAccountIdentities($oAccount->EntityId);
				$this->getMailManager()->deleteSystemFolderNames($oAccount->EntityId);
				$bServerRemoved = true;
				$oServer = $oAccount->getServer();
				if ($oServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::Account)
				{
					$bServerRemoved = $this->getServersManager()->deleteServer($oServer->EntityId);
				}
				if ($bServerRemoved)
				{
					$bResult = $this->getAccountsManager()->deleteAccount($oAccount);
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
	 * @api {post} ?/Api/ GetServers
	 * @apiName GetServers
	 * @apiGroup Mail
	 * @apiDescription Obtains list of servers wich contains settings for IMAP and SMTP connections.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetServers} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **TenantId** *int* (optional) Identifier of tenant which contains servers to return. If TenantId is 0 returns server which are belonged to SuperAdmin, not Tenant.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetServers'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List of mail servers in case of success, otherwise **false**. Description of server properties are placed in GetServer method description.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetServers',
	 *	Result: [ { "EntityId": 10, "UUID": "uuid_value", "TenantId": 0, "Name": "Mail server", 
	 * "IncomingServer": "mail.email", "IncomingPort": 143, "IncomingUseSsl": false, "OutgoingServer": "mail.email", 
	 * "OutgoingPort": 25, "OutgoingUseSsl": false, "SmtpAuthType": "0", "OwnerType": "superadmin", 
	 * "Domains": "", "ServerId": 10 } ]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetServers',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains list of servers wich contains settings for IMAP and SMTP connections.
	 * @param int $TenantId Identifier of tenant which contains servers to return. If $TenantId is 0 returns server which are belonged to SuperAdmin, not Tenant.
	 * @return array
	 */
	public function GetServers($TenantId = 0)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->getServersManager()->getServerList($TenantId);
	}
	
	/**
	 * @api {post} ?/Api/ GetServer
	 * @apiName GetServer
	 * @apiGroup Mail
	 * @apiDescription Obtains server with specified server identifier.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetServer} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **ServerId** *int* Server identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetServer',
	 *	Parameters: '{ "ServerId": 10 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Mail server properties in case of success, otherwise **false**.
	 * @apiSuccess {int} Result.Result.ServerId Server identifier.
	 * @apiSuccess {string} Result.Result.UUID Server UUID.
	 * @apiSuccess {int} Result.Result.TenantId Tenant identifier.
	 * @apiSuccess {string} Result.Result.Name Server name.
	 * @apiSuccess {string} Result.Result.IncomingServer IMAP server.
	 * @apiSuccess {int} Result.Result.IncomingPort IMAP port.
	 * @apiSuccess {boolean} Result.Result.IncomingUseSsl Indicates if SSL should be used for IMAP connection.
	 * @apiSuccess {string} Result.Result.OutgoingServer SMTP server.
	 * @apiSuccess {int} Result.Result.OutgoingPort SMTP port.
	 * @apiSuccess {boolean} Result.Result.OutgoingUseSsl Indicates if SSL should be used for SMTP connection.
	 * @apiSuccess {string} Result.Result.SmtpAuthType SMTP authentication type: '0' - no authentication, '1' - specified credentials, '2' - user credentials
	 * @apiSuccess {string} Result.Result.SmtpLogin
	 * @apiSuccess {string} Result.Result.SmtpPassword
	 * @apiSuccess {string} Result.Result.OwnerType Owner type: 'superadmin' - server was created by SuperAdmin user, 'tenant' - server was created by TenantAdmin user, 'account' - server was created when account was created and any existent server was chosen.
	 * @apiSuccess {string} Result.Result.Domains List of server domain separated by comma.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetServer',
	 *	Result: { "ServerId": 10, "UUID": "uuid_value", "TenantId": 0, "Name": "Mail server", 
	 * "IncomingServer": "mail.email", "IncomingPort": 143, "IncomingUseSsl": false, "OutgoingServer": "mail.email", 
	 * "OutgoingPort": 25, "OutgoingUseSsl": false, "SmtpAuthType": "0", "OwnerType": "superadmin", 
	 * "Domains": "" }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetServer',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains server with specified server identifier.
	 * @param int $ServerId Server identifier.
	 * @return \Aurora\Modules\Mail\Classes\Server|boolean
	 */
	public function GetServer($ServerId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->getServersManager()->getServer($ServerId);
	}
	
	/**
	 * @api {post} ?/Api/ CreateServer
	 * @apiName CreateServer
	 * @apiGroup Mail
	 * @apiDescription Creates mail server.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=CreateServer} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **Name** *string* Server name.<br>
	 * &emsp; **IncomingServer** *string* IMAP server.<br>
	 * &emsp; **IncomingPort** *int* Port for connection to IMAP server.<br>
	 * &emsp; **IncomingUseSsl** *boolean* Indicates if it is necessary to use SSL while connecting to IMAP server.<br>
	 * &emsp; **OutgoingServer** *string* SMTP server.<br>
	 * &emsp; **OutgoingPort** *int* Port for connection to SMTP server.<br>
	 * &emsp; **OutgoingUseSsl** *boolean* Indicates if it is necessary to use SSL while connecting to SMTP server.<br>
	 * &emsp; **SmtpAuthType** *string* SMTP authentication type: '0' - no authentication, '1' - specified credentials, '2' - user credentials.<br>
	 * &emsp; **SmtpLogin** *string* (optional)<br>
	 * &emsp; **SmtpPassword** *string* (optional)<br>
	 * &emsp; **Domains** *string* List of domains separated by comma.<br>
	 * &emsp; **TenantId** *int* (optional) If tenant identifier is specified creates mail server belonged to specified tenant.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateServer',
	 *	Parameters: '{ "Name": "Server name", "IncomingServer": "mail.server", "IncomingPort": 143,
	 *			"IncomingUseSsl": false, "OutgoingServer": "mail.server", "OutgoingPort": 25,
	 *			"OutgoingUseSsl": false, "SmtpAuthType": "0", "Domains": "" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Identifier of created server in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateServer',
	 *	Result: 10
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateServer',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Creates mail server.
	 * @param string $Name Server name.
	 * @param string $IncomingServer IMAP server.
	 * @param int $IncomingPort Port for connection to IMAP server.
	 * @param boolean $IncomingUseSsl Indicates if it is necessary to use SSL while connecting to IMAP server.
	 * @param string $OutgoingServer SMTP server.
	 * @param int $OutgoingPort Port for connection to SMTP server.
	 * @param boolean $OutgoingUseSsl Indicates if it is necessary to use SSL while connecting to SMTP server.
	 * @param string $SmtpAuthType SMTP authentication type: '0' - no authentication, '1' - specified credentials, '2' - user credentials.
	 * @param string $Domains List of domains separated by comma.
	 * @param boolean $EnableThreading
	 * @param boolean $EnableSieve
	 * @param int $SievePort
	 * @param string $SmtpLogin (optional)
	 * @param string $SmtpPassword (optional)
	 * @param boolean $UseFullEmailAddressAsLogin (optional)
	 * @param int $TenantId (optional) If tenant identifier is specified creates mail server belonged to specified tenant.
	 * @return int|boolean
	 */
	public function CreateServer($Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $SmtpAuthType, $Domains, $EnableThreading, $EnableSieve, 
			$SievePort, $SmtpLogin = '', $SmtpPassword = '', $UseFullEmailAddressAsLogin = true, $TenantId = 0)
	{
		$sOwnerType = ($TenantId === 0) ? \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin : \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant;
		
		if ($TenantId === 0)
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::TenantAdmin);
		}
		
		$oServer = new \Aurora\Modules\Mail\Classes\Server(self::GetName());
		$oServer->OwnerType = $sOwnerType;
		$oServer->TenantId = $TenantId;
		$oServer->Name = $Name;
		$oServer->IncomingServer = $IncomingServer;
		$oServer->IncomingPort = $IncomingPort;
		$oServer->IncomingUseSsl = $IncomingUseSsl;
		$oServer->OutgoingServer = $OutgoingServer;
		$oServer->OutgoingPort = $OutgoingPort;
		$oServer->OutgoingUseSsl = $OutgoingUseSsl;
		$oServer->SmtpAuthType = $SmtpAuthType;
		$oServer->SmtpLogin = $SmtpLogin;
		$oServer->SmtpPassword = $SmtpPassword;
		$oServer->Domains = $this->getServersManager()->trimDomains($Domains);
		$oServer->EnableThreading = $EnableThreading;
		$oServer->EnableSieve = $EnableSieve;
		$oServer->SievePort = $SievePort;
		$oServer->UseFullEmailAddressAsLogin = $UseFullEmailAddressAsLogin;
			
		return $this->getServersManager()->createServer($oServer);
	}
	
	/**
	 * @api {post} ?/Api/ UpdateServer
	 * @apiName UpdateServer
	 * @apiGroup Mail
	 * @apiDescription Updates mail server with specified identifier.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateServer} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **ServerId** *int* Server identifier.<br>
	 * &emsp; **Name** *string* New server name.<br>
	 * &emsp; **IncomingServer** *string* New IMAP server.<br>
	 * &emsp; **IncomingPort** *int* New port for connection to IMAP server.<br>
	 * &emsp; **IncomingUseSsl** *boolean* Indicates if it is necessary to use SSL while connecting to IMAP server.<br>
	 * &emsp; **OutgoingServer** *string* New SMTP server.<br>
	 * &emsp; **OutgoingPort** *int* New port for connection to SMTP server.<br>
	 * &emsp; **OutgoingUseSsl** *boolean* Indicates if it is necessary to use SSL while connecting to SMTP server.<br>
	 * &emsp; **SmtpAuthType** *string* SMTP authentication type: '0' - no authentication, '1' - specified credentials, '2' - user credentials.<br>
	 * &emsp; **SmtpLogin** *string* (optional)<br>
	 * &emsp; **SmtpPassword** *string* (optional)<br>
	 * &emsp; **Domains** *string* New list of domains separated by comma.<br>
	 * &emsp; **TenantId** *int* If tenant identifier is specified creates mail server belonged to specified tenant.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateServer',
	 *	Parameters: '{ "Name": "Server name", "IncomingServer": "mail.server", "IncomingPort": 143,
	 * "IncomingUseSsl": false, "OutgoingServer": "mail.server", "OutgoingPort": 25, "OutgoingUseSsl": false,
	 * "SmtpAuthType": "0", "Domains": "" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if server was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateServer',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateServer',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates mail server with specified identifier.
	 * @param int $ServerId Server identifier.
	 * @param string $Name New server name.
	 * @param string $IncomingServer New IMAP server.
	 * @param int $IncomingPort New port for connection to IMAP server.
	 * @param boolean $IncomingUseSsl Indicates if it is necessary to use SSL while connecting to IMAP server.
	 * @param string $OutgoingServer New SMTP server.
	 * @param int $OutgoingPort New port for connection to SMTP server.
	 * @param boolean $OutgoingUseSsl Indicates if it is necessary to use SSL while connecting to SMTP server.
	 * @param boolean $SmtpAuthType SMTP authentication type: '0' - no authentication, '1' - specified credentials, '2' - user credentials.
	 * @param string $Domains New list of domains separated by comma.
	 * @param boolean $EnableThreading
	 * @param boolean $EnableSieve
	 * @param int $SievePort
	 * @param string $SmtpLogin
	 * @param string $SmtpPassword
	 * @param boolean $UseFullEmailAddressAsLogin
	 * @param int $TenantId If tenant identifier is specified updates mail server belonged to specified tenant.
	 * @param boolean $SetExternalAccessServers
	 * @param string $ExternalAccessImapServer
	 * @param int $ExternalAccessImapPort
	 * @param string $ExternalAccessSmtpServer
	 * @param int $ExternalAccessSmtpPort
	 * @return boolean
	 */
	public function UpdateServer($ServerId, $Name, $IncomingServer, $IncomingPort, $IncomingUseSsl,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $SmtpAuthType, $Domains, $EnableThreading, $EnableSieve, 
			$SievePort, $SmtpLogin = '', $SmtpPassword = '', $UseFullEmailAddressAsLogin = true, $TenantId = 0,
			$SetExternalAccessServers = false, $ExternalAccessImapServer = '', $ExternalAccessImapPort = 143,
			$ExternalAccessSmtpServer = '', $ExternalAccessSmtpPort = 25)
	{
		$bResult = false;
		
		$oServer = $this->getServersManager()->getServer($ServerId);

		if ($oServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin)
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::TenantAdmin);
		}
		
		if ($oServer && ($oServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin || 
				$oServer->OwnerType === \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant && $oServer->TenantId === $TenantId))
		{
			$oServer->Name = $Name;
			$oServer->IncomingServer = $IncomingServer;
			$oServer->IncomingPort = $IncomingPort;
			$oServer->IncomingUseSsl = $IncomingUseSsl;
			$oServer->OutgoingServer = $OutgoingServer;
			$oServer->OutgoingPort = $OutgoingPort;
			$oServer->OutgoingUseSsl = $OutgoingUseSsl;
			$oServer->SmtpAuthType = $SmtpAuthType;
			$oServer->SmtpLogin = $SmtpLogin;
			$oServer->SmtpPassword = $SmtpPassword;
			$oServer->Domains = $this->getServersManager()->trimDomains($Domains);
			$oServer->EnableThreading = $EnableThreading;
			$oServer->EnableSieve = $EnableSieve;
			$oServer->SievePort = $SievePort;
			$oServer->UseFullEmailAddressAsLogin = $UseFullEmailAddressAsLogin;
			$oServer->SetExternalAccessServers = $SetExternalAccessServers;
			if ($oServer->SetExternalAccessServers)
			{
				$oServer->ExternalAccessImapServer = $ExternalAccessImapServer;
				$oServer->ExternalAccessImapPort = $ExternalAccessImapPort;
				$oServer->ExternalAccessSmtpServer = $ExternalAccessSmtpServer;
				$oServer->ExternalAccessSmtpPort = $ExternalAccessSmtpPort;
			}
			
			$bResult = $this->getServersManager()->updateServer($oServer);
		}
		else
		{
			$bResult = false;
		}
		
		return $bResult;
	}
	
	/**
	 * @api {post} ?/Api/ DeleteServer
	 * @apiName DeleteServer
	 * @apiGroup Mail
	 * @apiDescription Deletes mail server.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=DeleteServer} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **ServerId** *int* Identifier of server to delete.<br>
	 * &emsp; **TenantId** *int* (optional) Identifier of tenant that contains mail server.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteServer',
	 *	Parameters: '{ "ServerId": 10 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if server was deleted successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteServer',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteServer',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		}
		else
		{
			\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::TenantAdmin);
		}
		
		return $this->getServersManager()->deleteServer($ServerId, $TenantId);
	}
	
	/**
	 * @api {post} ?/Api/ GetFolders
	 * @apiName GetFolders
	 * @apiGroup Mail
	 * @apiDescription Obtains list of folders for specified account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetFolders} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountId** *int* Identifier of mail account that contains folders to obtain.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetFolders',
	 *	Parameters: '{ "AccountId": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Folder list data in case of success, otherwise **false**.
	 * @apiSuccess {object[]} Result.Result.Folders List of folders.
	 * @apiSuccess {int} Result.Result.Folders.Count Count of folders.
	 * @apiSuccess {object[]} Result.Result.Folders.Collection Collection of folders.
	 * @apiSuccess {int} Result.Result.Folders.Collection.Type Type of folder: 1 - Inbox; 2 - Sent; 3 - Drafts; 4 - Spam; 5 - Trash; 10 - other folders.
	 * @apiSuccess {string} Result.Result.Folders.Collection.Name Name of folder.
	 * @apiSuccess {string} Result.Result.Folders.Collection.FullName Folder full name.
	 * @apiSuccess {string} Result.Result.Folders.Collection.FullNameRaw Folder full name.
	 * @apiSuccess {string} Result.Result.Folders.Collection.FullNameHash Hash of folder full name.
	 * @apiSuccess {string} Result.Result.Folders.Collection.Delimiter Delimiter that is used in folder full name.
	 * @apiSuccess {boolean} Result.Result.Folders.Collection.IsSubscribed Indicates if folder is subscribed.
	 * @apiSuccess {boolean} Result.Result.Folders.Collection.IsSelectable Indicates if folder can be selected.
	 * @apiSuccess {boolean} Result.Result.Folders.Collection.Exists Indicates if folder exists.
	 * @apiSuccess {boolean} Result.Result.Folders.Collection.Extended Indicates if folder is extended.
	 * @apiSuccess {object[]} Result.Result.Folders.Collection.SubFolders List of sub folders.
	 * @apiSuccess {string} Result.Result.Namespace
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetFolders',
	 *	Result: { "Folders": {
	 *		"@Count": 5,
	 *		"@Collection": [
	 *			{	"Type": 1, "Name": "INBOX", "FullName": "INBOX", "FullNameRaw": "INBOX",
	 *				"FullNameHash": "hash_value", "Delimiter": "/", "IsSubscribed": true,
	 *				"IsSelectable": true, "Exists": true, "Extended": null, "SubFolders": null },
	 *			{	"Type": 2, "Name": "Sent", "FullName": "Sent", "FullNameRaw": "Sent",
	 *				"FullNameHash": "hash_value", "Delimiter": "/", "IsSubscribed": true,
	 *				"IsSelectable": true, "Exists": true, "Extended": null, "SubFolders": null },
	 *			...
	 *		]}, "Namespace": "" } }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetFolders',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains list of folders for specified account.
	 * @param int $AccountID Account identifier.
	 * @return array
	 */
	public function GetFolders($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
		$oFolderCollection = $this->getMailManager()->getFolders($oAccount);
		return array(
			'Folders' => $oFolderCollection, 
			'Namespace' => $oFolderCollection->GetNamespace()
		);
	}
	
	/**
	 * @api {post} ?/Api/ GetMessages
	 * @apiName GetMessages
	 * @apiGroup Mail
	 * @apiDescription Obtains message list for specified account and folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetMessages} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Offset** *int* Says to skip that many messages before beginning to return them.<br>
	 * &emsp; **Limit** *int* Limit says to return that many messages in the list.<br>
	 * &emsp; **Search** *string* Search string.<br>
	 * &emsp; **Filters** *string* List of conditions to obtain messages.<br>
	 * &emsp; **UseThreading** *int* Indicates if it is necessary to return messages in threads.<br>
	 * &emsp; **InboxUidnext** *string* (optional) UIDNEXT Inbox last value that is known on client side.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessages',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox", "Offset": 0, "Limit": 20, "Search": "",
	 *		"Filters": "", "UseThreading": true }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Messages data in case of success, otherwise **false**.
	 * @apiSuccess {int} Result.Result.Count Count of messages.
	 * @apiSuccess {object[]} Result.Result.Collection List of messages
	 * @apiSuccess {string} Result.Result.Collection.Folder Full name of folder that contains the message.
	 * @apiSuccess {int} Result.Result.Collection.Uid Message uid.
	 * @apiSuccess {string} Result.Result.Collection.Subject Message subject.
	 * @apiSuccess {string} Result.Result.Collection.MessageId Message string identifier that is retrieved from message headers.
	 * @apiSuccess {int} Result.Result.Collection.Size Message size.
	 * @apiSuccess {int} Result.Result.Collection.TextSize Message text size.
	 * @apiSuccess {int} Result.Result.Collection.InternalTimeStampInUTC Timestamp of message receiving date.
	 * @apiSuccess {int} Result.Result.Collection.ReceivedOrDateTimeStampInUTC Timestamp of date that is retrieved from message.
	 * @apiSuccess {int} Result.Result.Collection.TimeStampInUTC InternalTimeStampInUTC or ReceivedOrDateTimeStampInUTC depending on UseDateFromHeaders setting.
	 * @apiSuccess {object} Result.Result.Collection.From Collection of sender addresses. Usually contains one address.
	 * @apiSuccess {object} Result.Result.Collection.To Collection of recipient addresses.
	 * @apiSuccess {object} Result.Result.Collection.Cc Collection of recipient addresses which receive copies of message.
	 * @apiSuccess {object} Result.Result.Collection.Bcc Collection of recipient addresses which receive hidden copies of message.
	 * @apiSuccess {object} Result.Result.Collection.ReplyTo Collection of address which is used for message reply.
	 * @apiSuccess {boolean} Result.Result.Collection.IsSeen Indicates if message is seen.
	 * @apiSuccess {boolean} Result.Result.Collection.IsFlagged Indicates if message is flagged.
	 * @apiSuccess {boolean} Result.Result.Collection.IsAnswered Indicates if message is answered.
	 * @apiSuccess {boolean} Result.Result.Collection.IsForwarded Indicates if message is forwarded.
	 * @apiSuccess {boolean} Result.Result.Collection.HasAttachments Indicates if message has attachments.
	 * @apiSuccess {boolean} Result.Result.Collection.HasVcardAttachment Indicates if message has attachment with VCARD.
	 * @apiSuccess {boolean} Result.Result.Collection.HasIcalAttachment Indicates if message has attachment with ICAL.
	 * @apiSuccess {int} Result.Result.Collection.Importance Importance value of the message, from 1 (highest) to 5 (lowest).
	 * @apiSuccess {array} Result.Result.Collection.DraftInfo Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
	 * @apiSuccess {int} Result.Result.Collection.Sensitivity If Sensitivity header was set for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal". 
	 * @apiSuccess {int} Result.Result.Collection.TrimmedTextSize Size of text if it is trimmed.
	 * @apiSuccess {string} Result.Result.Collection.DownloadAsEmlUrl Url for download message as .eml file.
	 * @apiSuccess {string} Result.Result.Collection.Hash Message hash.
	 * @apiSuccess {array} Result.Result.Collection.Threads List of uids of messages that are belonged to one thread.
	 * @apiSuccess {array} Result.Result.Uids List determines order of messages.
	 * @apiSuccess {string} Result.Result.UidNext Last value of folder UIDNEXT.
	 * @apiSuccess {string} Result.Result.FolderHash Folder hash is used to determine if there were changes in folder.
	 * @apiSuccess {int} Result.Result.MessageCount Total count of messages in folder.
	 * @apiSuccess {int} Result.Result.MessageUnseenCount Count of unread messages in folder.
	 * @apiSuccess {int} Result.Result.MessageResultCount Count of messages in obtained list.
	 * @apiSuccess {string} Result.Result.FolderName Full name of folder.
	 * @apiSuccess {int} Result.Result.Offset Says to skip that many messages before beginning to return them.
	 * @apiSuccess {int} Result.Result.Limit Limit says to return that many messages in the list.
	 * @apiSuccess {string} Result.Result.Search Search string.
	 * @apiSuccess {string} Result.Result.Filters List of conditions to obtain messages.
	 * @apiSuccess {array} Result.Result.New List of short information about new messages.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessages',
	 *	Result: { "@Count": 30, "@Collection": [
	 *		{	"Folder": "INBOX", "Uid": 1690, "Subject": "subject_value", "MessageId": "id_value",
	 *			"Size": 17381, "TextSize": 117, "InternalTimeStampInUTC": 1493370309,
	 *			"ReceivedOrDateTimeStampInUTC": 1493370308, "TimeStampInUTC": 1493370309,
	 *			"From": { "@Count": 1, "@Collection": [ { "DisplayName": "", "Email": "test@email" } ] },
	 *			"To": { "@Count":1, "@Collection": [ { "DisplayName": "", "Email": "test2@email" } ] },
	 *			"Cc": null, "Bcc": null, "ReplyTo": null, "IsSeen": true, "IsFlagged": false,
	 *			"IsAnswered": false, "IsForwarded": false, "HasAttachments": true,
	 *			"HasVcardAttachment": false, "HasIcalAttachment": false, "Importance": 3,
	 *			"DraftInfo": null, "Sensitivity": 0, "TrimmedTextSize": 117,
	 *			"DownloadAsEmlUrl": "url_value", "Hash": "hash_value", "Threads": [] },
	 *		...
	 *	],
	 *	"Uids": [1690,1689,1667,1666,1651,1649,1648,1647,1646,1639], "UidNext": "1691",
	 *	"FolderHash": "hash_value", "MessageCount": 639, "MessageUnseenCount": 0,
	 *	"MessageResultCount": 602, "FolderName": "INBOX", "Offset": 0, "Limit": 30, "Search": "",
	 *	"Filters": "", "New": [] }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessages',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains message list for specified account and folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param int $Offset Says to skip that many messages before beginning to return them.
	 * @param int $Limit Limit says to return that many messages in the list.
	 * @param string $Search Search string.
	 * @param string $Filters List of conditions to obtain messages.
	 * @param int $UseThreading Indicates if it is necessary to return messages in threads.
	 * @param string $InboxUidnext UIDNEXT Inbox last value that is known on client side.
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function GetMessages($AccountID, $Folder, $Offset = 0, $Limit = 20, $Search = '', $Filters = '', $UseThreading = false, $InboxUidnext = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
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

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		return $this->getMailManager()->getMessageList(
			$oAccount, $Folder, $iOffset, $iLimit, $sSearch, $UseThreading, $aFilters, $InboxUidnext);
	}

	/**
	 * @api {post} ?/Api/ GetRelevantFoldersInformation
	 * @apiName GetRelevantFoldersInformation
	 * @apiGroup Mail
	 * @apiDescription Obtains relevant information about total and unseen messages count in specified folders.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetRelevantFoldersInformation} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folders** *array* List of folders' full names.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetRelevantFoldersInformation',
	 *	Parameters: '{ "AccountID": 12, "Folders": [ "INBOX", "Spam" ] }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Mail account properties in case of success, otherwise **false**.
	 * @apiSuccess {object[]} Result.Result.Counts List of folders' data where key is folder full name and value is array like [message_count, unread_message_count, "next_message_uid", "hash_to_indicate_changes"]
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetRelevantFoldersInformation',
	 *	Result: { "Counts": { "INBOX": [638, 0, "1690", "97b2a280e7b9f2cbf86857e5cacf63b7"], 
	 *		"Spam": [71, 69, "92", "3c9fe98367857e9930c725010e947d88" ] } }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetRelevantFoldersInformation',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (!\is_array($Folders) || 0 === \count($Folders))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$aResult = array();
		$oAccount = null;

		try
		{
			$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
			$aResult = $this->getMailManager()->getFolderListInformation($oAccount, $Folders);
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
	 * @api {post} ?/Api/ GetQuota
	 * @apiName GetQuota
	 * @apiGroup Mail
	 * @apiDescription Obtains mail account quota.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetQuota} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetQuota',
	 *	Parameters: '{ "AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Array like [quota_limit, used_space] in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetQuota',
	 *	Result: [8976, 10240]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetQuota',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains mail account quota.
	 * @param int $AccountID Account identifier.
	 * @return array|boolean
	 */
	public function GetQuota($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
		return $this->getMailManager()->getQuota($oAccount);
	}

	/**
	 * @api {post} ?/Api/ GetMessagesBodies
	 * @apiName GetMessagesBodies
	 * @apiGroup Mail
	 * @apiDescription Obtains full data of specified messages including plain text, HTML text and attachments.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetMessagesBodies} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Uids** *array* List of messages' uids.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesBodies',
	 *	Parameters: '{ "AccountID": 12, "Folder": "INBOX", "Uids": [ "1591", "1589", "1588", "1587", "1586" ] }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Array of messages in case of success, otherwise **false**.
	 * @apiSuccess {string} Result.Result.Folder Full name of folder that contains the message.
	 * @apiSuccess {int} Result.Result.Uid Message uid.
	 * @apiSuccess {string} Result.Result.Subject Message subject.
	 * @apiSuccess {string} Result.Result.MessageId Message string identifier that is retrieved from message headers.
	 * @apiSuccess {int} Result.Result.Size Message size.
	 * @apiSuccess {int} Result.Result.TextSize Message text size.
	 * @apiSuccess {int} Result.Result.InternalTimeStampInUTC Timestamp of message receiving date.
	 * @apiSuccess {int} Result.Result.ReceivedOrDateTimeStampInUTC Timestamp of date that is retrieved from message.
	 * @apiSuccess {int} Result.Result.TimeStampInUTC InternalTimeStampInUTC or ReceivedOrDateTimeStampInUTC depending on UseDateFromHeaders setting.
	 * @apiSuccess {object} Result.Result.From Collection of sender addresses. Usually contains one address.
	 * @apiSuccess {object} Result.Result.To Collection of recipient addresses.
	 * @apiSuccess {object} Result.Result.Cc Collection of recipient addresses which receive copies of message.
	 * @apiSuccess {object} Result.Result.Bcc Collection of recipient addresses which receive hidden copies of message.
	 * @apiSuccess {object} Result.Result.ReplyTo Collection of address which is used for message reply.
	 * @apiSuccess {boolean} Result.Result.IsSeen Indicates if message is seen.
	 * @apiSuccess {boolean} Result.Result.IsFlagged Indicates if message is flagged.
	 * @apiSuccess {boolean} Result.Result.IsAnswered Indicates if message is answered.
	 * @apiSuccess {boolean} Result.Result.IsForwarded Indicates if message is forwarded.
	 * @apiSuccess {boolean} Result.Result.HasAttachments Indicates if message has attachments.
	 * @apiSuccess {boolean} Result.Result.HasVcardAttachment Indicates if message has attachment with VCARD.
	 * @apiSuccess {boolean} Result.Result.HasIcalAttachment Indicates if message has attachment with ICAL.
	 * @apiSuccess {int} Result.Result.Importance Importance value of the message, from 1 (highest) to 5 (lowest).
	 * @apiSuccess {array} Result.Result.DraftInfo Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
	 * @apiSuccess {int} Result.Result.Sensitivity If Sensitivity header was set for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal". 
	 * @apiSuccess {int} Result.Result.TrimmedTextSize Size of text if it is trimmed.
	 * @apiSuccess {string} Result.Result.DownloadAsEmlUrl Url for download message as .eml file.
	 * @apiSuccess {string} Result.Result.Hash Message hash.
	 * @apiSuccess {string} Result.Result.Headers Block of headers of the message.
	 * @apiSuccess {string} Result.Result.InReplyTo Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.
	 * @apiSuccess {string} Result.Result.References Content of References header block of the message. 
	 * @apiSuccess {string} Result.Result.ReadingConfirmationAddressee Email address reading confirmation is to be sent to.
	 * @apiSuccess {string} Result.Result.Html HTML body of the message.
	 * @apiSuccess {boolean} Result.Result.Trimmed Indicates if message body is trimmed.
	 * @apiSuccess {string} Result.Result.Plain Message plaintext body prepared for display.
	 * @apiSuccess {string} Result.Result.PlainRaw Message plaintext body as is.
	 * @apiSuccess {boolean} Result.Result.Rtl Indicates if message body contains symbols from one of rtl languages.
	 * @apiSuccess {array} Result.Result.Extend List of custom content, implemented for use of ICAL/VCARD content.
	 * @apiSuccess {boolean} Result.Result.Safety Indication of whether the sender is trustworthy so it's safe to display external images.
	 * @apiSuccess {boolean} Result.Result.HasExternals Indicates if HTML message body contains images with external URLs.
	 * @apiSuccess {array} Result.Result.FoundedCIDs List of content-IDs used for inline attachments.
	 * @apiSuccess {array} Result.Result.FoundedContentLocationUrls
	 * @apiSuccess {array} Result.Result.Attachments Information about attachments of the message.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesBodies',
	 *	Result: [
	 *		{ "Folder": "INBOX", "Uid": 1591, "Subject": "test", "MessageId": "string_id", "Size": 2578,
	 * "TextSize": 243, "InternalTimeStampInUTC": 1490615414, "ReceivedOrDateTimeStampInUTC": 1490615414,
	 * "TimeStampInUTC": 1490615414, "From": {"@Count": 1, "@Collection": [ { "DisplayName": "",
	 * "Email": "test@afterlogic.com" } ] }, "To": { "@Count": 1, "@Collection": [ { "DisplayName": "test",
	 * "Email":"test@afterlogic.com" } ] }, "Cc": null, "Bcc": null, "ReplyTo": null, "IsSeen": true,
	 * "IsFlagged": false, "IsAnswered": false, "IsForwarded": false, "HasAttachments": false,
	 * "HasVcardAttachment": false, "HasIcalAttachment": false, "Importance": 3, "DraftInfo": null,
	 * "Sensitivity": 0, "TrimmedTextSize": 243, "DownloadAsEmlUrl": "url_value", "Hash": "hash_value",
	 * "Headers": "headers_value", "InReplyTo": "", "References": "", "ReadingConfirmationAddressee": "",
	 * "Html": "html_text_of_message", "Trimmed": false, "Plain": "", "PlainRaw": "", "Rtl": false,
	 * "Extend": [], "Safety": false, "HasExternals": false, "FoundedCIDs": [],
	 * "FoundedContentLocationUrls": [], "Attachments": null },
	 *		...
	 *	]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesBodies',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains full data of specified messages including plain text, HTML text and attachments.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param array $Uids List of messages' uids.
	 * @return array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function GetMessagesBodies($AccountID, $Folder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
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
				if ($oMessage instanceof \Aurora\Modules\Mail\Classes\Message)
				{
					$aList[] = $oMessage;
				}

				unset($oMessage);
			}
		}

		return $aList;
	}

	/**
	 * @api {post} ?/Api/ GetMessage
	 * @apiName GetMessage
	 * @apiGroup Mail
	 * @apiDescription Obtains full data of specified message including plain text, HTML text and attachments.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetMessage} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountId** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Uid** *string* Message uid.<br>
	 * &emsp; **Rfc822MimeIndex** *string* (optional) If specified obtains message from attachment of another message.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessage',
	 *	Parameters: '{ "AccountId": 12, "Folder": "Inbox", "Uid": 1232 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Message properties in case of success, otherwise **false**.
	 * @apiSuccess {string} Result.Result.Folder Full name of folder that contains the message.
	 * @apiSuccess {int} Result.Result.Uid Message uid.
	 * @apiSuccess {string} Result.Result.Subject Message subject.
	 * @apiSuccess {string} Result.Result.MessageId Message string identifier that is retrieved from message headers.
	 * @apiSuccess {int} Result.Result.Size Message size.
	 * @apiSuccess {int} Result.Result.TextSize Message text size.
	 * @apiSuccess {int} Result.Result.InternalTimeStampInUTC Timestamp of message receiving date.
	 * @apiSuccess {int} Result.Result.ReceivedOrDateTimeStampInUTC Timestamp of date that is retrieved from message.
	 * @apiSuccess {int} Result.Result.TimeStampInUTC InternalTimeStampInUTC or ReceivedOrDateTimeStampInUTC depending on UseDateFromHeaders setting.
	 * @apiSuccess {object} Result.Result.From Collection of sender addresses. Usually contains one address.
	 * @apiSuccess {object} Result.Result.To Collection of recipient addresses.
	 * @apiSuccess {object} Result.Result.Cc Collection of recipient addresses which receive copies of message.
	 * @apiSuccess {object} Result.Result.Bcc Collection of recipient addresses which receive hidden copies of message.
	 * @apiSuccess {object} Result.Result.ReplyTo Collection of address which is used for message reply.
	 * @apiSuccess {boolean} Result.Result.IsSeen Indicates if message is seen.
	 * @apiSuccess {boolean} Result.Result.IsFlagged Indicates if message is flagged.
	 * @apiSuccess {boolean} Result.Result.IsAnswered Indicates if message is answered.
	 * @apiSuccess {boolean} Result.Result.IsForwarded Indicates if message is forwarded.
	 * @apiSuccess {boolean} Result.Result.HasAttachments Indicates if message has attachments.
	 * @apiSuccess {boolean} Result.Result.HasVcardAttachment Indicates if message has attachment with VCARD.
	 * @apiSuccess {boolean} Result.Result.HasIcalAttachment Indicates if message has attachment with ICAL.
	 * @apiSuccess {int} Result.Result.Importance Importance value of the message, from 1 (highest) to 5 (lowest).
	 * @apiSuccess {array} Result.Result.DraftInfo Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
	 * @apiSuccess {int} Result.Result.Sensitivity If Sensitivity header was set for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal". 
	 * @apiSuccess {int} Result.Result.TrimmedTextSize Size of text if it is trimmed.
	 * @apiSuccess {string} Result.Result.DownloadAsEmlUrl Url for download message as .eml file.
	 * @apiSuccess {string} Result.Result.Hash Message hash.
	 * @apiSuccess {string} Result.Result.Headers Block of headers of the message.
	 * @apiSuccess {string} Result.Result.InReplyTo Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.
	 * @apiSuccess {string} Result.Result.References Content of References header block of the message. 
	 * @apiSuccess {string} Result.Result.ReadingConfirmationAddressee Email address reading confirmation is to be sent to.
	 * @apiSuccess {string} Result.Result.Html HTML body of the message.
	 * @apiSuccess {boolean} Result.Result.Trimmed Indicates if message body is trimmed.
	 * @apiSuccess {string} Result.Result.Plain Message plaintext body prepared for display.
	 * @apiSuccess {string} Result.Result.PlainRaw Message plaintext body as is.
	 * @apiSuccess {boolean} Result.Result.Rtl Indicates if message body contains symbols from one of rtl languages.
	 * @apiSuccess {array} Result.Result.Extend List of custom content, implemented for use of ICAL/VCARD content.
	 * @apiSuccess {boolean} Result.Result.Safety Indication of whether the sender is trustworthy so it's safe to display external images.
	 * @apiSuccess {boolean} Result.Result.HasExternals Indicates if HTML message body contains images with external URLs.
	 * @apiSuccess {array} Result.Result.FoundedCIDs List of content-IDs used for inline attachments.
	 * @apiSuccess {array} Result.Result.FoundedContentLocationUrls
	 * @apiSuccess {array} Result.Result.Attachments Information about attachments of the message.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessage',
	 *	Result: { "Folder": "INBOX", "Uid": 1591, "Subject": "test", "MessageId": "string_id", "Size": 2578,
	 * "TextSize": 243, "InternalTimeStampInUTC": 1490615414, "ReceivedOrDateTimeStampInUTC": 1490615414,
	 * "TimeStampInUTC": 1490615414, "From": {"@Count": 1, "@Collection": [ { "DisplayName": "",
	 * "Email": "test@afterlogic.com" } ] }, "To": { "@Count": 1, "@Collection": [ { "DisplayName": "test",
	 * "Email":"test@afterlogic.com" } ] }, "Cc": null, "Bcc": null, "ReplyTo": null, "IsSeen": true,
	 * "IsFlagged": false, "IsAnswered": false, "IsForwarded": false, "HasAttachments": false,
	 * "HasVcardAttachment": false, "HasIcalAttachment": false, "Importance": 3, "DraftInfo": null,
	 * "Sensitivity": 0, "TrimmedTextSize": 243, "DownloadAsEmlUrl": "url_value", "Hash": "hash_value",
	 * "Headers": "headers_value", "InReplyTo": "", "References": "", "ReadingConfirmationAddressee": "", 
	 * "Html": "html_text_of_message", "Trimmed": false, "Plain": "", "PlainRaw": "", "Rtl": false, "Extend": [],
	 * "Safety": false, "HasExternals": false, "FoundedCIDs": [], "FoundedContentLocationUrls": [], "Attachments": null }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessage',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains full data of specified message including plain text, HTML text and attachments.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param string $Uid Message uid.
	 * @param string $Rfc822MimeIndex If specified obtains message from attachment of another message.
	 * @return \Aurora\Modules\Mail\Classes\Message
	 * @throws \Aurora\System\Exceptions\ApiException
	 * @throws CApiInvalidArgumentException
	 */
	public function GetMessage($AccountID, $Folder, $Uid, $Rfc822MimeIndex = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$iBodyTextLimit = 600000;
		
		$iUid = 0 < \strlen($Uid) && \is_numeric($Uid) ? (int) $Uid : 0;

		if (0 === \strlen(\trim($Folder)) || 0 >= $iUid)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		if (0 === \strlen($Folder) || !\is_numeric($iUid) || 0 >= (int) $iUid)
		{
			throw new \CApiInvalidArgumentException();
		}

		$oImapClient =& $this->getMailManager()->_getImapClient($oAccount);

		$oImapClient->FolderExamine($Folder);

		$oMessage = false;

		$aTextMimeIndexes = array();

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

		if (!$oBodyStructure)
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE;
		}

		$aFetchResponse = $oImapClient->Fetch($aFetchItems, $iUid, true);
		if (0 < \count($aFetchResponse))
		{
			$oMessage = \Aurora\Modules\Mail\Classes\Message::createInstance($Folder, $aFetchResponse[0], $oBodyStructure, $Rfc822MimeIndex);
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
				$bAlwaysShowImagesInMessage = !!$this->getConfig('AlwaysShowImagesInMessage', false);
				$oMessage->setSafety($bAlwaysShowImagesInMessage ? true : 
						$this->getMailManager()->isSafetySender($oAccount->IdUser, $sFromEmail));
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

		if (!($oMessage instanceof \Aurora\Modules\Mail\Classes\Message))
		{
			throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotGetMessage);
		}

		return $oMessage;
	}

	/**
	 * @api {post} ?/Api/ SetMessagesSeen
	 * @apiName SetMessagesSeen
	 * @apiGroup Mail
	 * @apiDescription Puts on or off seen flag of message.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SetMessagesSeen} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Uids** *string* List of messages' uids.<br>
	 * &emsp; **SetAction** *boolean* Indicates if flag should be set or removed.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetMessagesSeen',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox", "Uids": "1243,1244,1245", "SetAction": false }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if seen flag was set successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetMessagesSeen',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetMessagesSeen',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::SEEN);
	}	
	
	/**
	 * @api {post} ?/Api/ SetMessageFlagged
	 * @apiName SetMessageFlagged
	 * @apiGroup Mail
	 * @apiDescription Puts on or off flagged flag of message.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SetMessageFlagged} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Uids** *string* List of messages' uids.<br>
	 * &emsp; **SetAction** *boolean* Indicates if flag should be set or removed.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetMessageFlagged',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox", "Uids": "1243,1244,1245", "SetAction": false }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if flagged flag was set successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetMessageFlagged',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetMessageFlagged',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::FLAGGED);
	}
	
	/**
	 * @api {post} ?/Api/ SetAllMessagesSeen
	 * @apiName SetAllMessagesSeen
	 * @apiGroup Mail
	 * @apiDescription Puts on seen flag for all messages in folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SetAllMessagesSeen} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetAllMessagesSeen',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if seen flag was set successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetAllMessagesSeen',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetAllMessagesSeen',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Puts on seen flag for all messages in folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SetAllMessagesSeen($AccountID, $Folder)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		return $this->getMailManager()->setMessageFlag($oAccount, $Folder, array(),
			\MailSo\Imap\Enumerations\MessageFlag::SEEN, \Aurora\Modules\Mail\Enums\MessageStoreAction::Add, true);
	}

	/**
	 * @api {post} ?/Api/ MoveMessages
	 * @apiName MoveMessages
	 * @apiGroup Mail
	 * @apiDescription Moves messages from one folder to another.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=MoveMessages} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Full name of the folder messages will be moved from.<br>
	 * &emsp; **ToFolder** *string* Full name of the folder messages will be moved to.<br>
	 * &emsp; **Uids** *string* Uids of messages to move.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'MoveMessages',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox", "ToFolder": "Trash", "Uids": "1212,1213,1215" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if messages were moved successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'MoveMessages',
	 *	Result: true
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'MoveMessages',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Moves messages from one folder to another.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Full name of the folder messages will be moved from.
	 * @param string $ToFolder Full name of the folder which messages will be moved to.
	 * @param string $Uids Uids of messages to move.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function CopyMessages($AccountID, $Folder, $ToFolder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$aUids = \Aurora\System\Utils::ExplodeIntUids((string) $Uids);

		if (0 === \strlen(\trim($Folder)) || 0 === \strlen(\trim($ToFolder)) || !\is_array($aUids) || 0 === \count($aUids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		try
		{
			$this->getMailManager()->copyMessage($oAccount, $Folder, $ToFolder, $aUids);
		}
		catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotMoveMessageQuota, $oException,
				$oResponse instanceof \MailSo\Imap\Response ? $oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '');
		}
		catch (\Exception $oException)
		{
			throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotMoveMessage, $oException,
				$oException->getMessage());
		}

		return true;
	}	
	
	/**
	 * @api {post} ?/Api/ MoveMessages
	 * @apiName MoveMessages
	 * @apiGroup Mail
	 * @apiDescription Moves messages from one folder to another.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=MoveMessages} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Full name of the folder messages will be moved from.<br>
	 * &emsp; **ToFolder** *string* Full name of the folder messages will be moved to.<br>
	 * &emsp; **Uids** *string* Uids of messages to move.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'MoveMessages',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox", "ToFolder": "Trash", "Uids": "1212,1213,1215" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if messages were moved successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'MoveMessages',
	 *	Result: true
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'MoveMessages',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Moves messages from one folder to another.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Full name of the folder messages will be moved from.
	 * @param string $ToFolder Full name of the folder which messages will be moved to.
	 * @param string $Uids Uids of messages to move.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function MoveMessages($AccountID, $Folder, $ToFolder, $Uids)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$aUids = \Aurora\System\Utils::ExplodeIntUids((string) $Uids);

		if (0 === \strlen(\trim($Folder)) || 0 === \strlen(\trim($ToFolder)) || !\is_array($aUids) || 0 === \count($aUids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		try
		{
			$this->getMailManager()->moveMessage($oAccount, $Folder, $ToFolder, $aUids);
		}
		catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotMoveMessageQuota, $oException,
				$oResponse instanceof \MailSo\Imap\Response ? $oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '');
		}
		catch (\Exception $oException)
		{
			throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotMoveMessage, $oException,
				$oException->getMessage());
		}

		return true;
	}
	
	/**
	 * @api {post} ?/Api/ DeleteMessages
	 * @apiName DeleteMessages
	 * @apiGroup Mail
	 * @apiDescription Deletes messages from folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=DeleteMessages} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Uids** *string* Uids of messages to delete.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteMessages',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox", "Uids": "1212,1213,1215" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if messages were deleted successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteMessages',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteMessages',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$aUids = \Aurora\System\Utils::ExplodeIntUids((string) $Uids);

		if (0 === \strlen(\trim($Folder)) || !\is_array($aUids) || 0 === \count($aUids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		$this->getMailManager()->deleteMessage($oAccount, $Folder, $aUids);

		return true;
	}
	
	/**
	 * @api {post} ?/Api/ CreateFolder
	 * @apiName CreateFolder
	 * @apiGroup Mail
	 * @apiDescription Creates folder in mail account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=CreateFolder} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **FolderNameInUtf8** *string* Name of folder to create.<br>
	 * &emsp; **FolderParentFullNameRaw** *string* Full name of parent folder.<br>
	 * &emsp; **Delimiter** *string* Delimiter that is used if full folder name.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateFolder',
	 *	Parameters: '{ "AccountID": 12, "FolderNameInUtf8": "new_folder",
	 *			"FolderParentFullNameRaw": "parent_folder", "Delimiter": "/" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if folder was created successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateFolder',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateFolder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		$bResult = true;
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen($FolderNameInUtf8) || 1 !== \strlen($Delimiter))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		try
		{
			$this->getMailManager()->createFolder($oAccount, $FolderNameInUtf8, $Delimiter, $FolderParentFullNameRaw);
		} 
		catch (\MailSo\Mail\Exceptions\AlreadyExistsFolder $oException) 
		{
			throw new \Aurora\Modules\Mail\Exceptions\Exception(
				Enums\ErrorCodes::FolderAlreadyExists, 
				$oException,
				$oException->getMessage()
			);
		}

		$aFoldersOrderList = $this->getMailManager()->getFoldersOrder($oAccount);
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
					$this->getMailManager()->updateFoldersOrder($oAccount, $aFoldersOrderList);
				}
			}
		}

		return $bResult;
	}
	
	/**
	 * @api {post} ?/Api/ RenameFolder
	 * @apiName RenameFolder
	 * @apiGroup Mail
	 * @apiDescription Renames folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=RenameFolder} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **PrevFolderFullNameRaw** *int* Full name of folder to rename.<br>
	 * &emsp; **NewFolderNameInUtf8** *int* New folder name.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'RenameFolder',
	 *	Parameters: '{ "AccountID": 12, "PrevFolderFullNameRaw": "old_folder_name",
	 *		"NewFolderNameInUtf8": "new_folder_name" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result New folder name information in case of success, otherwise **false**.
	 * @apiSuccess {string} Result.Result.FullName New full name of folder.
	 * @apiSuccess {string} Result.Result.FullNameHash Hash of new full name of folder.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'RenameFolder',
	 *	Result: { "FullName": "new_folder_name", "FullNameHash": "hash_value" }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'RenameFolder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen($PrevFolderFullNameRaw) || 0 === \strlen($NewFolderNameInUtf8))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		$mResult = $this->getMailManager()->renameFolder($oAccount, $PrevFolderFullNameRaw, $NewFolderNameInUtf8);

		return (0 < \strlen($mResult) ? array(
			'FullName' => $mResult,
			'FullNameHash' => \md5($mResult)
		) : false);
	}

	/**
	 * @api {post} ?/Api/ DeleteFolder
	 * @apiName DeleteFolder
	 * @apiGroup Mail
	 * @apiDescription Deletes folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=DeleteFolder} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Full name of folder to delete.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteFolder',
	 *	Parameters: '{ "AccountID": 12, "Folder": "folder2" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if folder was deleted successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteFolder',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteFolder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Deletes folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Full name of folder to delete.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function DeleteFolder($AccountID, $Folder)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		$this->getMailManager()->deleteFolder($oAccount, $Folder);

		return true;
	}	

	/**
	 * @api {post} ?/Api/ SubscribeFolder
	 * @apiName SubscribeFolder
	 * @apiGroup Mail
	 * @apiDescription Subscribes/unsubscribes folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SubscribeFolder} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Full name of folder to subscribe/unsubscribe.<br>
	 * &emsp; **SetAction** *boolean* Indicates if folder should be subscribed or unsubscribed.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SubscribeFolder',
	 *	Parameters: '{ "AccountID": 12, "Folder": "folder2", "SetAction": true }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if folder was subscribed/unsubscribed successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SubscribeFolder',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SubscribeFolder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('IgnoreImapSubscription', false))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::AccessDenied);
		}
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		$this->getMailManager()->subscribeFolder($oAccount, $Folder, $SetAction);
		
		return true;
	}	
	
	/**
	 * @api {post} ?/Api/ UpdateFoldersOrder
	 * @apiName UpdateFoldersOrder
	 * @apiGroup Mail
	 * @apiDescription Updates order of folders.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateFoldersOrder} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **FolderList** *array* List of folders with new order.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateFoldersOrder',
	 *	Parameters: '{ "AccountID": 12, "FolderList": [ "INBOX", "Sent", "Drafts", "Trash", "Spam", "folder1" ] }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if folders' order was changed successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateFoldersOrder',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateFoldersOrder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates order of folders.
	 * @param int $AccountID Account identifier.
	 * @param array $FolderList List of folders with new order.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function UpdateFoldersOrder($AccountID, $FolderList)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (!\is_array($FolderList))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		return $this->getMailManager()->updateFoldersOrder($oAccount, $FolderList);
	}
	
	/**
	 * @api {post} ?/Api/ ClearFolder
	 * @apiName ClearFolder
	 * @apiGroup Mail
	 * @apiDescription Removes all messages from folder. Method is used for Trash and Spam folders.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=ClearFolder} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'ClearFolder',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Trash" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if folder was cleared successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'ClearFolder',
	 *	Result: true
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'ClearFolder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Removes all messages from folder. Method is used for Trash and Spam folders.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function ClearFolder($AccountID, $Folder)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		$this->getMailManager()->clearFolder($oAccount, $Folder);

		return true;
	}
	
	/**
	 * @api {post} ?/Api/ GetMessagesByUids
	 * @apiName GetMessagesByUids
	 * @apiGroup Mail
	 * @apiDescription Obtains message list for specified messages' uids.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetMessagesByUids} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Uids** *array* Uids of messages to obtain.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesByUids',
	 *	Parameters: '{ "AccountID": 12, "Folder": "Inbox", "Uids": [ "1221", "1222", "1226" ] }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Messages data in case of success, otherwise **false**.
	 * @apiSuccess {int} Result.Result.Count Count of messages.
	 * @apiSuccess {object[]} Result.Result.Collection List of messages
	 * @apiSuccess {string} Result.Result.Collection.Folder Full name of folder that contains the message.
	 * @apiSuccess {int} Result.Result.Collection.Uid Message uid.
	 * @apiSuccess {string} Result.Result.Collection.Subject Message subject.
	 * @apiSuccess {string} Result.Result.Collection.MessageId Message string identifier that is retrieved from message headers.
	 * @apiSuccess {int} Result.Result.Collection.Size Message size.
	 * @apiSuccess {int} Result.Result.Collection.TextSize Message text size.
	 * @apiSuccess {int} Result.Result.Collection.InternalTimeStampInUTC Timestamp of message receiving date.
	 * @apiSuccess {int} Result.Result.Collection.ReceivedOrDateTimeStampInUTC Timestamp of date that is retrieved from message.
	 * @apiSuccess {int} Result.Result.Collection.TimeStampInUTC InternalTimeStampInUTC or ReceivedOrDateTimeStampInUTC depending on UseDateFromHeaders setting.
	 * @apiSuccess {object} Result.Result.Collection.From Collection of sender addresses. Usually contains one address.
	 * @apiSuccess {object} Result.Result.Collection.To Collection of recipient addresses.
	 * @apiSuccess {object} Result.Result.Collection.Cc Collection of recipient addresses which receive copies of message.
	 * @apiSuccess {object} Result.Result.Collection.Bcc Collection of recipient addresses which receive hidden copies of message.
	 * @apiSuccess {object} Result.Result.Collection.ReplyTo Collection of address which is used for message reply.
	 * @apiSuccess {boolean} Result.Result.Collection.IsSeen Indicates if message is seen.
	 * @apiSuccess {boolean} Result.Result.Collection.IsFlagged Indicates if message is flagged.
	 * @apiSuccess {boolean} Result.Result.Collection.IsAnswered Indicates if message is answered.
	 * @apiSuccess {boolean} Result.Result.Collection.IsForwarded Indicates if message is forwarded.
	 * @apiSuccess {boolean} Result.Result.Collection.HasAttachments Indicates if message has attachments.
	 * @apiSuccess {boolean} Result.Result.Collection.HasVcardAttachment Indicates if message has attachment with VCARD.
	 * @apiSuccess {boolean} Result.Result.Collection.HasIcalAttachment Indicates if message has attachment with ICAL.
	 * @apiSuccess {int} Result.Result.Collection.Importance Importance value of the message, from 1 (highest) to 5 (lowest).
	 * @apiSuccess {array} Result.Result.Collection.DraftInfo Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
	 * @apiSuccess {int} Result.Result.Collection.Sensitivity If Sensitivity header was set for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal". 
	 * @apiSuccess {int} Result.Result.Collection.TrimmedTextSize Size of text if it is trimmed.
	 * @apiSuccess {string} Result.Result.Collection.DownloadAsEmlUrl Url for download message as .eml file.
	 * @apiSuccess {string} Result.Result.Collection.Hash Message hash.
	 * @apiSuccess {array} Result.Result.Collection.Threads List of uids of messages that are belonged to one thread.
	 * @apiSuccess {array} Result.Result.Uids List determines order of messages.
	 * @apiSuccess {string} Result.Result.UidNext Last value of folder UIDNEXT.
	 * @apiSuccess {string} Result.Result.FolderHash Folder hash is used to determine if there were changes in folder.
	 * @apiSuccess {int} Result.Result.MessageCount Total count of messages in folder.
	 * @apiSuccess {int} Result.Result.MessageUnseenCount Count of unread messages in folder.
	 * @apiSuccess {int} Result.Result.MessageResultCount Count of messages in obtained list.
	 * @apiSuccess {string} Result.Result.FolderName Full name of folder.
	 * @apiSuccess {int} Result.Result.Offset Says to skip that many messages before beginning to return them.
	 * @apiSuccess {int} Result.Result.Limit Limit says to return that many messages in the list.
	 * @apiSuccess {string} Result.Result.Search Search string.
	 * @apiSuccess {string} Result.Result.Filters List of conditions to obtain messages.
	 * @apiSuccess {array} Result.Result.New List of short information about new messages.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesByUids',
	 *	Result: {
	 *		"@Count": 30,"@Collection": [
	 *			{ "Folder": "INBOX", "Uid": 1689, "Subject": "subject_value", "MessageId": "string_id", 
	 * "Size": 2947, "TextSize": 321, "InternalTimeStampInUTC": 1493290584,
	 * "ReceivedOrDateTimeStampInUTC": 1493290584, "TimeStampInUTC": 1493290584,
	 * "From": {"@Count": 1, "@Collection": [ { "DisplayName": "","Email": "test@email" } ] }, 
	 * "To": {"@Count": 1, "@Collection": [ { "DisplayName": "", "Email": "test2@email" } ] },
	 * "Cc": null, "Bcc": null,
	 * "ReplyTo": { "@Count": 1, "@Collection": [ { "DisplayName": "AfterLogic", "Email":"test@email" } ] }, 
	 * "IsSeen": true, "IsFlagged": false, "IsAnswered": false, "IsForwarded": false,
	 * "HasAttachments": false, "HasVcardAttachment": false, "HasIcalAttachment": false, "Importance": 3,
	 * "DraftInfo": null, "Sensitivity": 0, "TrimmedTextSize": 321, "DownloadAsEmlUrl": "url_value",
	 * "Hash": "hash_value", "Threads": [] },
	 *			... ],
	 *		"Uids": [1689,1667,1666,1651,1649,1648,1647,1646,1639,1638],
	 *		"UidNext": "1690", "FolderHash": "97b2a280e7b9f2cbf86857e5cacf63b7", "MessageCount": 638,
	 *		"MessageUnseenCount": 0, "MessageResultCount": 601, "FolderName": "INBOX", "Offset": 0,
	 *		"Limit": 30, "Search": "", "Filters": "", "New": []
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesByUids',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen(trim($Folder)) || !\is_array($Uids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		return $this->getMailManager()->getMessageListByUids($oAccount, $Folder, $Uids);
	}
	
	/**
	 * @api {post} ?/Api/ GetMessagesFlags
	 * @apiName GetMessagesFlags
	 * @apiGroup Mail
	 * @apiDescription Obtains infomation about flagged flags for specified messages.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetMessagesFlags} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * &emsp; **Uids** *array* Uids of messages.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesFlags'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List of flags for every message uid in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesFlags',
	 *	Result: { "1649": ["\flagged", "\seen"], "1666": ["\flagged", "\seen"] }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetMessagesFlags',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen(\trim($Folder)) || !\is_array($Uids))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		return $this->getMailManager()->getMessagesFlags($oAccount, $Folder, $Uids);
	}
	
	/**
	 * @api {post} ?/Api/ SaveMessage
	 * @apiName SaveMessage
	 * @apiGroup Mail
	 * @apiDescription Saves message to Drafts folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SaveMessage} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **FetcherID** *string* Fetcher identifier.<br>
	 * &emsp; **IdentityID** *int* Identity identifier.<br>
	 * &emsp; **DraftInfo** *array* Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.<br>
	 * &emsp; **DraftUid** *string* Uid of message to save in Drafts folder.<br>
	 * &emsp; **To** *string* Message recipients.<br>
	 * &emsp; **Cc** *string* Recipients which will get a copy of the message.<br>
	 * &emsp; **Bcc** *string* Recipients which will get a hidden copy of the message.<br>
	 * &emsp; **Subject** *string* Subject of the message.<br>
	 * &emsp; **Text** *string* Text of the message.<br>
	 * &emsp; **IsHtml** *boolean* Indicates if text of the message is HTML or plain.<br>
	 * &emsp; **Importance** *int* Importance of the message - LOW = 5, NORMAL = 3, HIGH = 1.<br>
	 * &emsp; **SendReadingConfirmation** *boolean* Indicates if it is necessary to include header that says.<br>
	 * &emsp; **Attachments** *array* List of attachments.<br>
	 * &emsp; **InReplyTo** *string* Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.<br>
	 * &emsp; **References** *string* Content of References header block of the message.<br>
	 * &emsp; **Sensitivity** *int* Sensitivity header for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal".<br>
	 * &emsp; **DraftFolder** *string* Full name of Drafts folder.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveMessage',
	 *	Parameters: '{ "AccountID": 12, "FetcherID": "", "IdentityID": 14, "DraftInfo": [], "DraftUid": "",
	 * "To": "test@email", "Cc": "", "Bcc": "", "Subject": "", "Text": "text_value", "IsHtml": true,
	 * "Importance": 3, "SendReadingConfirmation": false, "Attachments": [], "InReplyTo": "", "References": "",
	 * "Sensitivity": 0, "DraftFolder": "Drafts" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if message was saved successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveMessage',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveMessage',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Saves message to Drafts folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Fetcher Fetcher object is filled in by subscription. Webclient sends FetcherID parameter.
	 * @param int $IdentityID Identity identifier.
	 * @param array $DraftInfo Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
	 * @param string $DraftUid Uid of message to save in Drafts folder.
	 * @param string $To Message recipients.
	 * @param string $Cc Recipients which will get a copy of the message.
	 * @param string $Bcc Recipients which will get a hidden copy of the message.
	 * @param string $Subject Subject of the message.
	 * @param string $Text Text of the message.
	 * @param boolean $IsHtml Indicates if text of the message is HTML or plain.
	 * @param int $Importance Importance of the message - LOW = 5, NORMAL = 3, HIGH = 1.
	 * @param boolean $SendReadingConfirmation Indicates if it is necessary to include header that says
	 * @param array $Attachments List of attachments.
	 * @param string $InReplyTo Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.
	 * @param string $References Content of References header block of the message. 
	 * @param int $Sensitivity Sensitivity header for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal". 
	 * @param string $DraftFolder Full name of Drafts folder.
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SaveMessage($AccountID, $Fetcher = null, $IdentityID = 0, 
			$DraftInfo = [], $DraftUid = "", $To = "", $Cc = "", $Bcc = "", 
			$Subject = "", $Text = "", $IsHtml = false, $Importance = \MailSo\Mime\Enumerations\MessagePriority::NORMAL, 
			$SendReadingConfirmation = false, $Attachments = array(), $InReplyTo = "", 
			$References = "", $Sensitivity = \MailSo\Mime\Enumerations\Sensitivity::NOTHING, $DraftFolder = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		if (0 === \strlen($DraftFolder))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}
		
		$oIdentity = $IdentityID !== 0 ? $this->getIdentitiesManager()->getIdentity($IdentityID) : null;

		$oMessage = $this->Decorator()->BuildMessage($oAccount, $To, $Cc, $Bcc, 
			$Subject, $IsHtml, $Text, $Attachments, $DraftInfo, $InReplyTo, $References, $Importance,
			$Sensitivity, $SendReadingConfirmation, $Fetcher, true, $oIdentity);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->getMailManager()->saveMessage($oAccount, $oMessage, $DraftFolder, $DraftUid);
			}
			catch (\Aurora\System\Exceptions\ManagerException $oException)
			{
				throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotSaveMessage, $oException, $oException->getMessage());
			}
		}

		return $mResult;
	}	
	
	/**
	 * @api {post} ?/Api/ SendMessage
	 * @apiName SendMessage
	 * @apiGroup Mail
	 * @apiDescription Sends message.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SendMessage} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **FetcherID** *string* Fetcher identifier.<br>
	 * &emsp; **IdentityID** *int* Identity identifier.<br>
	 * &emsp; **DraftInfo** *array* Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.<br>
	 * &emsp; **DraftUid** *string* Uid of message to save in Drafts folder.<br>
	 * &emsp; **To** *string* Message recipients.<br>
	 * &emsp; **Cc** *string* Recipients which will get a copy of the message.<br>
	 * &emsp; **Bcc** *string* Recipients which will get a hidden copy of the message.<br>
	 * &emsp; **Subject** *string* Subject of the message.<br>
	 * &emsp; **Text** *string* Text of the message.<br>
	 * &emsp; **IsHtml** *boolean* Indicates if text of the message is HTML or plain.<br>
	 * &emsp; **Importance** *int* Importance of the message - LOW = 5, NORMAL = 3, HIGH = 1.<br>
	 * &emsp; **SendReadingConfirmation** *boolean* Indicates if it is necessary to include header that says.<br>
	 * &emsp; **Attachments** *array* List of attachments.<br>
	 * &emsp; **InReplyTo** *string* Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.<br>
	 * &emsp; **References** *string* Content of References header block of the message.<br>
	 * &emsp; **Sensitivity** *int* Sensitivity header for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal".<br>
	 * &emsp; **SentFolder** *string* Full name of Sent folder.<br>
	 * &emsp; **DraftFolder** *string* Full name of Drafts folder.<br>
	 * &emsp; **ConfirmFolder** *string* Full name of folder that contains a message that should be marked as confirmed read.<br>
	 * &emsp; **ConfirmUid** *string* Uid of message that should be marked as confirmed read.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SendMessage',
	 *	Parameters: '{ "AccountID": 12, "FetcherID": "", "IdentityID": 14, "DraftInfo": [], "DraftUid": "",
	 * "To": "test@email", "Cc": "", "Bcc": "", "Subject": "", "Text": "text_value", "IsHtml": true,
	 * "Importance": 3, "SendReadingConfirmation": false, "Attachments": [], "InReplyTo": "", "References": "",
	 * "Sensitivity": 0, "SentFolder": "Sent", "DraftFolder": "Drafts", "ConfirmFolder": "", "ConfirmUid": "" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if message was sent successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SendMessage',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SendMessage',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Sends message.
	 * @param int $AccountID Account identifier.
	 * @param string $Fetcher Fetcher object is filled in by subscription. Webclient sends FetcherID parameter.
	 * @param int $IdentityID Identity identifier.
	 * @param array $DraftInfo Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
	 * @param string $DraftUid Uid of message to save in Drafts folder.
	 * @param string $To Message recipients.
	 * @param string $Cc Recipients which will get a copy of the message.
	 * @param string $Bcc Recipients which will get a hidden copy of the message.
	 * @param string $Subject Subject of the message.
	 * @param string $Text Text of the message.
	 * @param boolean $IsHtml Indicates if text of the message is HTML or plain.
	 * @param int $Importance Importance of the message - LOW = 5, NORMAL = 3, HIGH = 1.
	 * @param boolean $SendReadingConfirmation Indicates if it is necessary to include header that says
	 * @param array $Attachments List of attachments.
	 * @param string $InReplyTo Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.
	 * @param string $References Content of References header block of the message.
	 * @param int $Sensitivity Sensitivity header for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal". 
	 * @param string $SentFolder Full name of Sent folder.
	 * @param string $DraftFolder Full name of Drafts folder.
	 * @param string $ConfirmFolder Full name of folder that contains a message that should be marked as confirmed read.
	 * @param string $ConfirmUid Uid of message that should be marked as confirmed read.
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SendMessage($AccountID, $Fetcher = null, $IdentityID = 0, 
			$DraftInfo = [], $DraftUid = "", $To = "", $Cc = "", $Bcc = "", 
			$Subject = "", $Text = "", $IsHtml = false, $Importance = \MailSo\Mime\Enumerations\MessagePriority::NORMAL, 
			$SendReadingConfirmation = false, $Attachments = array(), $InReplyTo = "", 
			$References = "", $Sensitivity = \MailSo\Mime\Enumerations\Sensitivity::NOTHING, $SentFolder = "",
			$DraftFolder = "", $ConfirmFolder = "", $ConfirmUid = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		$oIdentity = $IdentityID !== 0 ? $this->getIdentitiesManager()->getIdentity($IdentityID) : null;

		$oMessage = $this->Decorator()->BuildMessage($oAccount, $To, $Cc, $Bcc, 
			$Subject, $IsHtml, $Text, $Attachments, $DraftInfo, $InReplyTo, $References, $Importance,
			$Sensitivity, $SendReadingConfirmation, $Fetcher, false, $oIdentity);
		if ($oMessage)
		{
			$mResult = $this->getMailManager()->sendMessage($oAccount, $oMessage, $Fetcher, $SentFolder, $DraftFolder, $DraftUid);

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
							$this->getMailManager()->setMessageFlag($oAccount,
								$sDraftInfoFolder, array($sDraftInfoUid),
								\MailSo\Imap\Enumerations\MessageFlag::ANSWERED,
								\Aurora\Modules\Mail\Enums\MessageStoreAction::Add);
							break;
						case 'forward':
							$this->getMailManager()->setMessageFlag($oAccount,
								$sDraftInfoFolder, array($sDraftInfoUid),
								'$Forwarded',
								\Aurora\Modules\Mail\Enums\MessageStoreAction::Add);
							break;
					}
				}
				catch (\Exception $oException) {}
			}
			
			if (0 < \strlen($ConfirmFolder) && 0 < \strlen($ConfirmUid))
			{
				try
				{
					$mResult = $this->getMailManager()->setMessageFlag($oAccount, $ConfirmFolder, array($ConfirmUid), '$ReadConfirm', 
						\Aurora\Modules\Mail\Enums\MessageStoreAction::Add, false, true);
				}
				catch (\Exception $oException) {}
			}
		}

		\Aurora\System\Api::LogEvent('message-send: ' . $oAccount->Email, self::GetName());
		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ SetupSystemFolders
	 * @apiName SetupSystemFolders
	 * @apiGroup Mail
	 * @apiDescription Sets up new values of special folders.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SetupSystemFolders} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Sent** *string* New value of Sent folder full name.<br>
	 * &emsp; **Drafts** *string* New value of Drafts folder full name.<br>
	 * &emsp; **Trash** *string* New value of Trash folder full name.<br>
	 * &emsp; **Spam** *string* New value of Spam folder full name.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetupSystemFolders',
	 *	Parameters: '{ "AccountID": 12, "Sent": "Sent", "Drafts": "Drafts", "Trash": "Trash", "Spam": "Spam" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if system folders were set up successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetupSystemFolders',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetupSystemFolders',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Sets up new values of special folders.
	 * @param int $AccountID Account identifier.
	 * @param string $Sent New value of Sent folder full name.
	 * @param string $Drafts New value of Drafts folder full name.
	 * @param string $Trash New value of Trash folder full name.
	 * @param string $Spam New value of Spam folder full name.
	 * @return boolean
	 */
	public function SetupSystemFolders($AccountID, $Sent, $Drafts, $Trash, $Spam)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
		
		$aSystemNames = array();
		$aSystemNames[\Aurora\Modules\Mail\Enums\FolderType::Sent] = \trim($Sent);
		$aSystemNames[\Aurora\Modules\Mail\Enums\FolderType::Drafts] = \trim($Drafts);
		$aSystemNames[\Aurora\Modules\Mail\Enums\FolderType::Trash] = \trim($Trash);
		$aSystemNames[\Aurora\Modules\Mail\Enums\FolderType::Spam] = \trim($Spam);
		
		return $this->getMailManager()->updateSystemFolderNames($oAccount, $aSystemNames);
	}	
	
	/**
	 * Marks (or unmarks) folder as template folder.
	 * @param int $AccountID Account identifier.
	 * @param string $FolderFullName Full name of folder that should be marked/unmarked as template.
	 * @param boolean $SetTemplate Indicates if template should be set or unset.
	 * @return boolean
	 */
	public function SetTemplateFolderType($AccountID, $FolderFullName, $SetTemplate)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('AllowTemplateFolders', false))
		{
			$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

			return $this->getMailManager()->setSystemFolder($oAccount, $FolderFullName, \Aurora\Modules\Mail\Enums\FolderType::Template, $SetTemplate);
		}
		
		return false;
	}

	/**
	 * @api {post} ?/Api/ SetEmailSafety
	 * @apiName SetEmailSafety
	 * @apiGroup Mail
	 * @apiDescription Marks sender email as safety for authenticated user. So pictures in messages from this sender will be always displayed.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SetEmailSafety} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Email** *string* Sender email.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetEmailSafety',
	 *	Parameters: '{ "AccountID": 12, "Email": "test@email" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if email was marked as safety successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetEmailSafety',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SetEmailSafety',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Marks sender email as safety for authenticated user. So pictures in messages from this sender will be always displayed.
	 * @param int $AccountID Account identifier.
	 * @param string $Email Sender email.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SetEmailSafety($AccountID, $Email)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if (0 === \strlen(\trim($Email)))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		$this->getMailManager()->setSafetySender($oAccount->IdUser, $Email);

		return true;
	}	
	
	/**
	 * @api {post} ?/Api/ CreateIdentity
	 * @apiName CreateIdentity
	 * @apiGroup Mail
	 * @apiDescription Creates identity.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=CreateIdentity} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **UserId** *int* (optional) User identifier.<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **FriendlyName** *string* Identity friendly name.<br>
	 * &emsp; **Email** *string* Identity email.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateIdentity',
	 *	Parameters: '{ "AccountID": 12, "FriendlyName": "My name", "Email": "test@email" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Identifier of created identity in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateIdentity',
	 *	Result: 14
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'CreateIdentity',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Creates identity.
	 * @param int $UserId User identifier.
	 * @param int $AccountID Account identifier.
	 * @param string $FriendlyName Identity friendly name.
	 * @param string $Email Identity email.
	 * @return int|boolean
	 */
	public function CreateIdentity($UserId, $AccountID, $FriendlyName, $Email)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->getIdentitiesManager()->createIdentity($UserId, $AccountID, $FriendlyName, $Email);
	}
	
	/**
	 * @api {post} ?/Api/ UpdateIdentity
	 * @apiName UpdateIdentity
	 * @apiGroup Mail
	 * @apiDescription Updates identity.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateIdentity} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **UserId** *int* (optional) User identifier.<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **EntityId** *int* Identity identifier.<br>
	 * &emsp; **FriendlyName** *string* New value of identity friendly name.<br>
	 * &emsp; **Email** *string* New value of identity email.<br>
	 * &emsp; **Default** *boolean* Indicates if identity should be used by default.<br>
	 * &emsp; **AccountPart** *boolean* Indicated if account should be updated, not any identity.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateIdentity',
	 *	Parameters: '{ "AccountID": 12, "EntityId": 14, "FriendlyName": "New my name", "Email": "test@email",
	 *		"Default": false, "AccountPart": false }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if identity was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateIdentity',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateIdentity',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates identity.
	 * @param int $UserId User identifier.
	 * @param int $AccountID Account identifier.
	 * @param int $EntityId Identity identifier.
	 * @param string $FriendlyName New value of identity friendly name.
	 * @param string $Email New value of identity email.
	 * @param boolean $Default Indicates if identity should be used by default.
	 * @param boolean $AccountPart Indicated if account should be updated, not any identity.
	 * @return boolean
	 */
	public function UpdateIdentity($UserId, $AccountID, $EntityId, $FriendlyName, $Email, $Default = false, $AccountPart = false)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($Default)
		{
			$this->getIdentitiesManager()->resetDefaultIdentity($UserId, $AccountID);
		}
		
		if ($AccountPart)
		{
			return $this->UpdateAccount($AccountID, null, $Email, $FriendlyName);
		}
		else
		{
			return $this->getIdentitiesManager()->updateIdentity($EntityId, $FriendlyName, $Email, $Default);
		}
	}
	
	/**
	 * @api {post} ?/Api/ DeleteIdentity
	 * @apiName DeleteIdentity
	 * @apiGroup Mail
	 * @apiDescription Deletes identity.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=DeleteIdentity} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **EntityId** *int* Identity identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteIdentity',
	 *	Parameters: '{ "EntityId": 14 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if identity was deleted successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteIdentity',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'DeleteIdentity',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Deletes identity.
	 * @param int $EntityId Identity identifier.
	 * @return boolean
	 */
	public function DeleteIdentity($EntityId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->getIdentitiesManager()->deleteIdentity($EntityId);
	}
	
	/**
	 * @api {post} ?/Api/ GetIdentities
	 * @apiName GetIdentities
	 * @apiGroup Mail
	 * @apiDescription Obtaines all identities of specified user.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetServers} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **UserId** *int* (optional) User identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetIdentities'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List identities in case of success, otherwise **false**.
	 * @apiSuccess {int} Result.Result.EntityId Identity identifier.
	 * @apiSuccess {int} Result.Result.UUID Identity UUID.
	 * @apiSuccess {int} Result.Result.IdUser User identifier.
	 * @apiSuccess {int} Result.Result.IdAccount Identifier of account owns identity.
	 * @apiSuccess {int} Result.Result.Default Indicates if signature should be used as default on compose screen.
	 * @apiSuccess {int} Result.Result.Email Identity email.
	 * @apiSuccess {int} Result.Result.FriendlyName Identity friendly name.
	 * @apiSuccess {boolean} Result.Result.UseSignature Indicates if signature should be used in outgoing mails.
	 * @apiSuccess {string} Result.Result.Signature Identity signature.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetIdentities',
	 *	Result: [ { "EntityId": 14, "UUID": "uuid_value", "IdUser": 3, "IdAccount": 12,
	 *				"Default": false, "Email": "test@email", "FriendlyName": "My name",
	 *				"UseSignature": true, "Signature": "signature_value" },
	 *			  ... ]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetIdentities',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtaines all identities of specified user.
	 * @param int $UserId User identifier.
	 * @return array|false
	 */
	public function GetIdentities($UserId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->getIdentitiesManager()->getIdentities($UserId);
	}
	
	/**
	 * @api {post} ?/Api/ UpdateSignature
	 * @apiName UpdateSignature
	 * @apiGroup Mail
	 * @apiDescription Updates signature.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateSignature} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **UseSignature** *boolean* Indicates if signature should be used in outgoing mails.<br>
	 * &emsp; **Signature** *string* Account or identity signature.<br>
	 * &emsp; **IdentityId** *int* (optional) Identity identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateSignature',
	 *	Parameters: '{ "AccountID": 12, "UseSignature": true, "Signature": "signature_value", "IdentityId": 14 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if signature was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateSignature',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateSignature',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates signature.
	 * @param int $AccountID Account identifier.
	 * @param boolean $UseSignature Indicates if signature should be used in outgoing mails.
	 * @param string $Signature Account or identity signature.
	 * @param int $IdentityId Identity identifier.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function UpdateSignature($AccountID, $UseSignature = null, $Signature = null, $IdentityId = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($AccountID > 0)
		{
			if ($this->getConfig('AllowIdentities', false) && $IdentityId !== null)
			{
				return $this->getIdentitiesManager()->updateIdentitySignature($IdentityId, $UseSignature, $Signature);
			}
			else
			{
				$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

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

					return $this->getAccountsManager()->updateAccount($oAccount);
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
	 * @api {post} ?/Api/ UploadAttachment
	 * @apiName UploadAttachment
	 * @apiGroup Mail
	 * @apiDescription Uploads attachment.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UploadAttachment} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **UserId** *int* (optional) User identifier.<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * }
	 * @apiParam {string} UploadData Data of uploaded file.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UploadAttachment',
	 *	Parameters: '{ "AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Attachment properties in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UploadAttachment',
	 *	Result: { "Attachment": { "Name": "name.txt", "TempName": "temp_name_value", "MimeType": "text/plain",
	 * "Size": 14, "Hash": "hash_value", "Actions": { "view": { "url": "url_value" },
	 * "download": { "url": "url_value" } }, "ThumbnailUrl": "" } }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UploadAttachment',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Uploads attachment.
	 * @param int $UserId User identifier.
	 * @param int $AccountID Account identifier.
	 * @param array $UploadData Information about uploaded file.
	 * @return array
	 */
	public function UploadAttachment($UserId, $AccountID, $UploadData)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$sUUID = \Aurora\System\Api::getUserUUIDById($UserId);
		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
		
		$sError = '';
		$aResponse = array();

		if ($oAccount instanceof \Aurora\Modules\Mail\Classes\Account)
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
					if ($this->getFilecacheManager()->moveUploadedFile($sUUID, $sSavedName, $UploadData['tmp_name']))
					{
						$rData = $this->getFilecacheManager()->getFile($sUUID, $sSavedName);
					}
				}
				if ($rData)
				{
					$sUploadName = $UploadData['name'];
					$iSize = $UploadData['size'];
					$aResponse['Attachment'] = \Aurora\System\Utils::GetClientFileResponse(
						null, $UserId, $sUploadName, $sSavedName, $iSize
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
	
	/**
	 * @api {post} ?/Api/ SaveAttachmentsAsTempFiles
	 * @apiName SaveAttachmentsAsTempFiles
	 * @apiGroup Mail
	 * @apiDescription Retrieves attachments from message and saves them as files in temporary folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SaveAttachmentsAsTempFiles} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Attachments** *array* List of attachments hashes.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveAttachmentsAsTempFiles',
	 *	Parameters: '{ "Attachments": [ "hash_value" ], "AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Attachments' properties in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveAttachmentsAsTempFiles',
	 *	Result: { "temp_name_value": "hash_value" }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveAttachmentsAsTempFiles',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Retrieves attachments from message and saves them as files in temporary folder.
	 * @param int $AccountID Account identifier.
	 * @param array $Attachments List of attachments hashes.
	 * @return array|boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SaveAttachmentsAsTempFiles($AccountID, $Attachments = array())
	{
		$mResult = false;
		$self = $this;

		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
		if ($oAccount instanceof \Aurora\Modules\Mail\Classes\Account)
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
							if (!$this->getFilecacheManager()->isFileExists($sUUID, $sTempName))
							{
								$this->getMailManager()->directMessageToStream($oAccount,
									function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($sUUID, &$mResult, $sTempName, $sAttachment, $self) {
										if (is_resource($rResource))
										{
											$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
											$sFileName = \Aurora\System\Utils::clearFileName($sFileName, $sContentType, $sMimeIndex);

											if ($self->getFilecacheManager()->putFile($sUUID, $sTempName, $rResource))
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
				throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotConnectToMailServer, $oException, $oException->getMessage());
			}
		}

		return $mResult;
	}	
	
	/**
	 * @api {post} ?/Api/ SaveMessageAsTempFile
	 * @apiName SaveMessageAsTempFile
	 * @apiGroup Mail
	 * @apiDescription Retrieves message and saves it as .eml file in temporary folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=SaveMessageAsTempFile} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **MessageFolder** *string* Full name of folder.<br>
	 * &emsp; **MessageUid** *string* Message uid.<br>
	 * &emsp; **FileName** *string* Name of created .eml file.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveMessageAsTempFile',
	 *	Parameters: '{ "MessageFolder": "INBOX", "MessageUid": "1691", "FileName": "subject.eml",
	 *		"AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result .eml attachment properties in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveMessageAsTempFile',
	 *	Result: { "Name": "subject.eml", "FileName": "subject.eml", "TempName":"temp_name_value",
	 * "MimeType": "message/rfc822", "Size": 1669, "Hash": "hash_value",
	 * "Actions": { "view": { "url": "view_url" }, "download": { "url": "download_url" } },
	 * "ThumbnailUrl": "" } }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'SaveMessageAsTempFile',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Retrieves message and saves it as .eml file in temporary folder.
	 * @param int $AccountID Account identifier.
	 * @param string $MessageFolder Full name of folder.
	 * @param string $MessageUid Message uid.
	 * @param string $FileName Name of created .eml file.
	 * @return array|boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SaveMessageAsTempFile($AccountID, $MessageFolder, $MessageUid, $FileName)
	{
		$mResult = false;
		$self = $this;

		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);
		if ($oAccount instanceof \Aurora\Modules\Mail\Classes\Account)
		{
			$sUUID = \Aurora\System\Api::getUserUUIDById($oAccount->IdUser);
			try
			{
				$sMimeType = 'message/rfc822';
				$sTempName = md5($MessageFolder.$MessageUid);
				if (!$this->getFilecacheManager()->isFileExists($sUUID, $sTempName))
				{
					$this->getMailManager()->directMessageToStream($oAccount,
						function ($rResource, $sContentType, $sFileName) use ($sUUID, $sTempName, &$sMimeType, $self) {
							if (is_resource($rResource))
							{
								$sMimeType = $sContentType;
								$sFileName = \Aurora\System\Utils::clearFileName($sFileName, $sMimeType, '');
								$self->getFilecacheManager()->putFile($sUUID, $sTempName, $rResource);
							}
						}, $MessageFolder, $MessageUid);
				}

				if ($this->getFilecacheManager()->isFileExists($sUUID, $sTempName))
				{
					$iSize = $this->getFilecacheManager()->fileSize($sUUID, $sTempName);
					$mResult = \Aurora\System\Utils::GetClientFileResponse(
						null, $oAccount->IdUser, $FileName, $sTempName, $iSize
					);
				}
			}
			catch (\Exception $oException)
			{
				throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotConnectToMailServer, $oException, $oException->getMessage());
			}
		}

		return $mResult;
	}	
	
	/**
	 * @api {post} ?/Api/ UploadMessage
	 * @apiName UploadMessage
	 * @apiGroup Mail
	 * @apiDescription Uploads message and puts it to specified folder.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UploadMessage} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Folder full name.<br>
	 * }
	 * @apiParam {string} UploadData Information about uploaded .eml file.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UploadMessage',
	 *	Parameters: '{ "AccountID": 12, "Folder": "INBOX" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if message was uploaded successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UploadMessage',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UploadMessage',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Uploads message and puts it to specified folder.
	 * @param int $AccountID Account identifier.
	 * @param string $Folder Folder full name.
	 * @param array $UploadData Information about uploaded .eml file.
	 * @return boolean
	 * @throws \ProjectCore\Exceptions\ClientException
	 */
	public function UploadMessage($AccountID, $Folder, $UploadData)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$bResult = false;

		$oAccount = $this->getAccountsManager()->getAccountById((int)$AccountID);
		
		if ($oAccount)
		{
			$sUUID = \Aurora\System\Api::getUserUUIDById($oAccount->IdUser);
			if (is_array($UploadData))
			{
				$sUploadName = $UploadData['name'];
				$bIsEmlExtension  = strtolower(pathinfo($sUploadName, PATHINFO_EXTENSION)) === 'eml';

				if ($bIsEmlExtension) 
				{
					$sSavedName = 'upload-post-' . md5($UploadData['name'] . $UploadData['tmp_name']);
					if (is_resource($UploadData['tmp_name']))
					{
						$this->getFilecacheManager()->putFile($sUUID, $sSavedName, $UploadData['tmp_name']);
					}
					else
					{
						$this->getFilecacheManager()->moveUploadedFile($sUUID, $sSavedName, $UploadData['tmp_name']);
					}
					if ($this->getFilecacheManager()->isFileExists($sUUID, $sSavedName))
					{
						$sSavedFullName = $this->getFilecacheManager()->generateFullFilePath($sUUID, $sSavedName);
						try
						{
							$this->getMailManager()->appendMessageFromFile($oAccount, $sSavedFullName, $Folder);
							$bResult = true;
						}
						catch (\Exception $oException)
						{
							throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotUploadMessage, $oException, $oException->getMessage());
						}
					} 
					else 
					{
						throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UnknownError);
					}
				}
				else
				{
					throw new \Aurora\Modules\Mail\Exceptions\Exception(Enums\ErrorCodes::CannotUploadMessageFileNotEml);
				}
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
		}

		return $bResult;
	}
	
	/**
	 * @api {post} ?/Api/ ChangePassword
	 * @apiName ChangePassword
	 * @apiGroup Mail
	 * @apiDescription Changes account password.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=ChangePassword} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountId** *int* Account identifier.<br>
	 * &emsp; **CurrentPassword** *string* Current password.<br>
	 * &emsp; **NewPassword** *string* New password.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'ChangePassword',
	 *	Parameters: '{ "AccountId": 12, "CurrentPassword": "pass_value", "NewPassword": "new_pass_value" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if password was changed successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'ChangePassword',
	 *	Result: true
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'ChangePassword',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * This method will trigger some event, subscribers of which perform all change password process
	 * 
	 * @param int $AccountId Account identifier.
	 * @param string $CurrentPassword Current password.
	 * @param string $NewPassword New password.
	 * @return boolean
	 */
	public function ChangePassword($AccountId, $CurrentPassword, $NewPassword)
	{
		$mResult = false;

		if ($AccountId > 0)
		{
			$oAccount = $this->getAccountsManager()->getAccountById($AccountId);
			$oUser = \Aurora\System\Api::getAuthenticatedUser();
			if ($oAccount instanceof \Aurora\Modules\Mail\Classes\Account &&
				$oUser instanceof \Aurora\Modules\Core\Classes\User &&
				$oAccount->getPassword() === $CurrentPassword &&
				(($oUser->Role === \Aurora\System\Enums\UserRole::NormalUser && $oUser->EntityId === $oAccount->IdUser) ||
				$oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin)
			)
			{
				$oAccount->setPassword($NewPassword);
				if ($this->getAccountsManager()->updateAccount($oAccount))
				{
					$mResult = $oAccount;
					$mResult->RefreshToken = \Aurora\System\Api::UserSession()->UpdateTimestamp(\Aurora\System\Api::getAuthToken(), time());
				}
			}
		}

		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ GetFilters
	 * @apiName GetFilters
	 * @apiGroup Mail
	 * @apiDescription Obtains filters for specified account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetFilters} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetFilters',
	 *	Parameters: '{ "AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List of filters in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetFilters',
	 *	Result: []
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetFilters',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains filters for specified account.
	 * @param int $AccountID Account identifier.
	 * @return array|boolean
	 */
	public function GetFilters($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->getAccountsManager()->getAccountById((int) $AccountID);
		
		if ($oAccount)
		{
			$mResult = $this->getSieveManager()->getSieveFilters($oAccount);
		}
		
		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateFilters
	 * @apiName UpdateFilters
	 * @apiGroup Mail
	 * @apiDescription Updates filters.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateFilters} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier<br>
	 * &emsp; **Filters** *array* New filters data.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateFilters',
	 *	Parameters: '{ "AccountID": 12, "Filters": [ { "Enable": "1", "Field": 0, "Filter": "test", 
	 *			"Condition": 0, "Action": 3, "FolderFullName": "test" } ] }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if filters were updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateFilters',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateFilters',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates filters.
	 * @param int $AccountID Account identifier
	 * @param array $Filters New filters data.
	 * @return boolean
	 */
	public function UpdateFilters($AccountID, $Filters)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$bResult = false;
		
		$oAccount = $this->getAccountsManager()->getAccountById((int) $AccountID);

		if ($oAccount)
		{
			$aFilters = array();
			
			if (is_array($Filters))
			{
				foreach ($Filters as $aFilterData)
				{
					$oFilter = $this->getSieveManager()->createFilterInstance($oAccount, $aFilterData);
						
					if ($oFilter)
					{
						$aFilters[] = $oFilter;
					}
				}
			}
			
			$bResult = $this->getSieveManager()->updateSieveFilters($oAccount, $aFilters);
		}
		
		return $bResult;
	}
	
	/**
	 * @api {post} ?/Api/ GetForward
	 * @apiName GetForward
	 * @apiGroup Mail
	 * @apiDescription Obtains forward data for specified account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetForward} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetForward',
	 *	Parameters: '{ "AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Forward properties in case of success, otherwise **false**.
	 * @apiSuccess {boolean} Result.Result.Enable Indicates if forward is enabled.
	 * @apiSuccess {string} Result.Result.Email Forward email.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetForward',
	 *	Result: { "Enable": false, "Email": "" }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetForward',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains forward data for specified account.
	 * @param int $AccountID Account identifier.
	 * @return array|boolean
	 */
	public function GetForward($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->getAccountsManager()->getAccountById((int) $AccountID);
		
		if ($oAccount)
		{
			$mResult = $this->getSieveManager()->getForward($oAccount);
		}

		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateForward
	 * @apiName UpdateForward
	 * @apiGroup Mail
	 * @apiDescription Updates forward.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateForward} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Enable** *boolean* Indicates if forward is enabled.<br>
	 * &emsp; **Email** *string* Email that should be used for message forward.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateForward',
	 *	Parameters: '{ "AccountID": 12, "Enable": true, "Email": "test2@email" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if server was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateForward',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateForward',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates forward.
	 * @param int $AccountID Account identifier.
	 * @param boolean $Enable Indicates if forward is enabled.
	 * @param string $Email Email that should be used for message forward.
	 * @return boolean
	 */
	public function UpdateForward($AccountID, $Enable = false, $Email = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->getAccountsManager()->getAccountById((int) $AccountID);

		if ($oAccount && $Email !== "")
		{
			$mResult = $this->getSieveManager()->setForward($oAccount, $Email, $Enable);
		}
		
		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ GetAutoresponder
	 * @apiName GetAutoresponder
	 * @apiGroup Mail
	 * @apiDescription Obtains autoresponder for specified account.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=GetAutoresponder} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAutoresponder',
	 *	Parameters: '{ "AccountID": 12 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Autoresponder properties in case of success, otherwise **false**.
	 * @apiSuccess {boolean} Result.Result.Enable Indicates if autoresponder is enabled.
	 * @apiSuccess {string} Result.Result.Subject Subject of auto-respond message.
	 * @apiSuccess {string} Result.Result.Message Text of auto-respond message.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAutoresponder',
	 *	Result: { Enable: false, Subject: "", Message: "" }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'GetAutoresponder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains autoresponder for specified account.
	 * @param int $AccountID Account identifier.
	 * @return array|boolean
	 */
	public function GetAutoresponder($AccountID)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->getAccountsManager()->getAccountById((int) $AccountID);
		
		if ($oAccount)
		{
			$mResult = $this->getSieveManager()->getAutoresponder($oAccount);
		}

		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateAutoresponder
	 * @apiName UpdateAutoresponder
	 * @apiGroup Mail
	 * @apiDescription Updates autoresponder data.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=Mail} Module Module name
	 * @apiParam {string=UpdateAutoresponder} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountID** *int* Account identifier.<br>
	 * &emsp; **Enable** *boolean* Indicates if autoresponder is enabled.<br>
	 * &emsp; **Subject** *string* Subject of auto-respond message.<br>
	 * &emsp; **Message** *string* Text of auto-respond message.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateAutoresponder',
	 *	Parameters: '{ "AccountID": 12, "Enable": true, "Subject": "subject_value", "Text": "text_value" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if Autoresponder was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateAutoresponder',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Mail',
	 *	Method: 'UpdateAutoresponder',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates autoresponder data.
	 * @param int $AccountID Account identifier.
	 * @param boolean $Enable Indicates if autoresponder is enabled.
	 * @param string $Subject Subject of auto-respond message.
	 * @param string $Message Text of auto-respond message.
	 * @return boolean
	 */
	public function UpdateAutoresponder($AccountID, $Enable = false, $Subject = "", $Message = "")
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$mResult = false;
		
		$oAccount = $this->getAccountsManager()->getAccountById((int) $AccountID);

		if ($oAccount && ($Subject !== "" || $Message !== ""))
		{
			$mResult = $this->getSieveManager()->setAutoresponder($oAccount, $Subject, $Message, $Enable);
		}
		
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
		$mResult = $this->getAccountsManager()->getUserAccounts($iUserId);
		
		if (\is_array($mResult))
		{
			foreach($mResult as $oItem)
			{
				$this->Decorator()->DeleteAccount($oItem->EntityId);
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
		$iUserId = 0;
		
		$sEmail = $aArgs['Login'];
		$sMailLogin = $aArgs['Login'];
		$oAccount = $this->getAccountsManager()->getAccountUsedToAuthorize($sEmail);
		
		$bNewAccount = false;
		$bAutocreateMailAccountOnNewUserFirstLogin = $this->getConfig('AutocreateMailAccountOnNewUserFirstLogin', false);
		if (!$bAutocreateMailAccountOnNewUserFirstLogin && !$oAccount)
		{
			$oUser = \Aurora\System\Api::GetModuleDecorator('Core')->GetUserByPublicId($sEmail);
			if ($oUser instanceof \Aurora\Modules\Core\Classes\User)
			{
				$bAutocreateMailAccountOnNewUserFirstLogin = true;
			}
		}
		
		if ($bAutocreateMailAccountOnNewUserFirstLogin && !$oAccount)
		{
			$sDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
			if (!empty(trim($sDomain)))
			{
				$oServer = $this->getServersManager()->GetServerByDomain(strtolower($sDomain));
				if (!$oServer)
				{
					$oServer = $this->getServersManager()->GetServerByDomain('*');
				}

				if ($oServer)
				{
					$sMailLogin = !$oServer->UseFullEmailAddressAsLogin && preg_match('/(.+)@.+$/',  $sEmail, $matches) && $matches[1] ? $matches[1] : $sEmail;

					$oAccount = new Classes\Account(self::GetName());
					$oAccount->Email = $sEmail;
					$oAccount->IncomingLogin = $sMailLogin;
					$oAccount->setPassword($aArgs['Password']);
					$oAccount->ServerId = $oServer->EntityId;
					$bNewAccount = true;
				}
				else
				{
					throw new \Aurora\System\Exceptions\ApiException(
						Enums\ErrorCodes::DomainIsNotAllowedForLoggingIn,
						null,
						'',
						[],
						$this
					);
				}
			}
		}
		
		if ($oAccount instanceof \Aurora\Modules\Mail\Classes\Account)
		{
			try
			{
				if ($bAutocreateMailAccountOnNewUserFirstLogin || !$bNewAccount)
				{
					$sOldPassword = $oAccount->getPassword();
					$sNewPassword = $aArgs['Password'];

					if ($sNewPassword !== $sOldPassword)
					{
						$oAccount->setPassword($sNewPassword);
						$mResult = $this->getMailManager()->validateAccountConnection($oAccount, false);
						if (!($mResult instanceof \Exception))
						{
							$bResult = true;
							\Aurora\System\Api::setUserId($oAccount->IdUser); // set user id to yhe Session
							$this->Decorator()->ChangePassword($oAccount->EntityId, $sOldPassword, $sNewPassword);
						}
						else
						{
							$bResult = false;
						}
					}
					else
					{
						$bResult = true;
					}
				}

				if ($bAutocreateMailAccountOnNewUserFirstLogin && $bNewAccount)
				{
					$oUser = null;
					$aSubArgs = array(
						'UserName' => $sEmail,
						'Email' => $sEmail,
						'UserId' => $iUserId
					);
					$this->broadcastEvent(
						'CreateAccount', 
						$aSubArgs,
						$oUser
					);

					if ($oUser instanceof \Aurora\Modules\Core\Classes\User)
					{
						$iUserId = $oUser->EntityId;
						$bPrevState = \Aurora\System\Api::skipCheckUserRole(true);
						$oAccount = $this->Decorator()->CreateAccount(
							$iUserId, 
							'',
							$sEmail,
							$sMailLogin,
							$aArgs['Password'], 
							array('ServerId' => $oServer->EntityId)
						);
						\Aurora\System\Api::skipCheckUserRole($bPrevState);
						if ($oAccount)
						{
							$oAccount->UseToAuthorize = true;
							$oAccount->UseThreading = $oServer->EnableThreading;
							$bResult = $this->getAccountsManager()->updateAccount($oAccount);
						}
						else
						{
							$bResult = false;
						}
					}
				}
				
				if ($bResult)
				{
					$mResult = array(
						'token' => 'auth',
						'sign-me' => $aArgs['SignMe'],
						'id' => $oAccount->IdUser,
						'account' => $oAccount->EntityId,
						'account_type' => $oAccount->getName()
					);
				}
			}
			catch (\Aurora\System\Exceptions\ApiException $oException)
			{
				throw $oException;
			}
			catch (\Exception $oException) {}
		}			

		return $bResult;
	}
	
	/**
	 * 
	 * @param array $aArgs
	 * @param array $aResult
	 */
	public function onGetAccounts($aArgs, &$aResult)
	{
		$bWithPassword = $aArgs['WithPassword'];
		$aUserInfo = \Aurora\System\Api::getAuthenticatedUserInfo($aArgs['AuthToken']);
		if (isset($aUserInfo['userId']))
		{
			$mResult = $this->GetAccounts($aUserInfo['userId']);
			if (\is_array($mResult))
			{
				foreach($mResult as $oItem)
				{
					$aItem = array(
						'Type' => $oItem->getName(),
						'Module' => $oItem->getModule(),
						'Id' => $oItem->EntityId,
						'UUID' => $oItem->UUID,
						'Login' => $oItem->IncomingLogin
					);
					if ($bWithPassword)
					{
						$aItem['Password'] = $oItem->getPassword();
					}
					$aResult[] = $aItem;
				}
			}
		}
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

		$oAccount = $this->getAccountsManager()->getAccountById($AccountID);

		return $this->getMailManager()->setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagName,
			$bSetAction ? \Aurora\Modules\Mail\Enums\MessageStoreAction::Add : \Aurora\Modules\Mail\Enums\MessageStoreAction::Remove);
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
	 * Builds message for further sending or saving.
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param string $sTo Message recipients.
	 * @param string $sCc Recipients which will get a copy of the message.
	 * @param string $sBcc Recipients which will get a hidden copy of the message.
	 * @param string $sSubject Subject of the message.
	 * @param boolean $bTextIsHtml Indicates if text of the message is HTML or plain.
	 * @param string $sText Text of the message.
	 * @param array $aAttachments List of attachments.
	 * @param array $aDraftInfo Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
	 * @param string $sInReplyTo Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.
	 * @param string $sReferences Content of References header block of the message.
	 * @param int $iImportance Importance of the message - LOW = 5, NORMAL = 3, HIGH = 1.
	 * @param int $iSensitivity Sensitivity header for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal". 
	 * @param boolean $bSendReadingConfirmation Indicates if it is necessary to include header that says
	 * @param \Aurora\Modules\Mail\Classes\Fetcher $oFetcher
	 * @param boolean $bWithDraftInfo
	 * @param \Aurora\Modules\Mail\Classes\Identity $oIdentity
	 * @return \MailSo\Mime\Message
	 */
	public function BuildMessage($oAccount, $sTo = '', $sCc = '', $sBcc = '', 
			$sSubject = '', $bTextIsHtml = false, $sText = '', $aAttachments = null, 
			$aDraftInfo = null, $sInReplyTo = '', $sReferences = '', $iImportance = '',
			$iSensitivity = 0, $bSendReadingConfirmation = false,
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
		
		$bXOriginatingIP = 	$this->getConfig('XOriginatingIP', false);
		if ($bXOriginatingIP)
		{
			$sIP = $this->oHttp->GetClientIp();
			$oMessage->SetCustomHeader(
				\MailSo\Mime\Enumerations\Header::X_ORIGINATING_IP,
				$this->oHttp->IsLocalhost($sIP) ? '127.0.0.1' : $sIP
			);			
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
		
		if (\in_array($iImportance, array(
			\MailSo\Mime\Enumerations\MessagePriority::HIGH,
			\MailSo\Mime\Enumerations\MessagePriority::NORMAL,
			\MailSo\Mime\Enumerations\MessagePriority::LOW
		)))
		{
			$oMessage->SetPriority($iImportance);
		}

		if (\in_array($iSensitivity, array(
			\MailSo\Mime\Enumerations\Sensitivity::NOTHING,
			\MailSo\Mime\Enumerations\Sensitivity::CONFIDENTIAL,
			\MailSo\Mime\Enumerations\Sensitivity::PRIVATE_,
			\MailSo\Mime\Enumerations\Sensitivity::PERSONAL,
		)))
		{
			$oMessage->SetSensitivity((int) $iSensitivity);
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

					$rResource = $this->getFilecacheManager()->getFile($sUUID, $sTempName);
					if (\is_resource($rResource))
					{
						$iFileSize = $this->getFilecacheManager()->fileSize($sUUID, $sTempName);

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
					else
					{
						\Aurora\System\Api::Log('Error: there is no temp file for attachment ' . $sFileName);
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
	
	public function onAfterDeleteTenant(&$aArgs, &$mResult)
	{
		$TenantId = $aArgs['TenantId'];
		$aServers = $this->Decorator()->GetServers($TenantId);
		foreach ($aServers as $oServer)
		{
			if ($oServer->TenantId === $TenantId)
			{
				$this->Decorator()->DeleteServer($oServer->EntityId, $TenantId);
			}
		}
	}
	
	public function onAfterGetAutodiscover(&$aArgs, &$mResult)
	{
		$sIncomingServer = \trim($this->getConfig('ExternalHostNameOfLocalImap'));
		$sOutgoingServer = \trim($this->getConfig('ExternalHostNameOfLocalSmtp'));
		$sEmail = $aArgs['Email'];

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
'		<Account>',
'			<AccountType>email</AccountType>',
'			<Action>settings</Action>',
'			<Protocol>',
'				<Type>IMAP</Type>',
'				<Server>'.$sIncomingServer.'</Server>',
'				<LoginName>'.$sEmail.'</LoginName>',
'				<Port>'.$iIncomingPort.'</Port>',
'				<SSL>'.(993 === $iIncomingPort ? 'on' : 'off').'</SSL>',
'				<SPA>off</SPA>',
'				<AuthRequired>on</AuthRequired>',
'			</Protocol>',
'			<Protocol>',
'				<Type>SMTP</Type>',
'				<Server>'.$sOutgoingServer.'</Server>',
'				<LoginName>'.$sEmail.'</LoginName>',
'				<Port>'.$iOutgoingPort.'</Port>',
'				<SSL>'.(465 === $iOutgoingPort ? 'on' : 'off').'</SSL>',
'				<SPA>off</SPA>',
'				<AuthRequired>on</AuthRequired>',
'			</Protocol>',
'		</Account>'
));
			$mResult = $mResult . $sResult;
		}
	}
	
	public function EntryMessageNewtab()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

		$oApiIntegrator = \Aurora\Modules\Core\Managers\Integrator::getInstance();

		if ($oApiIntegrator)
		{
			$aConfig = array(
				'new_tab' => true,
				'modules_list' => $oApiIntegrator->GetModulesForEntry('MailWebclient')
			);

			$oCoreWebclientModule = \Aurora\System\Api::GetModule('CoreWebclient');
			if ($oCoreWebclientModule instanceof \Aurora\System\Module\AbstractModule) 
			{
				$sResult = \file_get_contents($oCoreWebclientModule->GetPath().'/templates/Index.html');
				if (\is_string($sResult)) 
				{
					return strtr($sResult, array(
						'{{AppVersion}}' => AU_APP_VERSION,
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		$this->getRaw(
			(string) \Aurora\System\Application::GetPathItemByIndex(1, ''),
			(string) \Aurora\System\Application::GetPathItemByIndex(2, '')
		);		
	}	
	
	/**
	 * @param string $sHash
	 * @param string $sAction
	 * @return boolean
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
		
		$aValues = \Aurora\System\Api::DecodeKeyValues($sHash);
		
		$sFolder = '';
		$iUid = 0;
		$sMimeIndex = '';

		$oAccount = null;

		$iUserId = (isset($aValues['UserId'])) ? $aValues['UserId'] : 0;
		$sUUID = \Aurora\System\Api::getUserUUIDById($iUserId);

		if (isset($aValues['AccountID']))
		{
			$oAccount = $this->getAccountsManager()->getAccountById((int) $aValues['AccountID']);
			
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
			\Aurora\System\Managers\Response::verifyCacheByKey($sHash);
		}
		
		return $this->getMailManager()->directMessageToStream($oAccount,
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
						\Aurora\System\Managers\Response::cacheByKey($sHash);
					}

					\Aurora\System\Utils::OutputFileResource($sUUID, $sContentType, $sFileName, $rResource, $bThumbnail, $bDownload);
				}
			}, $sFolder, $iUid, $sMimeIndex);
	}	
	/***** private functions *****/
}
