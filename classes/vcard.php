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
 * CApiMailIcs class is used for work with attachment that contains contact card.
 * 
 * @internal
 * 
 * @package Mail
 * @subpackage Classes
 */
class CApiMailVcard
{
	/**
	 * Contact identifier.
	 * 
	 * @var string
	 */
	public $Uid;

	/**
	 * Temp file name of the .vcf file.
	 * 
	 * @var string
	 */
	public $File;

	/**
	 * If **true** this contact already exists in address book.
	 * 
	 * @var bool
	 */
	public $Exists;

	/**
	 * Contact name.
	 * 
	 * @var string
	 */
	public $Name;

	/**
	 * Contact email.
	 * 
	 * @var string
	 */
	public $Email;

	private function __construct()
	{
		$this->Uid = '';
		$this->File = '';
		$this->Exists = false;
		$this->Name = '';
		$this->Email = '';
	}

	/**
	 * Creates new empty instance.
	 * 
	 * @return CApiMailVcard
	 */
	public static function createInstance()
	{
		return new self();
	}
	
	public function toResponseArray($aParameters = array())
	{
		return array(
			'Uid' => $this->Uid,
			'File' => $this->File,
			'Name' => $this->Name,
			'Email' => $this->Email,
			'Exists' => $this->Exists
		);		
	}
}
