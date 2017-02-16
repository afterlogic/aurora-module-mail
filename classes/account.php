<?php
/**
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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
 * 
 * @package Modules
 */

/**
 *
 * @package Users
 * @subpackage Classes
 */
class CMailAccount extends AEntity
{
	const ChangePasswordExtension = 'AllowChangePasswordExtension';
	const AutoresponderExtension = 'AllowAutoresponderExtension';
	const SpamFolderExtension = 'AllowSpamFolderExtension';
	const DisableAccountDeletion = 'DisableAccountDeletion';
	const DisableManageFolders = 'DisableManageFolders';
	const SieveFiltersExtension = 'AllowSieveFiltersExtension';
	const ForwardExtension = 'AllowForwardExtension';
	const DisableManageSubscribe = 'DisableManageSubscribe';
	const DisableFoldersManualSort = 'DisableFoldersManualSort';
	const IgnoreSubscribeStatus = 'IgnoreSubscribeStatus';
	
	private $oServer = null;
	
	/**
	 * Creates a new instance of the object.
	 * 
	 * @return void
	 */
	public function __construct($sModule, $oParams)
	{
		parent::__construct(get_class($this), $sModule);
		
		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		$this->setStaticMap(array(
			'IsDisabled'		=> array('bool', false),
			'IdUser'			=> array('int', 0),
			'IsInternal'		=> array('bool', false),
			'CanAuthorize'		=> array('bool', false),
			'IsMailingList'		=> array('bool', false),
			'StorageQuota'		=> array('int', 0),
			'StorageUsedSpace'	=> array('int', 0),
			'Email'				=> array('string', ''),
			'FriendlyName'		=> array('string', ''),
			'DetectSpecialFoldersWithXList' => array('bool', false),
			'IncomingLogin'		=> array('string', ''),
			'IncomingPassword'	=> array('encrypted', ''),
			'OutgoingLogin'		=> array('string', ''),
			'UseSignature'		=> array('bool', false),
			'Signature'			=> array('string', ''),
			'ServerId'			=> array('int',  0),
			'FoldersOrder'		=> array('text', '')
		));
	}

	public static function createInstance($sModule = 'Mail', $oParams = array())
	{
		return new CMailAccount($sModule, $oParams);
	}
	
	public function getServer()
	{
		if ($this->oServer === null && $this->ServerId !== 0)
		{
			$oMailModule = \CApi::GetModule('Mail');
			$this->oServer = $oMailModule->oApiServersManager->getServer($this->ServerId);
		}
		return $this->oServer;
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
		$aResponse['Server'] = $oServer->toResponseArray();
		return $aResponse;
	}
}
