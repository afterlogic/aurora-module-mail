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
 * CApiFetchersManager class summary
 *
 * @package Fetchers
 */
class CApiMailFetchersManager extends \Aurora\System\AbstractManagerWithStorage
{
	/**
	 * @param \Aurora\System\GlobalManager &$oManager
	 */
	public function __construct(\Aurora\System\GlobalManager &$oManager, $sForcedStorage = '', \Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct('fetchers', $oManager, $sForcedStorage, $oModule);
	}

	/**
	 * @param CFetcher $oFetcher
	 *
	 * @return \MailSo\Pop3\Pop3Client|null
	 */
	private function getTestPop3Client($oFetcher)
	{
		$oPop3Client = null;
		if ($oFetcher)
		{
			$oPop3Client = \MailSo\Pop3\Pop3Client::NewInstance();
			$oPop3Client->SetTimeOuts(5, 5);
			$oPop3Client->SetLogger(\Aurora\System\Api::MailSoLogger());
			
			if (!$oPop3Client->IsConnected())
			{
				$oPop3Client->Connect($oFetcher->IncomingServer, $oFetcher->IncomingPort, $oFetcher->IncomingMailSecurity);
			}

			if (!$oPop3Client->IsLoggined())
			{
				$oPop3Client->Login($oFetcher->IncomingLogin, $oFetcher->IncomingPassword);
			}
		}

		return $oPop3Client;
	}

	/**
	 * @param CAccount $oAccount
	 * @param CFetcher $oFetcher
	 *
	 * @return bool
	 */
	public function createFetcher($oAccount, &$oFetcher)
	{
		$mResult = false;
		try
		{
			$this->getTestPop3Client($oFetcher);
			$mResult = $this->oStorage->createFetcher($oAccount, $oFetcher);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		catch (\MailSo\Net\Exceptions\ConnectionException $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(CApiErrorCodes::Fetcher_ConnectToMailServerFailed, $oException));
		}
		catch (\MailSo\Pop3\Exceptions\LoginBadCredentialsException $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(CApiErrorCodes::Fetcher_AuthError, $oException));
		}
		catch (Exception $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(CApiErrorCodes::Fetcher_AuthError, $oException));
		}
		return $mResult;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return array|bool
	 */
	public function getFetchers($oAccount)
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->getFetchers($oAccount);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param int $iFetcherID
	 *
	 * @return bool
	 */
	public function deleteFetcher($oAccount, $iFetcherID)
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->deleteFetcher($oAccount, $iFetcherID);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param CFetcher $oFetcher
	 *
	 * @return bool
	 */
	public function updateFetcher($oAccount, $oFetcher)
	{
		$mResult = false;
		try
		{
			$this->getTestPop3Client($oFetcher);
			$mResult = $this->oStorage->updateFetcher($oAccount, $oFetcher);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		catch (\MailSo\Net\Exceptions\ConnectionException $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(CApiErrorCodes::Fetcher_ConnectToMailServerFailed, $oException));
		}
		catch (\MailSo\Pop3\Exceptions\LoginBadCredentialsException $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(CApiErrorCodes::Fetcher_AuthError, $oException));
		}
		catch (Exception $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(CApiErrorCodes::Fetcher_AuthError, $oException));
		}
		return $mResult;
	}
}
