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
class CApiMailFetchersCommandCreator extends \Aurora\System\Db\CommandCreator
{
	/**
	 * @param CAccount $oAccount
	 * @return string
	 */
	public function getFetchers($oAccount)
	{
		$aMap = \Aurora\System\AbstractContainer::DbReadKeys(CFetcher::getStaticMap());
		$aMap = array_map(array($this, 'escapeColumn'), $aMap);

		$sSql = 'SELECT %s FROM %sawm_fetchers WHERE %s = %d';

		return sprintf($sSql, implode(', ', $aMap), $this->prefix(),
			$this->escapeColumn('id_acct'), $oAccount->EntityId);
	}

	/**
	 * @param CAccount $oAccount
	 * @param int $iFetcherID
	 * @return string
	 */
	public function deleteFetcher($oAccount, $iFetcherID)
	{
		$sSql = 'DELETE FROM %sawm_fetchers WHERE %s = %d AND %s = %d';
		return sprintf($sSql, $this->prefix(),
			$this->escapeColumn('id_acct'), $oAccount->EntityId,
			$this->escapeColumn('id_fetcher'), $iFetcherID);
	}

	/**
	 * @param CAccount $oAccount
	 * @param CFetcher $oFetcher
	 * @return string
	 */
	public function createFetcher($oAccount, $oFetcher)
	{
		$aResults = \Aurora\System\AbstractContainer::DbInsertArrays($oFetcher, $this->oHelper);

		if ($aResults[0] && $aResults[1])
		{
			$sSql = 'INSERT INTO %sawm_fetchers ( %s ) VALUES ( %s )';
			return sprintf($sSql, $this->prefix(), implode(', ', $aResults[0]), implode(', ', $aResults[1]));
		}
		
		return '';
	}

	/**
	 * @param CAccount $oAccount
	 * @param CFetcher $oFetcher
	 * @return string
	 */
	public function updateFetcher($oAccount, $oFetcher)
	{
		$aResult = \Aurora\System\AbstractContainer::DbUpdateArray($oFetcher, $this->oHelper);

		$sSql = 'UPDATE %sawm_fetchers SET %s WHERE %s = %d AND %s = %d';
		return sprintf($sSql, $this->prefix(), implode(', ', $aResult),
			$this->escapeColumn('id_acct'), $oAccount->EntityId,
			$this->escapeColumn('id_fetcher'), $oFetcher->IdFetcher);
	}
}

/**
  * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersCommandCreatorMySQL extends CApiMailFetchersCommandCreator
{
	
}

/**
 * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersCommandCreatorPostgreSQL extends CApiMailFetchersCommandCreator
{

}
