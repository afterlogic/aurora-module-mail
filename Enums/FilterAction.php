<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Enums;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Sieve
 * @subpackage Enum
 */
class FilterAction extends \Aurora\System\Enums\AbstractEnumeration
{
	const DoNothing = 0;
	const DeleteFromServerImmediately = 1;
	const MarkGrey = 2;
	const MoveToFolder = 3;
	const MoveToSpamFolder = 4;
	const SpamDetect = 5;
	const VirusDetect = 6;
}
