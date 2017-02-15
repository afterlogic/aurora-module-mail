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
 * CApiMailServersManager class summary
 * 
 * @package Servers
 */
class CApiMailServersManager extends AApiManager
{
	/**
	 * @var CApiEavManager
	 */
	public $oEavManager = null;
	
	/**
	 * @param CApiGlobalManager &$oManager
	 * @param string $sForcedStorage
	 * @param AApiModule $oModule
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '', AApiModule $oModule = null)
	{
		parent::__construct('servers', $oManager, $oModule);
		
		$this->oEavManager = \CApi::GetSystemManager('eav', 'db');
	}

	/**
	 * 
	 * @param string $sName
	 * @param string $sIncomingServer
	 * @param int $iIncomingPort
	 * @param boolean $bIncomingUseSsl
	 * @param string $sOutgoingServer
	 * @param int $iOutgoingPort
	 * @param boolean $bOutgoingUseSsl
	 * @param boolean $bOutgoingUseAuth
	 * @param int $iTenantId
	 * @return boolean
	 * @throws CApiManagerException
	 */
	public function createServer($sName, $sIncomingServer, $iIncomingPort, $bIncomingUseSsl,
			$sOutgoingServer, $iOutgoingPort, $bOutgoingUseSsl, $bOutgoingUseAuth, $sOwnerType = \EMailServerOwnerType::Account, $iTenantId = 0)
	{
		try
		{
			$oServer = new CMailServer();
			$oServer->OwnerType = $sOwnerType;
			$oServer->TenantId = $iTenantId;
			$oServer->Name = $sName;
			$oServer->IncomingServer = $sIncomingServer;
			$oServer->IncomingPort = $iIncomingPort;
			$oServer->IncomingUseSsl = $bIncomingUseSsl;
			$oServer->OutgoingServer = $sOutgoingServer;
			$oServer->OutgoingPort = $iOutgoingPort;
			$oServer->OutgoingUseSsl = $bOutgoingUseSsl;
			$oServer->OutgoingUseAuth = $bOutgoingUseAuth;
			if (!$this->oEavManager->saveEntity($oServer))
			{
				throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
			}
			return $oServer->iId;
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return false;
	}
	
	/**
	 * 
	 * @param int $iServerId
	 * @param int $iTenantId
	 * @return boolean
	 */
	public function deleteServer($iServerId, $iTenantId = 0)
	{
		$bResult = false;
		
		try
		{
			$oServer = $this->getServer($iServerId);
			if ($oServer && ($oServer->OwnerType !== \EMailServerOwnerType::Tenant || $oServer->TenantId === $iTenantId))
			{
				$bResult = $this->oEavManager->deleteEntity($iServerId);
			}
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * 
	 * @param int $iServerId
	 * @return boolean
	 */
	public function getServer($iServerId)
	{
		$oServer = false;
		
		try
		{
			$oServer = $this->oEavManager->getEntity($iServerId);
		}
		catch (CApiBaseException $oException)
		{
			$oServer = false;
			$this->setLastException($oException);
		}
		
		return $oServer;
	}
	
	/**
	 * 
	 * @param int $iTenantId
	 * @return boolean|array
	 */
	public function getServerList($iTenantId = 0)
	{
		$aResult = false;
		$iOffset = 0;
		$iLimit = 0;
		$sOrderBy = 'Name';
		$iOrderType = \ESortOrder::ASC;
		
		$aFilters = [];
		if ($iTenantId === 0)
		{
			$aFilters = ['OwnerType' => [\EMailServerOwnerType::SuperAdmin, '=']];
		}
		else
		{
			$aFilters = ['OR' => [
				'OwnerType' => [\EMailServerOwnerType::SuperAdmin, '='],
				'AND' => [
					'TenantId' => [$iTenantId, '='],
					'OwnerType' => [\EMailServerOwnerType::Tenant, '='],
				],
			]];
		}
		
		try
		{
			$aResult = $this->oEavManager->getEntities(
				'CMailServer', 
				array(),
				$iOffset,
				$iLimit,
				$aFilters,
				$sOrderBy,
				$iOrderType
			);
		}
		catch (CApiBaseException $oException)
		{
			$aResult = false;
			$this->setLastException($oException);
		}
		
		return $aResult;
	}
	
	/**
	 * 
	 * @param int $iServerId
	 * @param string $sName
	 * @param string $sIncomingServer
	 * @param int $iIncomingPort
	 * @param boolean $bIncomingUseSsl
	 * @param string $sOutgoingServer
	 * @param int $iOutgoingPort
	 * @param boolean $bOutgoingUseAuth
	 * @param boolean $bOutgoingUseSsl
	 * @param int $iTenantId
	 * @return boolean
	 * @throws CApiManagerException
	 */
	public function updateServer($iServerId, $sName, $sIncomingServer, $iIncomingPort, $bIncomingUseSsl,
			$sOutgoingServer, $iOutgoingPort, $bOutgoingUseSsl, $bOutgoingUseAuth, $iTenantId = 0)
	{
		$bResult = false;
		
		try
		{
			$oServer = $this->getServer($iServerId);
			if ($oServer && $oServer->TenantId === $iTenantId)
			{
				$oServer->Name = $sName;
				$oServer->IncomingServer = $sIncomingServer;
				$oServer->IncomingPort = $iIncomingPort;
				$oServer->IncomingUseSsl = $bIncomingUseSsl;
				$oServer->OutgoingServer = $sOutgoingServer;
				$oServer->OutgoingPort = $iOutgoingPort;
				$oServer->OutgoingUseSsl = $bOutgoingUseSsl;
				$oServer->OutgoingUseAuth = $bOutgoingUseAuth;
				if (!$this->oEavManager->saveEntity($oServer))
				{
					throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
				}
				$bResult = true;
			}
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
