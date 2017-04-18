<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 */

/**
 *
 * @package Users
 * @subpackage Classes
 */
class CMailAccount extends \Aurora\System\EAV\Entity
{
	const ChangePasswordExtension = 'AllowChangePasswordExtension';
	const AutoresponderExtension = 'AllowAutoresponderExtension';
	const DisableAccountDeletion = 'DisableAccountDeletion';
	const DisableManageFolders = 'DisableManageFolders';
	const SieveFiltersExtension = 'AllowSieveFiltersExtension';
	const ForwardExtension = 'AllowForwardExtension';
	const DisableManageSubscribe = 'DisableManageSubscribe';
	const DisableFoldersManualSort = 'DisableFoldersManualSort';
	const IgnoreSubscribeStatus = 'IgnoreSubscribeStatus';
	
	private $oServer = null;
	
	protected $aStaticMap = array(
		'IsDisabled'		=> array('bool', false),
		'IdUser'			=> array('int', 0),
		'IsInternal'		=> array('bool', false),
		'UseToAuthorize'	=> array('bool', false),
		'IsMailingList'		=> array('bool', false),
		'Email'				=> array('string', ''),
		'FriendlyName'		=> array('string', ''),
		'DetectSpecialFoldersWithXList' => array('bool', false),
		'IncomingLogin'		=> array('string', ''),
		'IncomingPassword'	=> array('encrypted', ''),
		'UseSignature'		=> array('bool', false),
		'Signature'			=> array('string', ''),
		'ServerId'			=> array('int',  0),
		'FoldersOrder'		=> array('text', '')
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
	
	public function isExtensionEnabled($sExtention)
	{
		return $sExtention === CMailAccount::DisableFoldersManualSort;
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
