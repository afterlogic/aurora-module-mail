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
 * @package Mail
 * @subpackage Enum
 */
class MessageListSortType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Date = 0;
	const From_ = 1;
	const To_ = 2;
	const Subject = 3;
	const Size = 4;
}
