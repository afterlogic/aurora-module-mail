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
 * @package Sieve
 * @subpackage Enum
 */
class EFilterFiels extends \AbstractEnumeration
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
class EFilterCondition extends \AbstractEnumeration
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
class EFilterAction extends \AbstractEnumeration
{
	const DoNothing = 0;
	const DeleteFromServerImmediately = 1;
	const MarkGrey = 2;
	const MoveToFolder = 3;
	const MoveToSpamFolder = 4;
	const SpamDetect = 5;
	const VirusDetect = 6;
}
