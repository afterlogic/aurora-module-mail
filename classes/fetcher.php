<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @property int $IdFetcher
 * @property int $IdAccount
 * @property int $IdUser
 * @property int $IdTenant
 * @property bool $IsEnabled
 * @property bool $IsLocked
 * @property int $CheckInterval
 * @property int $CheckLastTime
 * @property string $Name
 * @property string $Email
 * @property string $Signature
 * @property int $SignatureOptions
 * @property bool $LeaveMessagesOnServer
 * @property string $IncomingServer
 * @property int $IncomingPort
 * @property string $IncomingLogin
 * @property string $IncomingPassword
 * @property int $IncomingMailSecurity
 * @property bool $IsOutgoingEnabled
 * @property string $OutgoingServer
 * @property int $OutgoingPort
 * @property bool $OutgoingUseAuth
 * @property int $OutgoingMailSecurity
 * @property string $Folder
 *
 * @package Fetchers
 * @subpackage Classes
 */
class CFetcher extends \Aurora\System\AbstractContainer
{
	/**
	 * @param CAccount $oAccount
	 */
	public function __construct(CAccount $oAccount)
	{
		parent::__construct(get_class($this));

		$this->SetTrimer(array('Name', 'Signature', 'IncomingServer', 'IncomingLogin', 'IncomingPassword',
			'OutgoingServer'));

		$this->SetLower(array('IncomingServer', 'OutgoingServer'));

		$this->SetDefaults(array(
			'IdFetcher'				=> 0,
			'IdAccount'				=> $oAccount->EntityId,
			'IdUser'				=> $oAccount->IdUser,
			'IdTenant'				=> $oAccount->IdTenant,
			'IsEnabled'				=> true,
			'IsLocked'				=> false,
			'CheckInterval'			=> 0,
			'CheckLastTime'			=> 0,
			'Name'					=> '',
			'Email'					=> '',
			'Signature'				=> '',
			'SignatureOptions'		=> EAccountSignatureOptions::DontAdd,
			'LeaveMessagesOnServer'	=> true,
			'IncomingServer'		=> '',
			'IncomingPort'			=> 110,
			'IncomingLogin'			=> '',
			'IncomingPassword'		=> '',
			'IncomingMailSecurity'	=> \MailSo\Net\Enumerations\ConnectionSecurityType::NONE,
			'IsOutgoingEnabled'		=> false,
			'OutgoingServer'		=> '',
			'OutgoingPort'			=> 25,
			'OutgoingUseAuth'		=> true,
			'OutgoingMailSecurity'	=>  \MailSo\Net\Enumerations\ConnectionSecurityType::NONE,
			'Folder'				=> 'INBOX'
		));
	}
	
	/**
	 * @return array
	 */
	public function getMap()
	{
		return self::getStaticMap();
	}

	/**
	 * @return array
	 */
	public static function getStaticMap()
	{
		return array(
			'IdFetcher'				=> array('int', 'id_fetcher', false, false),
			'IdAccount'				=> array('int', 'id_acct', true, false),
			'IdUser'				=> array('int', 'id_user', true, false),
			'IdTenant'				=> array('int', 'id_tenant', true, false),

			'IsEnabled'				=> array('bool', 'enabled'),
			'IsLocked'				=> array('bool', 'locked', false, false),
			'CheckInterval'			=> array('int', 'mail_check_interval'),
			'CheckLastTime'			=> array('int', 'mail_check_lasttime', false, false),
			
			'LeaveMessagesOnServer' => array('bool', 'leave_messages'),
			
			'Name'					=> array('string', 'frienly_name'),
			'Email'					=> array('string', 'email'),
			'Signature'				=> array('string', 'signature'),
			'SignatureOptions'		=> array('int', 'signature_opt'),

			'IncomingServer'		=> array('string', 'inc_host'),
			'IncomingPort'			=> array('int', 'inc_port'),
			'IncomingLogin'			=> array('string', 'inc_login'),
			'IncomingPassword'		=> array('string', 'inc_password'),
			'IncomingMailSecurity'	=> array('int', 'inc_security'),

			'IsOutgoingEnabled'		=> array('bool', 'out_enabled'),

			'OutgoingServer'		=> array('string', 'out_host'),
			'OutgoingPort'			=> array('int', 'out_port'),
			'OutgoingUseAuth'		=> array('bool', 'out_auth'),
			'OutgoingMailSecurity'	=> array('int', 'out_security'),

			'Folder'				=> array('string', 'dest_folder')
		);
	}
	
	public function toResponseArray($aParameters = array())
	{
		return array(
			'IdFetcher' => $this->IdFetcher,
			'IdAccount' => $this->IdAccount,
			'IsEnabled' => $this->IsEnabled,
			'IsLocked' => $this->IsLocked,
			'Folder' => $this->Folder,
			'Name' => $this->Name,
			'Email' => $this->Email,
			'Signature' => $this->Signature,
			'SignatureOptions' => $this->SignatureOptions,
			'LeaveMessagesOnServer' => $this->LeaveMessagesOnServer,
			'IncomingServer' => $this->IncomingServer,
			'IncomingPort' => $this->IncomingPort,
			'IncomingLogin' => $this->IncomingLogin,
			'IsOutgoingEnabled' => $this->IsOutgoingEnabled,
			'OutgoingServer' => $this->OutgoingServer,
			'OutgoingPort' => $this->OutgoingPort,
			'OutgoingUseAuth' => $this->OutgoingUseAuth,
			'IncomingUseSsl' => $this->IncomingMailSecurity === \MailSo\Net\Enumerations\ConnectionSecurityType::SSL,
			'OutgoingUseSsl' => $this->OutgoingMailSecurity === \MailSo\Net\Enumerations\ConnectionSecurityType::SSL
		);		
	}
}
