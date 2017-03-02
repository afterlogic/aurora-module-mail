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
 * 
 * @package Modules
 */

/**
 * @package Sieve
 * @subpackage Enum
 */
class EFilterFiels extends AbstractEnumeration
{
	const From = 0;
	const To = 1;
	const Subject = 2;
	const XSpam = 3;
	const XVirus = 4;
	const CustomHeader = 5;
}

/**
 * @package Sieve
 * @subpackage Enum
 */
class EFilterCondition extends AbstractEnumeration
{
	const ContainSubstring = 0;
	const ContainExactPhrase = 1;
	const NotContainSubstring = 2;
	const StartFrom = 3;
}

/**
 * @package Sieve
 * @subpackage Enum
 */
class EFilterAction extends AbstractEnumeration
{
	const DoNothing = 0;
	const DeleteFromServerImmediately = 1;
	const MarkGrey = 2;
	const MoveToFolder = 3;
	const MoveToSpamFolder = 4;
	const SpamDetect = 5;
	const VirusDetect = 6;
}
