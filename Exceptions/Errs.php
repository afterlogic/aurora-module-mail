<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Aurora\Modules\Mail\Exceptions;

/**
 * Description of Errs
 *
 * @author sash
 */
class Errs {
	const FolderNameContainsDelimiter = 4001;
	const AccountAuthentication = 4002;
	const AccountConnectToMailServerFailed = 4003;
	const AccountLoginFailed = 4004;
	const InvalidRecipients = 4005;
	const CannotRenameNonExistenFolder = 4006;
	const CannotSendMessage = 4007;
	const CannotSaveMessageInSentItems = 4008;
	const MailboxUnavailable = 4009;
}
