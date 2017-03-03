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
 * 
 * @package Modules
 */

/**
 * CApiMailAccountsManager class summary
 * 
 * @package Accounts
 */
class CApiMailAccountsManager extends \Aurora\System\AbstractManager
{
	/**
	 * @var CApiEavManager
	 */
	public $oEavManager = null;
	
	/**
	 * @param \Aurora\System\GlobalManager &$oManager
	 * @param string $sForcedStorage
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\GlobalManager &$oManager, $sForcedStorage = '', \Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct('accounts', $oManager, $oModule);
		
		$this->oEavManager = \Aurora\System\Api::GetSystemManager('eav', 'db');
	}

	/**
	 * 
	 * @param int $iAccountId
	 * @return boolean|CMailAccount
	 * @throws \Aurora\System\Exceptions\BaseException
	 */
	public function getAccountById($iAccountId)
	{
		$mAccount = false;
		try
		{
			if (is_numeric($iAccountId))
			{
				$mAccount = $this->oEavManager->getEntity((int) $iAccountId);
			}
			else
			{
				throw new \Aurora\System\Exceptions\BaseException(Errs::Validation_InvalidParameters);
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
	 * 
	 * @param string $sEmail
	 * @param string $sIncomingPassword
	 * @return CMailAccount|boolean
	 */
	public function getUseToAuthorizeAccount($sEmail, $sIncomingPassword)
	{
		$oAccount = false;
		try
		{
			$aResults = $this->oEavManager->getEntities(
				'CMailAccount', 
				array(),
				0,
				0,
				array(
					'Email' => $sEmail,
					'IsDisabled' => false,
					'IncomingPassword' => $sIncomingPassword,
					'UseToAuthorize' => [true, '=']
				)
			);
			
			if (is_array($aResults) && isset($aResults[0]))
			{
				$oAccount = $aResults[0];
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		
		return $oAccount;
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
				'CMailAccount', 
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
				'CMailAccount',
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
	 * @param CMailAccount $oAccount
	 *
	 * @return bool
	 */
	public function isExists(CMailAccount $oAccount)
	{
		$bResult = false;
		try
		{
			$aResults = $this->oEavManager->getEntities(
				'CMailAccount',
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
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}
	
	/**
	 * @param CMailAccount $oAccount
	 *
	 * @return bool
	 */
	public function createAccount (CMailAccount &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
				if (!$this->isExists($oAccount))
				{
					if (!$this->oEavManager->saveEntity($oAccount))
					{
						throw new \Aurora\System\Exceptions\ManagerException(Errs::UserManager_AccountCreateFailed);
					}
				}
				else
				{
					throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::AccountExists);
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
	 * @param CMailAccount $oAccount
	 *
	 * @return bool
	 */
	public function updateAccount (CMailAccount &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
				if (!$this->oEavManager->saveEntity($oAccount))
				{
					throw new \Aurora\System\Exceptions\ManagerException(Errs::UsersManager_UserCreateFailed);
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
	 * @param CMailAccount $oAccount
	 *
	 * @throws $oException
	 *
	 * @return bool
	 */
	public function deleteAccount(CMailAccount $oAccount)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oEavManager->deleteEntity($oAccount->EntityId);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
