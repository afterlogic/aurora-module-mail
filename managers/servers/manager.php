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
	 * @param string $sIncomingMailServer
	 * @param int $iIncomingMailPort
	 * @param boolean $bIncomingMailUseSSL
	 * @param string $sOutgoingMailServer
	 * @param int $iOutgoingMailPort
	 * @param boolean $bOutgoingMailAuth
	 * @param boolean $bOutgoingMailUseSSL
	 * @param int $iTenantId
	 * @return boolean
	 * @throws CApiManagerException
	 */
	public function createServer ($sName, $sIncomingMailServer, $iIncomingMailPort, $bIncomingMailUseSSL,
			$sOutgoingMailServer, $iOutgoingMailPort, $bOutgoingMailAuth, $bOutgoingMailUseSSL, $iTenantId = 0)
	{
		$bResult = false;
		
		try
		{
			$oServer = new CMailServer();
			$oServer->TenantId = $iTenantId;
			$oServer->Name = $sName;
			$oServer->IncomingMailServer = $sIncomingMailServer;
			$oServer->IncomingMailPort = $iIncomingMailPort;
			$oServer->IncomingMailUseSSL = $bIncomingMailUseSSL;
			$oServer->OutgoingMailServer = $sOutgoingMailServer;
			$oServer->OutgoingMailPort = $iOutgoingMailPort;
			$oServer->OutgoingMailAuth = $bOutgoingMailAuth;
			$oServer->OutgoingMailUseSSL = $bOutgoingMailUseSSL;
			if (!$this->oEavManager->saveEntity($oServer))
			{
				throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
			}
			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * 
	 * @param int $iServerId
	 * @param int $iTenantId
	 * @return boolean
	 */
	public function deleteServer($iServerId, $iTenantId)
	{
		$bResult = false;
		
		try
		{
			$oServer = $this->getServer($iServerId);
			if ($oServer && $oServer->TenantId === $iTenantId)
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
		$aFilters = ($iTenantId !== 0) ? ['$TenantId' => [$iTenantId, '=']] : [];
		
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
	 * @param string $sIncomingMailServer
	 * @param int $iIncomingMailPort
	 * @param boolean $bIncomingMailUseSSL
	 * @param string $sOutgoingMailServer
	 * @param int $iOutgoingMailPort
	 * @param boolean $bOutgoingMailAuth
	 * @param boolean $bOutgoingMailUseSSL
	 * @param int $iTenantId
	 * @return boolean
	 * @throws CApiManagerException
	 */
	public function updateServer($iServerId, $sName, $sIncomingMailServer, $iIncomingMailPort, $bIncomingMailUseSSL,
			$sOutgoingMailServer, $iOutgoingMailPort, $bOutgoingMailAuth, $bOutgoingMailUseSSL, $iTenantId = 0)
	{
		$bResult = false;
		
		try
		{
			$oServer = $this->getServer($iServerId);
			if ($oServer && $oServer->TenantId === $iTenantId)
			{
				$oServer->Name = $sName;
				$oServer->IncomingMailServer = $sIncomingMailServer;
				$oServer->IncomingMailPort = $iIncomingMailPort;
				$oServer->IncomingMailUseSSL = $bIncomingMailUseSSL;
				$oServer->OutgoingMailServer = $sOutgoingMailServer;
				$oServer->OutgoingMailPort = $iOutgoingMailPort;
				$oServer->OutgoingMailAuth = $bOutgoingMailAuth;
				$oServer->OutgoingMailUseSSL = $bOutgoingMailUseSSL;
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
