<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail;

use Aurora\System\SettingsProperty;

/**
 * @property bool $Disabled
 * @property bool $AllowAddAccounts
 * @property bool $AllowAutosaveInDrafts
 * @property bool $AllowChangeMailQuotaOnMailServer
 * @property bool $AllowDefaultAccountForUser
 * @property bool $AllowIdentities
 * @property bool $OnlyUserEmailsInIdentities
 * @property bool $AllowFilters
 * @property bool $AllowForward
 * @property bool $AllowAutoresponder
 * @property bool $EnableAllowBlockLists
 * @property int $DefaultSpamScore
 * @property bool $ConvertSpamScoreToSpamLevel
 * @property string $SieveSpamRuleCondition
 * @property bool $AllowInsertImage
 * @property bool $AlwaysShowImagesInMessage
 * @property int $AutoSaveIntervalSeconds
 * @property bool $AllowTemplateFolders
 * @property bool $AllowInsertTemplateOnCompose
 * @property int $MaxTemplatesCountOnCompose
 * @property bool $AllowAlwaysRefreshFolders
 * @property bool $DisplayInlineCss
 * @property bool $CleanupOutputBeforeDownload
 * @property bool $IgnoreImapSubscription
 * @property int $ImageUploadSizeLimit
 * @property bool $SaveRepliesToCurrFolder
 * @property string $SieveGeneralPassword
 * @property string $SieveFileName
 * @property string $SieveFiltersFolderCharset
 * @property string $OverriddenSieveHost
 * @property int $SievePort
 * @property bool $UseBodyStructuresForHasAttachmentsSearch
 * @property bool $UseDateFromHeaders
 * @property string $XMailerValue
 * @property string $ForwardedFlagName
 * @property bool $PreferStarttls
 * @property string $ExternalHostNameOfLocalImap
 * @property string $ExternalHostNameOfLocalSmtp
 * @property bool $AutocreateMailAccountOnNewUserFirstLogin
 * @property bool $SieveUseStarttls
 * @property bool $DisableStarttlsForLocalhost
 * @property string $XOriginatingIPHeaderName
 * @property bool $DoImapLoginOnAccountCreate
 * @property array $MessagesSortBy
 * @property bool $CreateHtmlLinksFromTextLinksInDOM
 * @property bool $SieveCheckScript
 * @property int $MessagesInfoChunkSize
 * @property int $ExpiredLinkLifetimeMinutes
 * @property bool $AllowUnifiedInbox
 * @property bool $UseIdentityEmailAsSmtpMailFrom
 * @property bool $AllowScheduledAutoresponder
 * @property bool $ImapSendOriginatingIP
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                "Setting to true disables the module",
            ),
            "AllowAddAccounts" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, Add New Account button is displayed in Email Accounts area of Settings",
            ),
            "AllowAutosaveInDrafts" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, message being composed is periodically saved to Drafts folder",
            ),
            "AllowChangeMailQuotaOnMailServer" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, allows setting email account quota in admin area",
            ),
            "AllowDefaultAccountForUser" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, a user has a default account and its email adress equals user's PublicId",
            ),
            "AllowIdentities" => new SettingsProperty(
                true,
                "bool",
                null,
                "Enables using identities on composing mails and managing them in Settings area",
            ),
            "OnlyUserEmailsInIdentities" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, email address cannot be changed when adding or editing an identity",
            ),
            "AllowFilters" => new SettingsProperty(
                true,
                "bool",
                null,
                "Enables Filters tab in account settings",
            ),
            "AllowForward" => new SettingsProperty(
                true,
                "bool",
                null,
                "Enables Forward tab in account settings",
            ),
            "AllowAutoresponder" => new SettingsProperty(
                true,
                "bool",
                null,
                "Enables Autoresponder tab in account settings",
            ),
            "AllowScheduledAutoresponder" => new SettingsProperty(
                false,
                "bool",
                null,
                "Enables scheduled Autoresponder",
            ),
            "EnableAllowBlockLists" => new SettingsProperty(
                false,
                "bool",
                null,
                "Enables the feature of allow/block lists (requires advanced maiserver configuration to work)",
            ),
            "DefaultSpamScore" => new SettingsProperty(
                5,
                "int",
                null,
                "Default value for per-user spam score",
            ),
            "ConvertSpamScoreToSpamLevel" => new SettingsProperty(
                false,
                "bool",
                null,
                "Enables converting spamscore to spamlevel for per-user spam settings",
            ),
            "SieveSpamRuleCondition" => new SettingsProperty(
                "allof ( not header :matches \"X-Spam-Score\" \"-*\", header :value \"ge\" :comparator \"i;ascii-numeric\" \"X-Spam-Score\" \"{{Value}}\" )",
                "string",
                null,
                "Defines rule for moving mails to spam, used within per-user spam settings",
            ),
            "AllowInsertImage" => new SettingsProperty(
                true,
                "bool",
                null,
                "Enables toolbar button for inserting an image when editing message text",
            ),
            "AlwaysShowImagesInMessage" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, external images are always displayed when viewing email messages",
            ),
            "AutoSaveIntervalSeconds" => new SettingsProperty(
                60,
                "int",
                null,
                "Defines interval for saving messages to Drafts periodically, in seconds",
            ),
            "AllowTemplateFolders" => new SettingsProperty(
                false,
                "bool",
                null,
                "Allows for setting a folder as template one under Manage Folders screen",
            ),
            "AllowInsertTemplateOnCompose" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, a tool for inserting a template is added on message compose screen",
            ),
            "MaxTemplatesCountOnCompose" => new SettingsProperty(
                100,
                "int",
                null,
                "A limit of number of email templates available for selecting on message compose screen",
            ),
            "AllowAlwaysRefreshFolders" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, each folder in Manage Folders screen gets an option whether it should ",
            ),
            "DisplayInlineCss" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, CSS of a message will be converted to inline one when displaying message",
            ),
            "CleanupOutputBeforeDownload" => new SettingsProperty(
                false,
                "bool",
                null,
                "Discard any data in the output buffer if possible, prior to downloading attachment",
            ),
            "IgnoreImapSubscription" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, all the email folders can be accessed, regardless of their IMAP subscription status; also, a tool for hiding/showing folder is removed from Manage Folders screen",
            ),
            "ImageUploadSizeLimit" => new SettingsProperty(
                0,
                "int",
                null,
                "Sets a limit of image file size for upload, or 0 for unlimited",
            ),
            "SaveRepliesToCurrFolder" => new SettingsProperty(
                false,
                "bool",
                null,
                "Defines a default behavior for saving replies to the original folder of the message, can be changed in user settings",
            ),
            "SieveGeneralPassword" => new SettingsProperty(
                "",
                "string",
                null,
                "Defines ManageSieve access password if it's required by the agent",
            ),
            "SieveFileName" => new SettingsProperty(
                "sieve",
                "string",
                null,
                "Defines default filename of Sieve rules file",
            ),
            "SieveFiltersFolderCharset" => new SettingsProperty(
                "utf-8",
                "string",
                null,
                "Defines charset used in Sieve rules files",
            ),
            "OverriddenSieveHost" => new SettingsProperty(
                "",
                "string",
                null,
                "If set, this value will be used as ManageSieve host - by default, IMAP host value is used for this",
            ),
            "SievePort" => new SettingsProperty(
                4190,
                "int",
                null,
                "Defines port number for accessing ManageSieve agent",
            ),
            "UseBodyStructuresForHasAttachmentsSearch" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, attempts to use IMAP bodystructure to perform search for messages that have attachments",
            ),
            "UseDateFromHeaders" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, date and time information for displaying a message is obtained from message headers, not from IMAP envelopes",
            ),
            "XMailerValue" => new SettingsProperty(
                "Afterlogic webmail client",
                "string",
                null,
                "If set, defines a value for X-Mailer header added to messages sent out",
            ),
            "ForwardedFlagName" => new SettingsProperty(
                "\$Forwarded",
                "string",
                null,
                "Defines a flag used to show message as Forwarded one",
            ),
            "PreferStarttls" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, STARTTLS will automatically be used if mail server advertises its support",
            ),
            "ExternalHostNameOfLocalImap" => new SettingsProperty(
                "",
                "string",
                null,
                "Default external hostname for IMAP server, used by autodiscover",
            ),
            "ExternalHostNameOfLocalSmtp" => new SettingsProperty(
                "",
                "string",
                null,
                "Default external hostname for SMTP server, used by autodiscover",
            ),
            "AutocreateMailAccountOnNewUserFirstLogin" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, a new account will be created in the database on first successful login",
            ),
            "SieveUseStarttls" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, STARTTLS will automatically be used to communicate to Sieve server",
            ),
            "DisableStarttlsForLocalhost" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, STARTTLS will not be used for a local mailserver",
            ),
            "XOriginatingIPHeaderName" => new SettingsProperty(
                "X-Original-IP",
                "string",
                null,
                "If set, defines a name of header included into outgoing mail, header's value is IP address of the sender",
            ),
            "DoImapLoginOnAccountCreate" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, logging onto IMAP will be performed when adding a new account in adminpanel",
            ),
            "MessagesSortBy" => new SettingsProperty(
                [
                    "Allow" => false,
                    "List" => [
                        [
                            "SortBy" => "arrival",
                            "LangConst" => "LABEL_SORT_BY_DATE"
                        ],
                        [
                            "SortBy" => "from",
                            "LangConst" => "LABEL_SORT_BY_FROM"
                        ],
                        [
                            "SortBy" => "to",
                            "LangConst" => "LABEL_SORT_BY_TO"
                        ],
                    ],
                    "DefaultSortBy" => "arrival",
                    "DefaultSortOrder" => "desc"
                ],
                "array",
                null,
                "Defines a set of rules for sorting mail",
            ),
            "CreateHtmlLinksFromTextLinksInDOM" => new SettingsProperty(
                false,
                "bool",
                null,
                "If set to true, address@domain texts are replaced with links in HTML; in plaintext, they're always shown as links",
            ),
            "SieveCheckScript" => new SettingsProperty(
                false,
                "bool",
                null,
                "If set, CHECKSCRIPT will be run prior to applying Sieve file changes",
            ),
            "MessagesInfoChunkSize" => new SettingsProperty(
                1000,
                "int",
                null,
                "No longer used",
            ),
            "ExpiredLinkLifetimeMinutes" => new SettingsProperty(
                30,
                "int",
                null,
                "Lifetime of link expiration. Reserved for use by alternative frontend clients",
            ),
            "AllowUnifiedInbox" => new SettingsProperty(
                true,
                "bool",
                null,
                "Enables Unified Inbox feature",
            ),
            "UseIdentityEmailAsSmtpMailFrom" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, email address of the identity will be used in MAIL FROM command to SMTP server, instead of main email address of the account",
            ),
            "ImapSendOriginatingIP" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, Aurora will send originating IP address in IMAP ID command",
            ),
        ];
    }
}
