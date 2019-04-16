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
 * @package Sieve
 * @subpackage Enum
 */
class FilterFields extends \Aurora\System\Enums\AbstractEnumeration
{
	const From = 0;
	const To = 1;
	const Subject = 2;
	const XSpam = 3;
	const XVirus = 4;
	const CustomHeader = 5;
}
