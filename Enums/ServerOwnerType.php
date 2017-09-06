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
 * @package Mail
 * @subpackage Enum
 */
class ServerOwnerType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Account = 'account';
	const Tenant = 'tenant';
	const SuperAdmin = 'superadmin';
}