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
 * CApiMailAccountsManager class summary
 * 
 * @package Accounts
 */
class CApiMailAccountsManager extends AApiManager
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
		parent::__construct('accounts', $oManager, $oModule);
		
		$this->oEavManager = \CApi::GetSystemManager('eav', 'db');
	}

	/**
	 * Retrieves information on particular WebMail Pro user. 
	 * 
	 * @todo not used
	 * 
	 * @param int $iAccountId Account identifier.
	 * 
	 * @return CUser | false
	 */
	public function getAccountById($iAccountId)
	{
		$oAccount = null;
		try
		{
			if (is_numeric($iAccountId))
			{
				$iAccountId = (int) $iAccountId;
				if (null === $oAccount)
				{
//					$oAccount = $this->oStorage->getUserById($iUserId);
					$oAccount = $this->oEavManager->getEntity($iAccountId);
					
					if ($oAccount instanceof \CMailAccount)
					{
						//TODO method needs to be refactored according to the new system of properties inheritance
//						$oApiDomainsManager = CApi::GetCoreManager('domains');
//						$oDomain = $oApiDomainsManager->getDefaultDomain();
						
//						$oAccount->setInheritedSettings(array(
//							'domain' => $oDomain
//						));
					}
				}
			}
			else
			{
				throw new CApiBaseException(Errs::Validation_InvalidParameters);
			}
		}
		catch (CApiBaseException $oException)
		{
			$oAccount = false;
			$this->setLastException($oException);
		}
		return $oAccount;
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
		catch (CApiBaseException $oException)
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
		catch (CApiBaseException $oException)
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
		catch (CApiBaseException $oException)
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
		catch (CApiBaseException $oException)
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
						throw new CApiManagerException(Errs::UserManager_AccountCreateFailed);
					}
				}
				else
				{
					throw new \System\Exceptions\AuroraApiException(\System\Notifications::AccountExists);
				}
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
					throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
				}
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
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
