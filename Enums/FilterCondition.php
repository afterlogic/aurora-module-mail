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
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package Sieve
 * @subpackage Enum
 */
class FilterCondition extends \Aurora\System\Enums\AbstractEnumeration
{
    public const ContainSubstring = 0;
    public const ContainExactPhrase = 1;
    public const NotContainSubstring = 2;
    public const StartFrom = 3;
}
