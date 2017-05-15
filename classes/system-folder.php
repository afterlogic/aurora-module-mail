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
 * @property int $IdAccount
 * @property string $FolderFullName
 * @property int $Type
 *
 * @ignore
 * @package Mail
 * @subpackage Classes
 */
class CSystemFolder extends \Aurora\System\EAV\Entity
{
	protected $aStaticMap = array(
		'IdAccount'			=> array('int', 0),
		'FolderFullName'	=> array('string', ''),
		'Type'				=> array('int', 0)
	);
}
