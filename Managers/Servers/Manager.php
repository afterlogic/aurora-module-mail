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
		
		$this->oEavManager = \Aurora\System\Managers\Eav::getInstance();
	}

	/**
	 * @param instanceof \Aurora\Modules\Mail\Classes\Server
	 * @return int|boolean
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function createServer(\Aurora\Modules\Mail\Classes\Server $oServer)
	{
		try
		{
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
			$oServer = $this->oEavManager->getEntity((int)$iServerId, \Aurora\Modules\Mail\Classes\Server::class);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$oServer = false;
			$this->setLastException($oException);
		}
		
		return $oServer;
	}
	
	public function trimDomains($sDomains)
	{
		$aDomains = array_filter(array_map("trim", explode("\n", $sDomains)));
		return join("\n", $aDomains);
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
			$iTenantId = \Aurora\Modules\Core\Module::Decorator()->GetTenantIdByName('Default');
			$aFilters = [
				'$AND' => [
					'$OR' => [
						'OwnerType' => [\Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin, '='],
						'$AND' => [
							'TenantId' => [$iTenantId, '='],
							'OwnerType' => [\Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant, '='],
						],
					],
					'Domains' => ['%' . $sDomain . '%', 'LIKE']
				]
			];

			$aResult = $this->oEavManager->getEntities(
				\Aurora\Modules\Mail\Classes\Server::class,
				array(),
				0,
				999,
				$aFilters
			);		
			if (count($aResult) > 0)
			{
				foreach ($aResult as $oTempServer)
				{
					$sTrimmedDomains = $this->trimDomains($oTempServer->Domains);
					$aDomains = explode("\n",  $sTrimmedDomains);
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
			$aFilters = ['$OR' => [
				'OwnerType' => [\Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin, '='],
				'$AND' => [
					'TenantId' => [$iTenantId, '='],
					'OwnerType' => [\Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant, '='],
				],
			]];
		}
		
		try
		{
			$aResult = $this->oEavManager->getEntities(
				\Aurora\Modules\Mail\Classes\Server::class,
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

	/**
	 *
	 * @param array $aFilters
	 * @return boolean
	 */
	public function getServerByFilter($aFilters)
	{
		$oServer = false;

		$aResult = $this->oEavManager->getEntities(
			\Aurora\Modules\Mail\Classes\Server::class,
			array(),
			0,
			999,
			$aFilters
		);
		if (count($aResult) > 0)
		{
			$oServer = $aResult[0];
		}

		return $oServer;
	}
}
