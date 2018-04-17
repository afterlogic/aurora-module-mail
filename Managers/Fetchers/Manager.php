<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Fetchers;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 */
class Manager extends \Aurora\System\Managers\AbstractManagerWithStorage
{
	/**
	 * 
	 * @param \Aurora\System\Managers\AbstractManager $oManager
	 */
	public function __construct($sForcedStorage = '', \Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($sForcedStorage, $oModule);
	}

	/**
	 * @param \Aurora\Modules\Mail\Classes\Fetcher $oFetcher
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
			$oPop3Client->SetLogger(\Aurora\System\Api::SystemLogger());
			
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param \Aurora\Modules\Mail\Classes\Fetcher $oFetcher
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
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\ErrorCodes::Fetcher_ConnectToMailServerFailed, $oException));
		}
		catch (\MailSo\Pop3\Exceptions\LoginBadCredentialsException $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\ErrorCodes::Fetcher_AuthError, $oException));
		}
		catch (Exception $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\ErrorCodes::Fetcher_AuthError, $oException));
		}
		return $mResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param \Aurora\Modules\Mail\Classes\Fetcher $oFetcher
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
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\ErrorCodes::Fetcher_ConnectToMailServerFailed, $oException));
		}
		catch (\MailSo\Pop3\Exceptions\LoginBadCredentialsException $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\ErrorCodes::Fetcher_AuthError, $oException));
		}
		catch (Exception $oException)
		{
			$this->setLastException(new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\ErrorCodes::Fetcher_AuthError, $oException));
		}
		return $mResult;
	}
}
