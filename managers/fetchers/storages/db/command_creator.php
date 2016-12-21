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
 * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersCommandCreator extends api_CommandCreator
{
	/**
	 * @param CAccount $oAccount
	 * @return string
	 */
	public function getFetchers($oAccount)
	{
		$aMap = api_AContainer::DbReadKeys(CFetcher::getStaticMap());
		$aMap = array_map(array($this, 'escapeColumn'), $aMap);

		$sSql = 'SELECT %s FROM %sawm_fetchers WHERE %s = %d';

		return sprintf($sSql, implode(', ', $aMap), $this->prefix(),
			$this->escapeColumn('id_acct'), $oAccount->IdAccount);
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
			$this->escapeColumn('id_acct'), $oAccount->IdAccount,
			$this->escapeColumn('id_fetcher'), $iFetcherID);
	}

	/**
	 * @param CAccount $oAccount
	 * @param CFetcher $oFetcher
	 * @return string
	 */
	public function createFetcher($oAccount, $oFetcher)
	{
		$aResults = api_AContainer::DbInsertArrays($oFetcher, $this->oHelper);

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
		$aResult = api_AContainer::DbUpdateArray($oFetcher, $this->oHelper);

		$sSql = 'UPDATE %sawm_fetchers SET %s WHERE %s = %d AND %s = %d';
		return sprintf($sSql, $this->prefix(), implode(', ', $aResult),
			$this->escapeColumn('id_acct'), $oAccount->IdAccount,
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
