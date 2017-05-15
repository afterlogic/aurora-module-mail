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
 * CApiMailServersManager class summary
 * 
 * @package Servers
 */
class CApiMailServersManager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @var \Aurora\System\Managers\Eav\Manager
	 */
	public $oEavManager = null;
	
	/**
	 * @param \Aurora\System\Managers\GlobalManager &$oManager
	 * @param string $sForcedStorage
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Managers\GlobalManager &$oManager, $sForcedStorage = '', \Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct('servers', $oManager, $oModule);
		
		$this->oEavManager = \Aurora\System\Api::GetSystemManager('eav', 'db');
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
	 * @param string $sDomains
	 * @param boolean $bEnableSieve
	 * @param int $iSievePort
	 * @param string $sOwnerType
	 * @param int $iTenantId
	 * @return int|boolean
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function createServer($sName, $sIncomingServer, $iIncomingPort, $bIncomingUseSsl,
			$sOutgoingServer, $iOutgoingPort, $bOutgoingUseSsl, $bOutgoingUseAuth, $sDomains, 
			$bEnableSieve, $iSievePort, $sOwnerType = \EMailServerOwnerType::Account, $iTenantId = 0)
	{
		try
		{
			$oServer = new CMailServer($this->oModule->GetName());
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
			$oServer->Domains = $sDomains;
			$oServer->EnableSieve = $bEnableSieve;
			$oServer->SievePort = $iSievePort;
			if (!$this->oEavManager->saveEntity($oServer))
			{
				throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::UsersManager_UserCreateFailed);
			}
			return $oServer->EntityId;
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$oServer = false;
			$this->setLastException($oException);
		}
		
		return $oServer;
	}
	
	/**
	 * 
	 * @param int $sDomain
	 * @return boolean
	 */
	public function getServerByDomain($sDomain)
	{
		$oServer = false;
		
		try
		{
			$aResult = $this->oEavManager->getEntities(
				'CMailServer', 
				array(),
				0,
				999,
				['Domains' => ['%' . $sDomain . '%', 'LIKE']]
			);		
			if (count($aResult) > 0)
			{
				foreach ($aResult as $oTempServer)
				{
					$aDomains = explode("\n",  $oTempServer->Domains);
					if (in_array($sDomain, $aDomains))
					{
						$oServer = $oTempServer;
						break;
					}
				}
			}			
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
	 * @param boolean $bOutgoingUseSsl
	 * @param boolean $bOutgoingUseAuth
	 * @param string $sDomains
	 * @param boolean $bEnableSieve
	 * @param int $iSievePort
	 * @param int $iTenantId
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function updateServer($iServerId, $sName, $sIncomingServer, $iIncomingPort, $bIncomingUseSsl,
			$sOutgoingServer, $iOutgoingPort, $bOutgoingUseSsl, $bOutgoingUseAuth, $sDomains,
			$bEnableSieve, $iSievePort, $iTenantId = 0)
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
				$oServer->Domains = $sDomains;
				$oServer->EnableSieve = $bEnableSieve;
				$oServer->SievePort = $iSievePort;
				if (!$this->oEavManager->saveEntity($oServer))
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::UsersManager_UserCreateFailed);
				}
				$bResult = true;
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
