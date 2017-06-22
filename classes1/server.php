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
		'EnableSieve'		=> array('bool', false),
		'SievePort'			=> array('int',  2000),
	);	

	public function toResponseArray()
	{
		$aResponse = parent::toResponseArray();
		$aResponse['ServerId'] = $this->EntityId;
		return $aResponse;
	}
}
