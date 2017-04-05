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
 * CApiMailIdentitiesManager class
 */
class CApiMailIdentitiesManager extends \Aurora\System\Managers\AbstractManager
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
		parent::__construct('accounts', $oManager, $oModule);
		
		$this->oEavManager = \Aurora\System\Api::GetSystemManager('eav', 'db');
	}

	/**
	 * @param int $iUserId
	 * @param int $iAccountID
	 * @param string $sFriendlyName
	 * @param string $sEmail
	 * @return boolean
	 */
	public function createIdentity($iUserId, $iAccountID, $sFriendlyName, $sEmail)
	{
		try
		{
			$oIdentity = new CIdentity($this->oModule->GetName());
			$oIdentity->IdUser = $iUserId;
			$oIdentity->IdAccount = $iAccountID;
			$oIdentity->FriendlyName = $sFriendlyName;
			$oIdentity->Email = $sEmail;
			$this->oEavManager->saveEntity($oIdentity);
			return $oIdentity->EntityId;
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return false;
	}

	/**
	 * @param int $iEntityId
	 * @return boolean
	 */
	public function getIdentity($iEntityId)
	{
		$oIdentity = false;
		
		try
		{
			$oIdentity = $this->oEavManager->getEntity($iEntityId);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$oIdentity = false;
			$this->setLastException($oException);
		}
		
		return $oIdentity;
	}
	
	/**
	 * @param int $iEntityId
	 * @param string $sFriendlyName
	 * @param string $sEmail
	 * @param boolean $bDefault
	 * @return boolean
	 */
	public function updateIdentity($iEntityId, $sFriendlyName, $sEmail, $bDefault)
	{
		try
		{
			$oIdentity = $this->getIdentity($iEntityId);
			$oIdentity->FriendlyName = $sFriendlyName;
			$oIdentity->Email = $sEmail;
			$oIdentity->Default = $bDefault;
			return $this->oEavManager->saveEntity($oIdentity);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return false;
	}

	/**
	 * @param int $iEntityId
	 * @param boolean $bUseSignature
	 * @param string $sSignature
	 * @return boolean
	 */
	public function updateIdentitySignature($iEntityId, $bUseSignature, $sSignature)
	{
		try
		{
			$oIdentity = $this->getIdentity($iEntityId);
			$oIdentity->UseSignature = $bUseSignature;
			$oIdentity->Signature = $sSignature;
			return $this->oEavManager->saveEntity($oIdentity);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return false;
	}

	/**
	 * @param int $iEntityId
	 * @return boolean
	 */
	public function deleteIdentity($iEntityId)
	{
		$bResult = false;
		
		try
		{
			$oIdentity = $this->getIdentity($iEntityId);
			if ($oIdentity)
			{
				$bResult = $this->oEavManager->deleteEntity($iEntityId);
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param int $iUserId
	 * @param array $aFilters
	 * @return boolean
	 */
	public function getIdentities($iUserId, $aFilters = [])
	{
		$aResult = false;
		$iOffset = 0;
		$iLimit = 0;
		if (count($aFilters) === 0)
		{
			$aFilters = ['IdUser' => [$iUserId, '=']];
		}
		else
		{
			$aFilters['IdUser'] = [$iUserId, '='];
			$aFilters = ['$AND' => $aFilters];
		}
		$sOrderBy = 'FriendlyName';
		$iOrderType = \ESortOrder::ASC;
		
		try
		{
			$aResult = $this->oEavManager->getEntities(
				'CIdentity', 
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
	 * @param int $iUserId
	 */
	public function resetDefaultIdentity($iUserId)
	{
		$aIdentities = $this->getIdentities($iUserId, ['Default' => [true, '=']]);
		foreach ($aIdentities as $oIdentity)
		{
			$oIdentity->Default = false;
			$this->oEavManager->saveEntity($oIdentity);
		}
	}
}
