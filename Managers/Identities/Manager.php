<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Identities;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
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
			$oIdentity = new \Aurora\Modules\Mail\Classes\Identity(\Aurora\Modules\Mail\Module::GetName());
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
			$oIdentity = $this->oEavManager->getEntity($iEntityId, \Aurora\Modules\Mail\Classes\Identity::class);
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
				$bResult = $this->oEavManager->deleteEntity($iEntityId, \Aurora\Modules\Mail\Classes\Identity::class);
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
		$iOrderType = \Aurora\System\Enums\SortOrder::ASC;
		
		try
		{
			$aResult = $this->oEavManager->getEntities(
				\Aurora\Modules\Mail\Classes\Identity::class,
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
	 * Deletes identities of the account.
	 * @param int $iAccountId Account identifier.
	 * @return boolean
	 */
	public function deleteAccountIdentities($iAccountId)
	{
		$bResult = true;
		
		$iOffset = 0;
		$iLimit = 0;
		$aFilters = array('IdAccount' => array($iAccountId, '='));
		$aIdentities = $this->oEavManager->getEntities(\Aurora\Modules\Mail\Classes\Identity::class, array(), $iOffset, $iLimit, $aFilters);
		if (is_array($aIdentities))
		{
			foreach ($aIdentities as $oIdentity)
			{
				$bResult = $bResult && $this->oEavManager->deleteEntity($oIdentity->EntityId, \Aurora\Modules\Mail\Classes\Identity::class);
			}
		}
		
		return $bResult;
	}
	
	/**
	 * @param int $iUserId
	 * @param int $iAccountId
	 */
	public function resetDefaultIdentity($iUserId, $iAccountId)
	{
		$aIdentities = $this->getIdentities($iUserId, ['Default' => [true, '=']]);
		foreach ($aIdentities as $oIdentity)
		{
			if ($oIdentity->IdAccount === $iAccountId)
			{
				$oIdentity->Default = false;
				$this->oEavManager->saveEntity($oIdentity);
			}
		}
	}
}
