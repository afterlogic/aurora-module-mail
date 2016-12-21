<?php
/**
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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
 * 
 * @package Modules
 */

/**
 * @package Mail
 * @subpackage Enum
 */
class EMailMessageListSortType extends AEnumeration
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
class EMailMessageStoreAction extends AEnumeration
{
	const Add = 0;
	const Remove = 1;
	const Set = 2;
}

/**
 * @package Mail
 * @subpackage Enum
 */
class EMailMessageFlag extends AEnumeration
{
	const Recent = '\Recent';
	const Seen = '\Seen';
	const Deleted = '\Deleted';
	const Flagged = '\Flagged';
	const Answered = '\Answered';
	const Draft = '\Draft';
}
