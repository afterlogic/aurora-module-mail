<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @property int $IdAccount
 * @property bool $Enable
 * @property int $Field
 * @property string $Filter
 * @property int $Condition
 * @property int $Action
 * @property string $FolderFullName
 *
 * @package Sieve
 * @subpackage Classes
 */
class SieveFilter extends \Aurora\System\AbstractContainer
{
	/**
	 * @param CAccount $oAccount
	 */
	public function __construct(\Aurora\Modules\Mail\Classes\Account $oAccount)
	{
		parent::__construct(get_class($this));

		$this->SetDefaults(array(
			'IdAccount'	=> $oAccount->EntityId,
			'Enable'	=> true,
			'Field'		=> \Aurora\Modules\Mail\Enums\FilterFields::From,
			'Filter'	=> '',
			'Condition'	=> \Aurora\Modules\Mail\Enums\FilterCondition::ContainSubstring,
			'Action'	=> \Aurora\Modules\Mail\Enums\FilterAction::DoNothing,
			'FolderFullName' => ''
		));
	}
	
	/**
	 * @return array
	 */
	public function getMap()
	{
		return self::getStaticMap();
	}

	/**
	 * @return array
	 */
	public static function getStaticMap()
	{
		return array(
			'IdAccount'	=> array('int'),
			'Enable'	=> array('bool'),
			'Field'		=> array('int'),
			'Filter'	=> array('string'),
			'Condition'	=> array('int'),
			'Action'	=> array('int'),
			'FolderFullName' => array('string')
		);
	}
	
	public function toResponseArray($aParameters = array())
	{
		return array(
			'Enable' => $this->Enable,
			'Field' => $this->Field,
			'Filter' => $this->Filter,
			'Condition' => $this->Condition,
			'Action' => $this->Action,
			'FolderFullName' => $this->FolderFullName,
		);		
	}
}
