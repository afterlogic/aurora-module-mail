<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersDbStorage extends CApiMailFetchersStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiFetchersCommandCreatorMySQL
	 */
	protected $oCommandCreator;

	/**
	 * @param \Aurora\System\Managers\GlobalManager &$oManager
	 */
	public function __construct(\Aurora\System\Managers\AbstractManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				\Aurora\System\Enums\DbType::MySQL => 'CApiMailFetchersCommandCreatorMySQL',
				\Aurora\System\Enums\DbType::PostgreSQL => 'CApiMailFetchersCommandCreatorPostgreSQL'
			)
		);
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 *
	 * @return array|bool
	 */
	public function getFetchers($oAccount)
	{
		$mResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getFetchers($oAccount)))
		{
			$oRow = null;
			$mResult = array();

			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$oFetcher = new CFetcher($oAccount);
				$oFetcher->InitByDbRow($oRow);
				
				$mResult[] = $oFetcher;
			}
		}

		$this->throwDbExceptionIfExist();
		return $mResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param CFetcher $oFetcher
	 * @return bool
	 */
	public function createFetcher($oAccount, &$oFetcher)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->createFetcher($oAccount, $oFetcher)))
		{
			$oFetcher->IdFetcher = $this->oConnection->GetLastInsertId('awm_fetchers', 'id_fetcher');
			$bResult = true;
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param CFetcher $oFetcher
	 *
	 * @return bool
	 */
	public function updateFetcher($oAccount, $oFetcher)
	{
		$bResult = (bool) $this->oConnection->Execute($this->oCommandCreator->updateFetcher($oAccount, $oFetcher));

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param int $iFetcherID
	 *
	 * @return bool
	 */
	public function deleteFetcher($oAccount, $iFetcherID)
	{
		$bResult = (bool) $this->oConnection->Execute($this->oCommandCreator->deleteFetcher($oAccount, $iFetcherID));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}
}
