<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Enums;

/**
 * @package Sieve
 * @subpackage Enum
 */
class FilterCondition extends \Aurora\System\Enums\AbstractEnumeration
{
	const ContainSubstring = 0;
	const ContainExactPhrase = 1;
	const NotContainSubstring = 2;
	const StartFrom = 3;
}