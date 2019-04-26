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
 * @property int $IdUser Identifier of user wich contains the identity.
 * @property int $IdAccount Identifier of account wich contains the identity.
 * @property bool $Default
 * @property string $Email Email of identity.
 * @property string $FriendlyName Display name of identity.
 * @property bool $UseSignature If **true** and this identity is used for message sending the identity signature will be attached to message body.
 * @property string $Signature Signature of identity.
 *
 * @package Users
 * @subpackage Classes
 */
class Identity extends \Aurora\System\EAV\Entity
{
	protected $aStaticMap = array(
		'IdUser'		=> array('int', 0, true),
		'IdAccount'		=> array('int', 0, true),
		'Default'		=> array('bool', false),
		'Email'			=> array('string', ''),
		'FriendlyName'	=> array('string', '', true),
		'UseSignature'	=> array('bool', false),
		'Signature'		=> array('text', ''),
	);
}
