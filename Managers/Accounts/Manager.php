<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Accounts;

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
	 * 
	 * @param int $iAccountId
	 * @return boolean|Aurora\Modules\Mail\Classes\Account
	 * @throws \Aurora\System\Exceptions\BaseException
	 */
	public function getAccountById($iAccountId)
	{
		$mAccount = false;
		try
		{
			if (is_numeric($iAccountId))
			{
				$mAccount = $this->oEavManager->getEntity((int) $iAccountId, \Aurora\Modules\Mail\Classes\Account::class);
			}
			else
			{
				throw new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\Errs::Validation_InvalidParameters);
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$mAccount = false;
			$this->setLastException($oException);
		}
		return $mAccount;
	}
	
	/**
	 * Obtains an account with the specified email and for the specified user.
	 * @param string $sEmail Email of the account.
	 * @param int $iUserId Identifier of the user which owns the account.
	 * @return boolean|Aurora\Modules\Mail\Classes\Account
	 * @throws \Aurora\System\Exceptions\BaseException
	 */
	public function getAccountByEmail($sEmail, $iUserId)
	{
		$mAccount = false;

		$aFilters = [
			'Email' => $sEmail,
			'IsDisabled' => false,
			'IdUser' => $iUserId,
		];

		$aResults = $this->oEavManager->getEntities(
			\Aurora\Modules\Mail\Classes\Account::class,
			[],
			0,
			0,
			$aFilters
		);

		if (is_array($aResults) && isset($aResults[0]))
		{
			$mAccount = $aResults[0];
		}

		return $mAccount;
	}

	/**
	 * Obtains an account with specified email. The account must be allowed to authenticate its user.
	 * @param string $sEmail Email of the account.
	 * @return Aurora\Modules\Mail\Classes\Account|boolean
	 */
	public function getAccountUsedToAuthorize($sEmail)
	{
		$mAccount = false;
		
		$aFilters = [
			'Email' => $sEmail,
			'IsDisabled' => false,
			'UseToAuthorize' => true
		];
		$aResults = $this->oEavManager->getEntities(
			\Aurora\Modules\Mail\Classes\Account::class,
			[],
			0,
			0,
			$aFilters
		);

		if (is_array($aResults) && isset($aResults[0]))
		{
			$mAccount = $aResults[0];
		}
		
		return $mAccount;
	}
	
	/**
	 * @param string $sEmail
	 * @param int $iExceptId
	 * @return array
	 */
	public function useToAuthorizeAccountExists($sEmail, $iExceptId = 0)
	{
		$bExists = false;
		
		try
		{
			$aResults = $this->oEavManager->getEntities(
				'Aurora\Modules\Mail\Classes\Account',
				array(
					'Email'
				),
				0,
				0,
				array(
					'Email' => [$sEmail, '='],
					'UseToAuthorize' => [true, '=']
				)
			);
			
			if (is_array($aResults) && count($aResults) > 0)
			{
				foreach ($aResults as $oAccount)
				{
					if ($oAccount->EntityId !== $iExceptId)
					{
						$bExists = true;
					}
				}
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		
		return $bExists;
	}

	/**
	 * @param int $iUserId
	 *
	 * @return Array | false
	 */
	public function getUserAccounts($iUserId)
	{
		$mResult = false;
		try
		{
			$mResult = $this->oEavManager->getEntities(
				'Aurora\Modules\Mail\Classes\Account',
				array(),
				0,
				0,
				array('IdUser' => $iUserId, 'IsDisabled' => false)
			);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		
		return $mResult;
	}
	
	/**
	 * Obtains mail accounts with specified parameters.
	 * @param Array $aFilters
	 * @return Array|false
	 */
	public function getAccounts($aFilters)
	{
		return $this->oEavManager->getEntities(
			'Aurora\Modules\Mail\Classes\Account',
			array(),
			0,
			0,
			$aFilters
		);
	}
	
	/**
	 * @param int $iUserId
	 *
	 * @return Array | false
	 */
	public function getUserAccountsCount($iUserId)
	{
		$mResult = false;
		try
		{
			$mResult = $this->oEavManager->getEntitiesCount(
				'Aurora\Modules\Mail\Classes\Account',
				array('IdUser' => $iUserId)
			);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		
		return $mResult;
	}
	
	/**
	 * @param Aurora\Modules\Mail\Classes\Account $oAccount
	 *
	 * @return bool
	 */
	public function canCreate($iUserId)
	{
		$bResult = false;
		
		$iAccounts = $this->getUserAccountsCount($iUserId);
		
		if ($iAccounts < 1)
		{
			$bResult = true;
		}
		
		return $bResult;
	}
	
	/**
	 * @param Aurora\Modules\Mail\Classes\Account $oAccount
	 *
	 * @return bool
	 */
	public function isExists(\Aurora\Modules\Mail\Classes\Account $oAccount)
	{
		$bResult = false;

		$aResults = $this->oEavManager->getEntities(
			'Aurora\Modules\Mail\Classes\Account',
			['Email'],
			0,
			0,
			[
				'$AND' => [
					'Email' => [$oAccount->Email, '='],
					'IdUser' => [$oAccount->IdUser, '=']
				]
			]
		);

		if ($aResults && count($aResults) > 0)
		{
			$bResult = true;
		}

		return $bResult;
	}
	
	/**
	 * @param Aurora\Modules\Mail\Classes\Account $oAccount
	 *
	 * @return bool
	 */
	public function createAccount (\Aurora\Modules\Mail\Classes\Account &$oAccount)
	{
		$bResult = false;

		if ($oAccount->validate() && $this->canCreate($oAccount->IdUser))
		{
			if (!$this->isExists($oAccount))
			{
				if (!$this->oEavManager->saveEntity($oAccount))
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::UserManager_AccountCreateFailed);
				}
			}
			else
			{
				throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::AccountExists);
			}

			$bResult = true;
		}

		return $bResult;
	}
	
	/**
	 * @param Aurora\Modules\Mail\Classes\Account $oAccount
	 *
	 * @return bool
	 */
	public function updateAccount (\Aurora\Modules\Mail\Classes\Account &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
				if (!$this->oEavManager->saveEntity($oAccount))
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::UsersManager_UserCreateFailed);
				}
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
	 * @param Aurora\Modules\Mail\Classes\Account $oAccount
	 *
	 * @throws $oException
	 *
	 * @return bool
	 */
	public function deleteAccount(\Aurora\Modules\Mail\Classes\Account $oAccount)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oEavManager->deleteEntity($oAccount->EntityId, \Aurora\Modules\Mail\Classes\Account::class);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
