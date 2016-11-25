<?php

/* -AFTERLOGIC LICENSE HEADER- */

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
	 * Retrieves information on particular WebMail Pro user. 
	 * 
	 * @todo not used
	 * 
	 * @param int $iUserId User identifier.
	 * 
	 * @return CUser | false
	 */
	public function getAccountByCredentials($sEmail, $sIncomingMailPassword)
	{
		$oAccount = null;
		try
		{
			$aResults = $this->oEavManager->getEntities(
				'CMailAccount', 
				array(
					'IsDisabled', 'Email', 'IncomingMailPassword', 'IncomingMailLogin', 'IncomingMailServer', 'IdUser'
				),
				0,
				0,
				array(
					'Email' => $sEmail,
					'IncomingMailPassword' => $sIncomingMailPassword,
					'IsDisabled' => false
				)
			);
			
			if (is_array($aResults) && count($aResults) === 1)
			{
				$oAccount = $aResults[0];
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
	 * Retrieves information on particular WebMail Pro user. 
	 * 
	 * 
	 * @param int $iUserId User identifier.
	 * 
	 * @return CUser | false
	 */
	public function isDefaultUserAccountExists ($iUserId)
	{
		$bExists = false;
		
		try
		{
			$aResults = $this->oEavManager->getEntities(
				'CMailAccount', 
				array(
					'IdUser'
				),
				0,
				0,
				array(
					'IdUser' => $iUserId,
					'IsDefaultAccount' => true
				)
			);
			
			if (is_array($aResults) && count($aResults) > 0)
			{
				$bExists = true;
			}
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		
		return $bExists;
	}

	/**
	 * Obtains list of information about users for specific domain. Domain identifier is used for look up.
	 * The answer contains information only about default account of founded user.
	 * 
	 * 
	 * @param int $iDomainId Domain identifier.
	 * @param int $iPage List page.
	 * @param int $iUsersPerPage Number of users on a single page.
	 * @param string $sOrderBy = 'email'. Field by which to sort.
	 * @param bool $bAscOrderType = true. If **true** the sort order type is ascending.
	 * @param string $sSearchDesc = ''. If specified, the search goes on by substring in the name and email of default account.
	 * 
	 * @return array | false [IdAccount => [IsMailingList, Email, FriendlyName, IsDisabled, IdUser, StorageQuota, LastLogin]]
	 */
	public function getAccountList($iPage, $iUsersPerPage, $sOrderBy = 'Email', $iOrderType = \ESortOrder::ASC, $sSearchDesc = '')
	{
		$aResult = false;
		try
		{
//			$aResult = $this->oStorage->getUserList($iDomainId, $iPage, $iUsersPerPage, $sOrderBy, $bAscOrderType, $sSearchDesc);
			
			$aFilters =  array();
			
			if ($sSearchDesc !== '')
			{
				$aFilters['Email'] = '%'.$sSearchDesc.'%';
			}
				
			$aResults = $this->oEavManager->getEntities(
				'CMailAccount', 
				array(
					'IsDisabled', 'Email', 'IncomingMailPassword', 'IncomingMailServer', 'IsDefaultAccount', 'IdUser'
				),
				$iPage,
				$iUsersPerPage,
				$aFilters,
				$sOrderBy,
				$iOrderType
			);

			if (is_array($aResults))
			{
				foreach($aResults as $oItem)
				{
					$aResult[$oItem->iId] = array(
						$oItem->Email,
						$oItem->IncomingMailPassword,
						$oItem->IncomingMailServer,
						$oItem->IsDefaultAccount,
						$oItem->IdUser,
						$oItem->IsDisabled
					);
				}
			}
		}
		catch (CApiBaseException $oException)
		{
			$aResult = false;
			$this->setLastException($oException);
		}
		return $aResult;
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
			$aResults = $this->oEavManager->getEntities(
				'CMailAccount',
				array('Email', 'FriendlyName', 'IsDefaultAccount'),
				0,
				0,
				array('IdUser' => $iUserId, 'IsDisabled' => false)
			);

			if (is_array($aResults))
			{
				$mResult = array();
				foreach($aResults as $oItem)
				{
					$mResult[$oItem->iId] = array(
						'AccountID' => $oItem->iId,
						'IsDefault' => $oItem->IsDefaultAccount,
						'Email' => $oItem->Email,
						'FriendlyName' => $oItem->FriendlyName,
						'Signature' => array('Signature' => '', 'Type' => 1, 'Options' =>0),
						'IsPasswordSpecified' => true,
						'AllowMail' => true
					);
				}
			}
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
				array('Email'),
				0,
				0,
				array('Email' => $oAccount->Email)
			);

			if ($aResults)
			{
				foreach($aResults as $oObject)
				{
					if ($oObject->iId !== $oAccount->iId)
					{
						$bResult = true;
						break;
					}
				}
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
						throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
					}
				}
				else
				{
					throw new CApiManagerException(Errs::UsersManager_UserAlreadyExists);
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
//				if ($this->isExists($oAccount))
//				{
					if (!$this->oEavManager->saveEntity($oAccount))
					{
						throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
					}
//				}
//				else
//				{
//					throw new CApiManagerException(Errs::UsersManager_UserAlreadyExists);
//				}
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
			$bResult = $this->oEavManager->deleteEntity($oAccount->iId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
