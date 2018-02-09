<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
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

namespace Aurora\Modules\Mail\Classes;

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
