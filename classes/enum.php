<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
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
