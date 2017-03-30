<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

/**
 * @category Core
 * @package Base
 */
class DataByRef
{
	protected $aData;
	
	public static function createInstance($mData = null)
	{
		$oResult = new DataByRef();
		$oResult->aData = $mData;
		
		return $oResult;
	}
	
	public function getData()
	{
		return $this->aData;
	}

	public function setData($mData)
	{
		$this->aData = $mData;
	}
}
