<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Enums;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 * @subpackage Enum
 */
class FolderType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Inbox = 1;
	const Sent = 2;
	const Drafts = 3;
	const Spam = 4;
	const Trash = 5;
	const Virus = 6;
	const Template = 8;
	const System = 9;
	const Custom = 10;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Inbox' => self::Inbox,
		'Sent' => self::Sent,
		'Drafts' => self::Drafts,
		'Spam' => self::Spam,
		'Trash' => self::Trash,
		'Quarantine' => self::Virus,
		'System' => self::System,
		'Custom' => self::Custom
	);
}