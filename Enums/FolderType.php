<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Enums;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package Api
 * @subpackage Enum
 */
class FolderType extends \Aurora\System\Enums\AbstractEnumeration
{
    public const Inbox = 1;
    public const Sent = 2;
    public const Drafts = 3;
    public const Spam = 4;
    public const Trash = 5;
    public const Virus = 6;
    public const Template = 8;
    public const System = 9;
    public const Custom = 10;
    public const All = 11;

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
        'Custom' => self::Custom,
        'All' => self::All
    );
}
