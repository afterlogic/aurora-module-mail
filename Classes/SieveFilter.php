<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property int $IdAccount
 * @property bool $Enable
 * @property int $Field
 * @property string $Filter
 * @property int $Condition
 * @property int $Action
 * @property string $FolderFullName
 * @property string $Email
 *
 * @package Sieve
 * @subpackage Classes
 */
class SieveFilter
{
    public $IdAccount;
    public $Enable = true;
    public $Field = \Aurora\Modules\Mail\Enums\FilterFields::From;
    public $Filter = '';
    public $Condition = \Aurora\Modules\Mail\Enums\FilterCondition::ContainSubstring;
    public $Action = \Aurora\Modules\Mail\Enums\FilterAction::DoNothing;
    public $FolderFullName = '';
    public $Email = '';

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     */
    public function __construct(\Aurora\Modules\Mail\Models\MailAccount $oAccount)
    {
        $this->IdAccount = $oAccount->Id;
    }

    public function toResponseArray($aParameters = [])
    {
        return [
            'Enable' => $this->Enable,
            'Field' => $this->Field,
            'Filter' => $this->Filter,
            'Condition' => $this->Condition,
            'Action' => $this->Action,
            'FolderFullName' => $this->FolderFullName,
            'Email' => $this->Email,
        ];
    }
}
