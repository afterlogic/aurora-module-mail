<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Aurora\Modules\Mail;

/**
 * Description of Notifications
 *
 * @author sash
 */
class Notifications {
	const CanNotGetMessageList = 201;
	const CanNotGetMessage = 202;
	const CanNotDeleteMessage = 203;
	const CanNotMoveMessage = 204;
	const CanNotMoveMessageQuota = 205;
	const CanNotCopyMessage = 206;
	const CanNotCopyMessageQuota = 207;
	const LibraryNoFound = 208;

	const CanNotSaveMessage = 301;
	const CanNotSendMessage = 302;
	const InvalidRecipients = 303;
	const CannotSaveMessageInSentItems = 304;
	const UnableSendToRecipients = 305;
	const ExternalRecipientsBlocked = 306;

	const CanNotCreateFolder = 401;
	const CanNotDeleteFolder = 402;
	const CanNotSubscribeFolder = 403;
	const CanNotUnsubscribeFolder = 404;
}
