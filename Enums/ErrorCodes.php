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
 */
class ErrorCodes
{
    public const CannotConnectToMailServer = 4001;
    public const CannotLoginCredentialsIncorrect = 4002;
    public const FolderAlreadyExists = 4003;
    public const FolderNameContainsDelimiter = 4004;
    public const CannotRenameNonExistenFolder = 4005;
    public const CannotGetMessage = 4006;
    public const CannotMoveMessage = 4007;
    public const CannotMoveMessageQuota = 4008; // is used on client side
    public const CannotSendMessage = 4009;
    public const CannotSendMessageInvalidRecipients = 4010;
    public const CannotSendMessageToRecipients = 4011;
    public const CannotSendMessageToExternalRecipients = 4012;
    public const CannotSaveMessage = 4013;
    public const CannotSaveMessageToSentItems = 4014;
    public const CannotUploadMessage = 4015;
    public const CannotUploadMessageFileNotEml = 4016;
    public const DomainIsNotAllowedForLoggingIn = 4017;
    public const TenantQuotaExceeded = 4018;

    /**
     * @var array
     */
    protected $aConsts = [
        'CannotConnectToMailServer' => self::CannotConnectToMailServer,
        'CannotLoginCredentialsIncorrect' => self::CannotLoginCredentialsIncorrect,
        'FolderAlreadyExists' => self::FolderAlreadyExists,
        'FolderNameContainsDelimiter' => self::FolderNameContainsDelimiter,
        'CannotRenameNonExistenFolder' => self::CannotRenameNonExistenFolder,
        'CannotGetMessage' => self::CannotGetMessage,
        'CannotMoveMessage' => self::CannotMoveMessage,
        'CannotMoveMessageQuota' => self::CannotMoveMessageQuota,
        'CannotSendMessage' => self::CannotSendMessage,
        'CannotSendMessageInvalidRecipients' => self::CannotSendMessageInvalidRecipients,
        'CannotSendMessageToRecipients' => self::CannotSendMessageToRecipients,
        'CannotSendMessageToExternalRecipients' => self::CannotSendMessageToExternalRecipients,
        'CannotSaveMessage' => self::CannotSaveMessage,
        'CannotSaveMessageToSentItems' => self::CannotSaveMessageToSentItems,
        'CannotUploadMessage' => self::CannotUploadMessage,
        'CannotUploadMessageFileNotEml' => self::CannotUploadMessageFileNotEml,
        'DomainIsNotAllowedForLoggingIn' => self::DomainIsNotAllowedForLoggingIn,
        'TenantQuotaExceeded' => self::TenantQuotaExceeded,
    ];
}
