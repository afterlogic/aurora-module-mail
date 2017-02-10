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
 *
 * @package Users
 * @subpackage Classes
 */
class CMailServer extends AEntity
{
	/**
	 * Creates a new instance of the object.
	 * 
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct(get_class($this), 'Mail');
		
		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		$this->setStaticMap(array(
			'TenantId'			=> array('int',  0),
			'Name'				=> array('string', ''),
			'IncomingServer'	=> array('string', ''),
			'IncomingPort'		=> array('int',  143),
			'IncomingUseSsl'	=> array('bool', false),
			'OutgoingServer'	=> array('string', ''),
			'OutgoingPort'		=> array('int',  25),
			'OutgoingUseSsl'	=> array('bool', false),
			'OutgoingUseAuth'	=> array('bool', false),
		));
	}
	
	public static function createInstance()
	{
		return new CMailServer();
	}
}
