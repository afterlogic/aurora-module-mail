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
 */

/**
 *
 * @package Users
 * @subpackage Classes
 */
class CMailServer extends \Aurora\System\EAV\Entity
{
	protected $aStaticMap = array(
		'TenantId'			=> array('int',  0),
		'Name'				=> array('string', ''),
		'IncomingServer'	=> array('string', ''),
		'IncomingPort'		=> array('int',  143),
		'IncomingUseSsl'	=> array('bool', false),
		'OutgoingServer'	=> array('string', ''),
		'OutgoingPort'		=> array('int',  25),
		'OutgoingUseSsl'	=> array('bool', false),
		'OutgoingUseAuth'	=> array('bool', false),
		'OwnerType'			=> array('string', \EMailServerOwnerType::Account),
		'Domains'	 		=> array('text', ''),
		'Internal'			=> array('bool', false)
	);	

	public function toResponseArray()
	{
		$aResponse = parent::toResponseArray();
		$aResponse['ServerId'] = $this->EntityId;
		return $aResponse;
	}
}
