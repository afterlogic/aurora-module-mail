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
 * @property int $IdAccount
 * @property bool $Enable
 * @property int $Field
 * @property string $Filter
 * @property int $Condition
 * @property int $Action
 * @property string $FolderFullName
 *
 * @package Sieve
 * @subpackage Classes
 */
class CFilter extends api_AContainer
{
	/**
	 * @param CAccount $oAccount
	 */
	public function __construct(CAccount $oAccount)
	{
		parent::__construct(get_class($this));

		$this->SetDefaults(array(
			'IdAccount'	=> $oAccount->IdAccount,
			'Enable'	=> true,
			'Field'		=> EFilterFiels::From,
			'Filter'	=> '',
			'Condition'	=> EFilterCondition::ContainSubstring,
			'Action'	=> EFilterAction::DoNothing,
			'FolderFullName' => ''
		));
	}
	
	/**
	 * @return array
	 */
	public function getMap()
	{
		return self::getStaticMap();
	}

	/**
	 * @return array
	 */
	public static function getStaticMap()
	{
		return array(
			'IdAccount'	=> array('int'),
			'Enable'	=> array('bool'),
			'Field'		=> array('int'),
			'Filter'	=> array('string'),
			'Condition'	=> array('int'),
			'Action'	=> array('int'),
			'FolderFullName' => array('string')
		);
	}
	
	public function toResponseArray($aParameters = array())
	{
		return array(
			'Enable' => $this->Enable,
			'Field' => $this->Field,
			'Filter' => $this->Filter,
			'Condition' => $this->Condition,
			'Action' => $this->Action,
			'FolderFullName' => $this->FolderFullName,
		);		
	}
}
