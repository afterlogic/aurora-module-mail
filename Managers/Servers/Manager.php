<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Servers;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @var \Aurora\System\Managers\Eav
	 */
	public $oEavManager = null;
	
	/**
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule);
		
		$this->oEavManager = new \Aurora\System\Managers\Eav();
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
	 * @param string $sSmtpAuthType
	 * @param string $sDomains
	 * @param boolean $bEnableThreading
	 * @param string $sSmtpLogin
	 * @param string $sSmtpPassword
	 * @param boolean $bEnableSieve
	 * @param int $iSievePort
	 * @param string $sOwnerType
	 * @param int $iTenantId
	 * @return int|boolean
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function createServer($sName, $sIncomingServer, $iIncomingPort, $bIncomingUseSsl,
			$sOutgoingServer, $iOutgoingPort, $bOutgoingUseSsl, $sSmtpAuthType, $sDomains, $bEnableThreading = true, $sSmtpLogin = 0, $sSmtpPassword = 0, 
			$bEnableSieve = false, $iSievePort = 4190, $sOwnerType = \Aurora\Modules\Mail\Enums\ServerOwnerType::Account, $iTenantId = 0)
	{
		try
		{
			$oServer = new \Aurora\Modules\Mail\Classes\Server($this->oModule->GetName());
			$oServer->OwnerType = $sOwnerType;
			$oServer->TenantId = $iTenantId;
			$oServer->Name = $sName;
			$oServer->IncomingServer = $sIncomingServer;
			$oServer->IncomingPort = $iIncomingPort;
			$oServer->IncomingUseSsl = $bIncomingUseSsl;
			$oServer->OutgoingServer = $sOutgoingServer;
			$oServer->OutgoingPort = $iOutgoingPort;
			$oServer->OutgoingUseSsl = $bOutgoingUseSsl;
			$oServer->SmtpAuthType = $sSmtpAuthType;
			$oServer->SmtpLogin = $sSmtpLogin;
			$oServer->SmtpPassword = $sSmtpPassword;
			$oServer->Domains = $sDomains;
			$oServer->EnableThreading = $bEnableThreading;
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
			if ($oServer && ($oServer->OwnerType !== \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant || $oServer->TenantId === $iTenantId))
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
			$oServer = $this->oEavManager->getEntity((int)$iServerId, $this->getModule()->getNamespace() . '\Classes\Server');
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
				$this->getModule()->getNamespace() . '\Classes\Server',
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
		$iOrderType = \Aurora\System\Enums\SortOrder::ASC;
		
		$aFilters = [];
		if ($iTenantId === 0)
		{
			$aFilters = ['OwnerType' => [\Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin, '=']];
		}
		else
		{
			$aFilters = ['OR' => [
				'OwnerType' => [\Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin, '='],
				'AND' => [
					'TenantId' => [$iTenantId, '='],
					'OwnerType' => [\Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant, '='],
				],
			]];
		}
		
		try
		{
			$aResult = $this->oEavManager->getEntities(
				$this->getModule()->getNamespace() . '\Classes\Server',
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
	 * @param instanceof \Aurora\Modules\Mail\Classes\Server
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function updateServer(\Aurora\Modules\Mail\Classes\Server $oServer)
	{
		$bResult = false;
		
		try
		{
			if (!$this->oEavManager->saveEntity($oServer))
			{
				throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::UsersManager_UserCreateFailed);
			}
			$bResult = true;
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
