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
 * @package Mail
 * @subpackage Enum
 */
class EMailMessageListSortType extends \AbstractEnumeration
{
	const Date = 0;
	const From_ = 1;
	const To_ = 2;
	const Subject = 3;
	const Size = 4;
}

/**
 * @package Mail
 * @subpackage Enum
 */
class EMailMessageStoreAction extends \AbstractEnumeration
{
	const Add = 0;
	const Remove = 1;
	const Set = 2;
}

/**
 * @package Mail
 * @subpackage Enum
 */
class EMailMessageFlag extends \AbstractEnumeration
{
	const Recent = '\Recent';
	const Seen = '\Seen';
	const Deleted = '\Deleted';
	const Flagged = '\Flagged';
	const Answered = '\Answered';
	const Draft = '\Draft';
}

/**
 * @package Api
 * @subpackage Enum
 */
class EFolderType extends \AbstractEnumeration
{
	const Inbox = 1;
	const Sent = 2;
	const Drafts = 3;
	const Spam = 4;
	const Trash = 5;
	const Virus = 6;
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

/**
 * @package Mail
 * @subpackage Enum
 */
class EMailServerOwnerType extends \AbstractEnumeration
{
	const Account = 'account';
	const Tenant = 'tenant';
	const SuperAdmin = 'superadmin';
}
