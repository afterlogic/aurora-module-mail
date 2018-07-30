<?php
/*
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Enums;

class ErrorCodes
{
	const CannotConnectToMailServer = 4001;
	const CannotLoginCredentialsIncorrect = 4002;
	const FolderAlreadyExists = 4003;
	const FolderNameContainsDelimiter = 4004;
	const CannotRenameNonExistenFolder = 4005;
	const CannotGetMessage = 4006;
	const CannotMoveMessage = 4007;
	const CannotMoveMessageQuota = 4008; // is used on client side
	const CannotSendMessage = 4009;
	const CannotSendMessageInvalidRecipients = 4010;
	const CannotSendMessageToRecipients = 4011;
	const CannotSendMessageToExternalRecipients = 4012;
	const CannotSaveMessage = 4013;
	const CannotSaveMessageToSentItems = 4014;
	const CannotUploadMessage = 4015;
	const CannotUploadMessageFileNotEml = 4016;
	const DomainIsNotAllowedForLoggingIn = 4017;

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
	];
}
