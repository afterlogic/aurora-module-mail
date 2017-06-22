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
class CIdentity extends \Aurora\System\EAV\Entity
{
	protected $aStaticMap = array(
		'IdUser'		=> array('int', 0),
		'IdAccount'		=> array('int', 0),
		'Default'		=> array('bool', false),
		'Email'			=> array('string', ''),
		'FriendlyName'	=> array('string', ''),
		'UseSignature'	=> array('bool', false),
		'Signature'		=> array('string', ''),
	);
}
