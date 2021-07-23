<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Users
 * @subpackage Classes
 */
class Server extends \Aurora\System\EAV\Entity
{
	protected $aStaticMap = array(
		'TenantId'			=> array('int',  0, true),
		'Name'				=> array('string', '', true),
		'IncomingServer'	=> array('string', ''),
		'IncomingPort'		=> array('int',  143),
		'IncomingUseSsl'	=> array('bool', false),
		'OutgoingServer'	=> array('string', ''),
		'OutgoingPort'		=> array('int',  25),
		'OutgoingUseSsl'	=> array('bool', false),
		'SmtpAuthType'		=> array('string', \Aurora\Modules\Mail\Enums\SmtpAuthType::NoAuthentication),
		'SmtpLogin'			=> array('string', ''),
		'SmtpPassword'		=> array('encrypted', ''),
		'OwnerType'			=> array('string', \Aurora\Modules\Mail\Enums\ServerOwnerType::Account, true),
		'Domains'	 		=> array('text', '', true),
		'EnableSieve'		=> array('bool', false),
		'SievePort'			=> array('int',  4190),
		'EnableThreading'	=> array('bool', true),
		'UseFullEmailAddressAsLogin'	=> array('bool', true),
		'SetExternalAccessServers'		=> array('bool', false),
		'ExternalAccessImapServer'		=> array('string', ''),
		'ExternalAccessImapPort'		=> array('int',  143),
		'ExternalAccessImapAlterPort'	=> array('int',  0),
		'ExternalAccessPop3Server'		=> array('string', ''),
		'ExternalAccessPop3Port'		=> array('int',  110),
		'ExternalAccessPop3AlterPort'	=> array('int',  0),
		'ExternalAccessSmtpServer'		=> array('string', ''),
		'ExternalAccessSmtpPort'		=> array('int',  25),
		'ExternalAccessSmtpAlterPort'	=> array('int',  0),
		'OAuthEnable'					=> array('bool', false),
		'OAuthName'						=> array('string', ''),
		'OAuthType'						=> array('string', ''),
		'OAuthIconUrl'					=> array('string', '')
	);

	public function toResponseArray()
	{
		$aResponse = parent::toResponseArray();
		$aResponse['ServerId'] = $this->EntityId;

		$aArgs = [];
		\Aurora\System\Api::GetModule('Mail')->broadcastEvent(
			'ServerToResponseArray',
			$aArgs,
			$aResponse
		);
		return $aResponse;
	}
}
