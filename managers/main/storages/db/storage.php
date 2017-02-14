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
 * CApiMailDbStorage class for work with database.
 * 
 * @internal
 * 
 * @package Mail
 * @subpackage Storages
 */
class CApiMailMainDbStorage extends CApiMailMainStorage
{
	/**
	 * Object for work with database connection.
	 * 
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * Object for generating query strings.
	 * 
	 * @var CApiMailCommandCreator
	 */
	protected $oCommandCreator;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiMailMainCommandCreatorMySQL',
				EDbType::PostgreSQL => 'CApiMailMainCommandCreatorPostgreSQL'
			)
		);
	}
	
	/**
	 * Obtains folders order.
	 * 
	 * @param CAccount $oAccount Account object.
	 *
	 * @return array
	 */
	public function getFoldersOrder($oAccount)
	{
		$aList = array();
		if ($this->oConnection->Execute($this->oCommandCreator->getSelectFoldersOrderQuery($oAccount)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$sOrder = $oRow->folders_order;
				if (!empty($sOrder))
				{
					$aOrder = @json_decode($sOrder, 3);
					if (is_array($aOrder) && 0 < count($aOrder))
					{
						$aList = $aOrder;
					}
				}
			}

			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $aList;
	}

	/**
	 * Clears information about folders order.
	 * 
	 * @param CAccount $oAccount Account object.
	 *
	 * @return bool
	 */
	public function clearFoldersOrder($oAccount)
	{
		$this->oConnection->Execute($this->oCommandCreator->getClearFoldersOrderQuery($oAccount));
		$this->throwDbExceptionIfExist();
		return true;
	}
	
	/**
	 * Updates information about folders order.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param array $aOrder New folders order.
	 *
	 * @return bool
	 */
	public function updateFoldersOrder($oAccount, $aOrder)
	{
		if (!is_array($aOrder))
		{
			return false;
		}
		
		$this->clearFoldersOrder($oAccount);
		
		$this->oConnection->Execute($this->oCommandCreator->getUpdateFoldersOrderQuery($oAccount, @json_encode($aOrder)));
		$this->throwDbExceptionIfExist();
		
		return true;
	}
}