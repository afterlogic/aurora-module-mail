<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Users
 * @subpackage Classes
 */
class Account extends \Aurora\System\EAV\Entity
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
			$this->oServer = $oMailModule->oApiServersManager->getServer($this->ServerId);
		}
		return $this->oServer;
	}
	
	private function canBeUsedToAuthorize()
	{
		$oMailModule = \Aurora\System\Api::GetModule('Mail');
		return !$oMailModule->oApiAccountsManager->useToAuthorizeAccountExists($this->Email, $this->EntityId);
	}
	
	public function getDefaultTimeOffset()
	{
		return 0;
	}
	
	public function toResponseArray()
	{
		$aResponse = parent::toResponseArray();
		$aResponse['AccountID'] = $this->EntityId;
		$oServer = $this->getServer();
		if ($oServer instanceof \Aurora\System\EAV\Entity)
		{
			$aResponse['Server'] = $oServer->toResponseArray();
		}
		$aResponse['CanBeUsedToAuthorize'] = $this->canBeUsedToAuthorize();
		unset($aResponse['IncomingPassword']);
		return $aResponse;
	}
}
