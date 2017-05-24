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
 * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersStorage extends \Aurora\System\Managers\AbstractManagerStorage
{
	/**
	 * @param \Aurora\System\Managers\GlobalManager &$oManager
	 */
	public function __construct($sStorageName, \Aurora\System\Managers\AbstractManager &$oManager)
	{
		parent::__construct('fetchers', $sStorageName, $oManager);
	}
}