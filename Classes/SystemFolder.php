<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @property int $IdAccount
 * @property string $FolderFullName
 * @property int $Type
 *
 * @ignore
 * @package Mail
 * @subpackage Classes
 */
class SystemFolder extends \Aurora\System\EAV\Entity
{
	protected $aStaticMap = array(
		'IdAccount'			=> array('int', 0, true),
		'FolderFullName'	=> array('string', ''),
		'Type'				=> array('int', 0, true)
	);
}
