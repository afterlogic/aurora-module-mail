<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Users
 * @subpackage Classes
 */
class Account extends \Aurora\System\Classes\AbstractAccount
{
	private $oServer = null;
	
	protected $aStaticMap = array(
		'IsDisabled'		=> array('bool', false, true),
		'IdUser'			=> array('int', 0, true),
		'UseToAuthorize'	=> array('bool', false, true),
		'Email'				=> array('string', '', true),
		'FriendlyName'		=> array('string', ''),
		'IncomingLogin'		=> array('string', ''),
		'IncomingPassword'	=> array('encrypted', ''),
		'UseSignature'		=> array('bool', false),
		'Signature'			=> array('text', ''),
		'ServerId'			=> array('int',  0),
		'FoldersOrder'		=> array('text', ''),
		'UseThreading'		=> array('bool', false),
		'SaveRepliesToCurrFolder' => array('bool', false),
	);
	
	public function getPassword()
	{
		$sPassword = '';
		if (!$this->IncomingPassword) // TODO: Legacy support
		{
			$sSalt = \Aurora\System\Api::$sSalt;
			\Aurora\System\Api::$sSalt = md5($sSalt);
			$sPassword = $this->IncomingPassword;
			\Aurora\System\Api::$sSalt = $sSalt;
		}
		else
		{
			$sPassword = $this->IncomingPassword;
		}
		
		if ($sPassword !== '' && strpos($sPassword, $this->IncomingLogin . ':') === false)
		{
			$this->setPassword($sPassword);
			\Aurora\System\Api::GetModule('Mail')->getAccountsManager()->updateAccount($this);
		}
		else
		{
			$sPassword = substr($sPassword, strlen($this->IncomingLogin) + 1);
		}
		return $sPassword;
	}
	
	public function setPassword($sPassword)
	{
		$this->IncomingPassword = $this->IncomingLogin . ':' . $sPassword;
	}
			
	public function updateServer($iServerId)
	{
		$this->oServer = null;
		$this->ServerId = $iServerId;
		$this->oServer = $this->getServer();
	}
	
	public function getServer()
	{
		$oMailModule = \Aurora\System\Api::GetModule('Mail');
		if ($this->oServer === null && $this->ServerId !== 0)
		{
			$this->oServer = $oMailModule->getServersManager()->getServer($this->ServerId);
		}
		return $this->oServer;
	}
	
	private function canBeUsedToAuthorize()
	{
		$oMailModule = \Aurora\System\Api::GetModule('Mail');
		return !$oMailModule->getAccountsManager()->useToAuthorizeAccountExists($this->Email, $this->EntityId);
	}
	
	public function getDefaultTimeOffset()
	{
		return 0;
	}
	
	public function toResponseArray()
	{
		$aResponse = parent::toResponseArray();
		$aResponse['AccountID'] = $this->EntityId;
		$aResponse['AllowFilters'] = false;
		$aResponse['AllowForward'] = false;
		$aResponse['AllowAutoresponder'] = false;
		
		$oServer = $this->getServer();
		if ($oServer instanceof \Aurora\System\EAV\Entity)
		{
			$aResponse['Server'] = $oServer->toResponseArray();
			
			$oMailModule = \Aurora\System\Api::GetModule('Mail');
			if ($oServer->EnableSieve && $oMailModule)
			{
				$aResponse['AllowFilters'] = $oMailModule->getConfig('AllowFilters', '');
				$aResponse['AllowForward'] = $oMailModule->getConfig('AllowForward', '');
				$aResponse['AllowAutoresponder'] = $oMailModule->getConfig('AllowAutoresponder', '');
			}
		}
		
		$aResponse['CanBeUsedToAuthorize'] = $this->canBeUsedToAuthorize();
//		unset($aResponse['IncomingPassword']);
		
		$aArgs = ['Account' => $this];
		\Aurora\System\Api::GetModule('Core')->broadcastEvent(
			'Mail::Account::ToResponseArray',
			$aArgs,
			$aResponse
		);
		
		return $aResponse;
	}

	public function getLogin()
	{
		return $this->IncomingLogin;
	}
}
