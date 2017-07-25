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
 * @subpackage Enum
 */
class SmtpAuthType extends \Aurora\System\Enums\AbstractEnumeration
{
	const NoAuthentication = '0';
	const UseSpecifiedCredentials = '1';
	const UseUserCredentials = '2';
	
	protected $aConsts = array(
		'NoAuthentication' => self::NoAuthentication,
		'UseSpecifiedCredentials' => self::UseSpecifiedCredentials,
		'UseUserCredentials' => self::UseUserCredentials
	);
}
