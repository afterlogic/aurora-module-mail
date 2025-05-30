<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

use Dom\XMLDocument;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package Mail
 * @subpackage Classes
 */
class Message
{
    /**
     * Account Id.
     *
     * @var int
     */
    protected $iAccountId;

    /**
     * Raw full name of the folder the message resides in.
     *
     * @var string
     */
    protected $sFolder;

    /**
     * UID value of the message.
     *
     * @var int
     */
    protected $iUid;

    /**
     * Unified UID value of the message.
     *
     * @var int
     */
    protected $sUnifiedUid;

    /**
     * Subject of the message.
     *
     * @var string
     */
    protected $sSubject;

    /**
     * Value of Message-ID header.
     *
     * @var string
     */
    protected $sMessageId;

    /**
     * Content-Type value of the message.
     *
     * @var string
     */
    protected $sContentType;

    /**
     * Message size in bytes.
     *
     * @var int
     */
    protected $iSize;

    /**
     * Total size of text parts of the message in bytes.
     *
     * @var int
     */
    protected $iTextSize;

    protected $bTruncated;

    /**
     * Timestamp information of the message in UTC/GMT.
     *
     * @var int
     */
    protected $iInternalTimeStampInUTC;

    /**
     * UTC timestamp of the message, checking through various sources including Received and Date headers.
     *
     * @var int
     */
    protected $iReceivedOrDateTimeStampInUTC;

    /**
     * List of message flags.
     *
     * @var array
     */
    protected $aFlags;

    /**
     * List of message flags converted to lower case.
     *
     * @var array
     */
    protected $aFlagsLowerCase;

    /**
     * List of From addresses in the message. Though in most cases it's just one address, it's possible to have multiple From entries in the same message.
     *
     * @var \MailSo\Mime\EmailCollection
     */
    protected $oFrom;

    /**
     * List of Sender addresses in the message. Though in most cases it's just one address, it's possible to have multiple Sender entries in the same message.
     *
     * @var \MailSo\Mime\EmailCollection
     */
    protected $oSender;

    /**
     * Value of Reply-To header.
     *
     * @var \MailSo\Mime\EmailCollection
     */
    protected $oReplyTo;

    /**
     * List of To addresses of the message.
     *
     * @var \MailSo\Mime\EmailCollection
     */
    protected $oTo;

    /**
     * List of CC addresses of the message.
     *
     * @var \MailSo\Mime\EmailCollection
     */
    protected $oCc;

    /**
     * List of BCC addresses of the message.
     *
     * @var \MailSo\Mime\EmailCollection
     */
    protected $oBcc;

    /**
     * Value of **In-Reply-To** header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.
     *
     * @var string
     */
    protected $sInReplyTo;

    /**
     * Content of References header block of the message.
     *
     * @var string
     */
    protected $sReferences;

    /**
     * Plaintext body of the message.
     *
     * @var string
     */
    protected $sPlain;

    /**
     * HTML body of the message.
     *
     * @var string
     */
    protected $sHtml;

    /**
     * If Sensitivity header was set for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal".
     *
     * @var int
     */
    protected $iSensitivity;

    /**
     * Importance value of the message, from 1 (highest) to 5 (lowest).
     *
     * @var int
     */
    protected $iImportance;

    /**
     * Email address reading confirmation is to be sent to.
     *
     * @var string
     */
    protected $sReadingConfirmation;

    /**
     * Indication of whether the sender is trustworthy so it's safe to display external images.
     *
     * @var bool
     */
    protected $bSafety;

    /**
     * Information about attachments of the message.
     *
     * @var \Aurora\Modules\Mail\Classes\AttachmentCollection
     */
    protected $oAttachments;

    /**
     * Information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
     *
     * @var array
     */
    private $aDraftInfo;

    /**
     * List of custom content, implemented for use of iCal/vCard content.
     *
     * @var array
     */
    private $aExtend;

    /**
     * List of custom values in message object, implemented for use by plugins.
     *
     * @var array
     */
    private $aCustom;

    /**
     * Threading information such as UIDs of messages in the thread.
     *
     * @var array
     */
    private $aThreads;

    /**
     * @var string
     */
    private $sHeaders;

    /**
     * @return void
     */
    protected function __construct()
    {
        $this->clear();
    }

    /**
     * Clears all the properties of the message.
     *
     * @return Message
     */
    public function clear()
    {
        $this->iAccountId = null;
        $this->sFolder = '';
        $this->iUid = 0;
        $this->sUnifiedUid = '';
        $this->sSubject = '';
        $this->sMessageId = '';
        $this->sContentType = '';
        $this->iSize = 0;
        $this->iTextSize = 0;
        $this->iInternalTimeStampInUTC = 0;
        $this->iReceivedOrDateTimeStampInUTC = 0;
        $this->aFlags = array();
        $this->aFlagsLowerCase = array();

        $this->oFrom = null;
        $this->oSender = null;
        $this->oReplyTo = null;
        $this->oTo = null;
        $this->oCc = null;
        $this->oBcc = null;

        $this->sInReplyTo = '';
        $this->sReferences = '';

        $this->sHeaders = '';
        $this->sPlain = '';
        $this->sHtml = '';

        $this->iSensitivity = \MailSo\Mime\Enumerations\Sensitivity::NOTHING;
        $this->iImportance = \MailSo\Mime\Enumerations\MessagePriority::NORMAL;
        $this->bSafety = false;
        $this->sReadingConfirmation = '';

        $this->oAttachments = null;
        $this->aDraftInfo = null;
        $this->aExtend = null;
        $this->aCustom = array();

        $this->aThreads = array();

        return $this;
    }

    /**
     * Creates a new instance of the object.
     *
     * @return Message
     */
    public static function createEmptyInstance()
    {
        return new self();
    }

    /**
     * Returns block of headers of the message.
     *
     * @return string
     */
    public function getHeaders()
    {
        return $this->sHeaders;
    }

    /**
     * Allows for joining custom content to mail message, implemented for use of iCal/vCard content.
     *
     * @param string $sName Name of the custom content.
     * @param mixed $mValue Value of the custom content.
     *
     * @return Message
     */
    public function addExtend($sName, $mValue)
    {
        if (!is_array($this->aExtend)) {
            $this->aExtend = array();
        }

        $i = 0;
        while (isset($this->aExtend[$sName])) {
            $sName .= $i;
            $i++;
        }

        $this->aExtend[$sName] = $mValue;

        return $this;
    }

    /**
     * Returns value set with **addExtend** method.
     *
     * @param string $sName Extended value to be looked up.
     *
     * @return mixed
     */
    public function getExtend($sName)
    {
        return isset($this->aExtend[$sName]) ? $this->aExtend[$sName] : null;
    }

    /**
     * Allows for adding custom values to message object, implemented for use by plugins.
     *
     * @param string $sName Name of custom data.
     * @param mixed $mValue Value of custom data.
     *
     * @return Message
     */
    public function addCustom($sName, $mValue)
    {
        $this->aCustom[$sName] = $mValue;

        return $this;
    }

    /**
     * Returns a list of values set with **addCustom** method.
     *
     * @return array
     */
    public function getCustomList()
    {
        return $this->aCustom;
    }

    /**
     * Returns plaintext body of the message.
     *
     * @return string
     */
    public function getPlain()
    {
        return $this->sPlain;
    }

    /**
     * Returns HTML body of the message.
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->sHtml;
    }

    public function getAccountId()
    {
        return $this->iAccountId;
    }

    public function setAccountId($iAccountId)
    {
        $this->iAccountId = $iAccountId;
    }

    /**
     * Raw full name of the folder the message resides in.
     *
     * @return string
     */
    public function getFolder()
    {
        return $this->sFolder;
    }

    /**
     * UID value of the message.
     *
     * @return int
     */
    public function getUid()
    {
        return $this->iUid;
    }

    /**
     * Unified UID value of the message.
     *
     * @return int
     */
    public function getUnifiedUid()
    {
        return $this->sUnifiedUid;
    }

    public function setUnifiedUid($sUnifiedUid)
    {
        $this->sUnifiedUid = $sUnifiedUid;
    }

    /**
     * Value of Message-ID header.
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->sMessageId;
    }

    /**
     * Subject of the message.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->sSubject;
    }

    /**
     * Content-Type value of the message.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->sContentType;
    }

    /**
     * Message size in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->iSize;
    }

    /**
     * Total size of text parts of the message in bytes.
     *
     * @return int
     */
    public function getTextSize()
    {
        return $this->iTextSize;
    }

    /**
     * Returns timestamp information of the message in UTC/GMT.
     *
     * @return int
     */
    public function getInternalTimeStamp()
    {
        return $this->iInternalTimeStampInUTC;
    }

    /**
     * Returns UTC timestamp of the message, checking through various sources including Received and Date headers.
     *
     * @return int
     */
    public function getReceivedOrDateTimeStamp()
    {
        return $this->iReceivedOrDateTimeStampInUTC;
    }

    /**
     * Returns list of message flags.
     *
     * @return array
     */
    public function getFlags()
    {
        return $this->aFlags;
    }

    /**
     * Returns list of message flags converted to lower case.
     *
     * @return array
     */
    public function getFlagsLowerCase()
    {
        return $this->aFlagsLowerCase;
    }

    /**
     * List of From addresses in the message. Though in most cases it's just one address, it's possible to have multiple From entries in the same message.
     *
     * @return \MailSo\Mime\EmailCollection
     */
    public function getFrom()
    {
        return $this->oFrom;
    }

    /**
     * List of Sender addresses in the message. Though in most cases it's just one address, it's possible to have multiple Sender entries in the same message.
     *
     * @return \MailSo\Mime\EmailCollection
     */
    public function getSender()
    {
        return $this->oSender;
    }

    /**
     * Returns value of Reply-To header.
     *
     * @return \MailSo\Mime\EmailCollection
     */
    public function getReplyTo()
    {
        return $this->oReplyTo;
    }

    /**
     * List of To addresses of the message.
     *
     * @return \MailSo\Mime\EmailCollection
     */
    public function getTo()
    {
        return $this->oTo;
    }

    /**
     * List of CC addresses of the message.
     *
     * @return \MailSo\Mime\EmailCollection
     */
    public function getCc()
    {
        return $this->oCc;
    }

    /**
     * List of BCC addresses of the message.
     *
     * @return \MailSo\Mime\EmailCollection
     */
    public function getBcc()
    {
        return $this->oBcc;
    }

    /**
     * Returns value of *In-Reply-To* header which is supplied in replies/forwards and contains Message-ID of the original message. This approach allows for organizing threads.
     *
     * @return string
     */
    public function getInReplyTo()
    {
        return $this->sInReplyTo;
    }

    /**
     * Returns the content of References header block of the message.
     *
     * @return string
     */
    public function getReferences()
    {
        return $this->sReferences;
    }

    /**
     * If Sensitivity header was set for the message, its value will be returned: 1 for "Confidential", 2 for "Private", 3 for "Personal".
     *
     * @return int
     */
    public function getSensitivity()
    {
        return $this->iSensitivity;
    }

    /**
     * Returns importance value of the message, from 1 (highest) to 5 (lowest).
     *
     * @return int
     */
    public function getImportance()
    {
        return $this->iImportance;
    }

    /**
     * The method returns indication of whether the sender is trustworthy so it's safe to display external images.
     *
     * @return bool
     */
    public function getSafety()
    {
        return $this->bSafety;
    }

    /**
     * Returns email address reading confirmation is to be sent to.
     *
     * @return string
     */
    public function getReadingConfirmation()
    {
        return $this->sReadingConfirmation;
    }

    /**
     * The method allows for indication of whether the sender is trustworthy so it's safe to display external images.
     *
     * @param bool $bSafety If **true** displaying external images is allowed.
     *
     * @return void
     */
    public function setSafety($bSafety)
    {
        $this->bSafety = $bSafety;
    }

    /**
     * Returns information about attachments of the message.
     *
     * @return \Aurora\Modules\Mail\Classes\AttachmentCollection
     */
    public function getAttachments()
    {
        return $this->oAttachments;
    }

    /**
     * Contains information about the original message which is replied or forwarded: message type (reply/forward), UID and folder.
     *
     * @return array | null
     */
    public function getDraftInfo()
    {
        return $this->aDraftInfo;
    }

    /**
     * Threading information such as UIDs of messages in the thread.
     *
     * @return array
     */
    public function getThreads()
    {
        return $this->aThreads;
    }

    /**
     * Updates information about the message by supplying threading information such as UIDs of messages in the thread.
     *
     * @param array $aThreads
     */
    public function setThreads($aThreads)
    {
        $this->aThreads = \is_array($aThreads) ? $aThreads : array();
    }

    /**
     * Get a collection of headers.
     *
     * @return \MailSo\Mime\HeaderCollection
     */
    public function getHeadersCollection()
    {
        return \MailSo\Mime\HeaderCollection::NewInstance()->Parse($this->getHeaders());
    }

    protected function getParsedHeaders()
    {
        $headerLines = explode("\r\n", $this->getHeaders());
        $headerLineCount = count($headerLines);
        $headerLineIndex = 0;
        $parsedHeaders = [];
        $currentHeaderLabel = '';
        $currentHeaderValue = '';
        $currentRawHeaderLines = [];
        foreach ($headerLines as $headerLine) {
            $matches = [];
            if (preg_match('/^([^ \t]*?)(?::[ \t]*)(.*)$/', $headerLine, $matches)) {
                //This is a line that does not start with FWS, so it's the start of a new header
                if ($currentHeaderLabel !== '') {
                    $parsedHeaders[] = [
                        'label'      => $currentHeaderLabel,
                        'lowerlabel' => strtolower($currentHeaderLabel),
                        'value'   => $currentHeaderValue,
                    ];
                }
                $currentHeaderLabel = $matches[1];
                $currentHeaderValue = $matches[2];
                $currentRawHeaderLines = [$currentHeaderValue];
            } elseif (preg_match('/^[ \t]+(.*)$/', $headerLine, $matches)) {
                if ($headerLineIndex === 0) {
                    // throw new DKIMException('Invalid headers starting with a folded line');
                }
                //This is a folded continuation of the current header
                $currentHeaderValue .= $matches[1];
                $currentRawHeaderLines[] = $matches[1];
            }
            ++$headerLineIndex;
            if ($headerLineIndex >= $headerLineCount) {
                //This was the last line, so finish off this header
                $parsedHeaders[] = [
                    'label'      => $currentHeaderLabel,
                    'lowerlabel' => strtolower($currentHeaderLabel),
                    'value'   => $currentHeaderValue,
                ];
            }
        }

        return $parsedHeaders;
    }

    /**
     * Extract the headers from a message.
     *
     * @param $headerName
     *
     * @return array
     */
    protected function getHeadersNamed(string $headerName)
    {
        $headerName = strtolower($headerName);
        $matchedHeaders = [];
        $parsedHeaders = $this->getParsedHeaders();
        foreach ($parsedHeaders as $header) {
            //Don't exit early in case there are multiple headers with the same name
            if ($header['lowerlabel'] === $headerName) {
                $matchedHeaders[] = $header['value'];
            }
        }

        return $matchedHeaders;
    }

    /**
     * @return array
     */
    public function parseUnsubscribeHeaders()
    {
        $mResult = [
            'OneClick' => false,
            'Url' => '',
            'Email' => ''
        ];
        $aDKIMHeaders = $this->getHeadersNamed('DKIM-Signature');

        $bSignatureLU = false;
        $bSignatureLUPrev = true;

        // $bSignatureLUP = false;
        // $bSignatureLUPPrev = true;

        foreach ($aDKIMHeaders as $sDKIMHeader) {
            if ($sDKIMHeader) {
                $aHeaders = \explode(';', $sDKIMHeader);
                foreach ($aHeaders as $sHeaderValue) {
                    $aHeader = \explode('=', \trim($sHeaderValue), 2);
                    if ($aHeader[0] === 'h') {
                        $aVal = \explode(':', $aHeader[1]);
                        foreach ($aVal as $v) {
                            $v = \trim($v);
                            if (strtolower($v) === 'list-unsubscribe') {
                                $bSignatureLU = $bSignatureLUPrev && true;
                            }
                            // elseif (strtolower($v) === 'list-unsubscribe-post') {
                            // 	$bSignatureLUP = $bSignatureLUPPrev && true;
                            // }
                        }
                    }
                }

                $bSignatureLUPrev = $bSignatureLU;
                // $bSignatureLUPPrev = $bSignatureLUP;
            }
        }

        $oHeadersCol = $this->getHeadersCollection();
        $oHeaderLU = $oHeadersCol->GetByName('List-Unsubscribe');
        if ($oHeaderLU) {
            $sHeaderValueLU = $oHeaderLU->Value();
            if (!empty($sHeaderValueLU)) {
                $sEmail = $sUrl = '';
                preg_match_all('#<(.*?)>#i', $sHeaderValueLU, $matches);

                $aValues = [];
                if (isset($matches[1]) && count($matches[1]) > 0) {
                    $aValues = $matches[1];
                } else {
                    $aValues = explode(',', $sHeaderValueLU);
                }

                foreach ($aValues as $value) {
                    if (stripos($value, 'mailto:') === 0) {
                        $sEmailCheck = str_ireplace('mailto:', '', $value);
                        $aEmailData = explode('?', $sEmailCheck);
                        if (filter_var($aEmailData[0], FILTER_VALIDATE_EMAIL)) {
                            $sEmail = $aEmailData[0];
                        }
                    } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                        $sUrl = $value;
                    }
                }

                $oHeaderLUP = $oHeadersCol->GetByName('List-Unsubscribe-Post');
                if ($oHeaderLUP) {
                    $sHeaderLUP = $oHeaderLUP->Value();
                    if (!empty($sUrl) && $bSignatureLU) {
                        $mResult['Url'] = $sUrl;
                    }
                    if (strcasecmp($sHeaderLUP, 'List-Unsubscribe=One-Click') == 0 && !empty($sUrl) /*&& $bSignatureLUP*/ && $bSignatureLU) {
                        $mResult['OneClick'] = true;
                    }
                } elseif (!empty($sEmail) && $bSignatureLU) {
                    $mResult['Email'] = $sEmail;
                } else {
                    if (!empty($sUrl) && $bSignatureLU) {
                        $mResult['Url'] = $sUrl;
                    }
                }
            }
        }
        return $mResult;
    }


    /**
     * Creates and initializes instance of the object.
     *
     * @param string $sRawFolderFullName Raw full name of the folder that contains the message.
     * @param \MailSo\Imap\FetchResponse $oFetchResponse FetchResponse object.
     * @param \MailSo\Imap\BodyStructure $oBodyStructure = null. BodyStructure object.
     * @param string $sRfc822SubMimeIndex = ''. Index at which a message is taken to parse. Index is used if the message is another message attachment.
     *
     * @return Message
     */
    public static function createInstance($sRawFolderFullName, $oFetchResponse, $oBodyStructure = null, $sRfc822SubMimeIndex = '', $bTruncated = false)
    {
        return self::createEmptyInstance()->initialize($sRawFolderFullName, $oFetchResponse, $oBodyStructure, $sRfc822SubMimeIndex, $bTruncated);
    }

    /**
     * Advanced method which allows for creating messages invoking MailSo library built into the product.
     *
     * @param string $sRawFolderFullName Raw full name of the folder that contains the message.
     * @param \MailSo\Imap\FetchResponse $oFetchResponse FetchResponse object.
     * @param \MailSo\Imap\BodyStructure $oBodyStructure = null. BodyStructure object.
     * @param string $sRfc822SubMimeIndex = ''. Index at which a message is taken to parse. Index is used if the message is another message attachment.
     *
     * @return Message
     */
    protected function initialize($sRawFolderFullName, $oFetchResponse, $oBodyStructure = null, $sRfc822SubMimeIndex = '', $bTruncated = false)
    {
        if (!$oBodyStructure) {
            $oBodyStructure = $oFetchResponse->GetFetchBodyStructure($sRfc822SubMimeIndex);
        }

        $aTextParts = $oBodyStructure ? $oBodyStructure->SearchHtmlOrPlainParts() : array();

        $sUid = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::UID);
        $sSize = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE);
        $sInternalDate = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::INTERNALDATE);
        $aFlags = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::FLAGS);

        $this->sFolder = $sRawFolderFullName;
        $this->iUid = is_numeric($sUid) ? (int) $sUid : 0;
        $this->iSize = is_numeric($sSize) ? (int) $sSize : 0;
        $this->iTextSize = 0;
        $this->bTruncated = $bTruncated;
        $this->aFlags = is_array($aFlags) ? $aFlags : array();
        $this->aFlagsLowerCase = array_map('strtolower', $this->aFlags);

        $this->iInternalTimeStampInUTC = \MailSo\Base\DateTimeHelper::ParseInternalDateString($sInternalDate);

        $sCharset = $oBodyStructure ? $oBodyStructure->SearchCharset() : '';
        $sCharset = \MailSo\Base\Utils::NormalizeCharset($sCharset);

        $this->sHeaders = trim($oFetchResponse->GetHeaderFieldsValue($sRfc822SubMimeIndex));
        if (!empty($this->sHeaders)) {
            $oHeaders = \MailSo\Mime\HeaderCollection::NewInstance()->Parse($this->sHeaders, false, $sCharset);

            $sContentTypeCharset = $oHeaders->ParameterValue(
                \MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
                \MailSo\Mime\Enumerations\Parameter::CHARSET
            );

            if (!empty($sContentTypeCharset)) {
                $sCharset = $sContentTypeCharset;
                $sCharset = \MailSo\Base\Utils::NormalizeCharset($sCharset);
            }

            if (!empty($sCharset)) {
                $oHeaders->SetParentCharset($sCharset);
            }

            $bCharsetAutoDetect = 0 === \strlen($sCharset);

            $this->sSubject = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::SUBJECT, $bCharsetAutoDetect);
            $this->sMessageId = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::MESSAGE_ID);
            $this->sContentType = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::CONTENT_TYPE);

            $this->setReceivedOrDateTimeStampInUTC($oHeaders);

            $this->oFrom = $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::FROM_, $bCharsetAutoDetect);
            $this->oTo = $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::TO_, $bCharsetAutoDetect);
            $this->oCc = $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::CC, $bCharsetAutoDetect);
            $this->oBcc = $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::BCC, $bCharsetAutoDetect);
            $this->oSender = $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::SENDER, $bCharsetAutoDetect);
            $this->oReplyTo = $oHeaders->GetAsEmailCollection(\MailSo\Mime\Enumerations\Header::REPLY_TO, $bCharsetAutoDetect);

            $this->sInReplyTo = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::IN_REPLY_TO);
            $this->sReferences = \preg_replace(
                '/[\s]+/',
                ' ',
                $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::REFERENCES)
            );

            $this->setSensitivity($oHeaders);
            $this->setImportance($oHeaders);
            $this->setReadingConfirmation($oHeaders);
            $this->setDraftInfo($oHeaders);
        }

        if (is_array($aTextParts) && 0 < count($aTextParts)) {
            if (0 === \strlen($sCharset)) {
                $sCharset = \MailSo\Base\Enumerations\Charset::UTF_8;
            }

            $sHtmlParts = array();
            $sPlainParts = array();

            $iHtmlSize = 0;
            $iPlainSize = 0;
            foreach ($aTextParts as /* @var $oPart \MailSo\Imap\BodyStructure */ $oPart) {
                if ($oPart) {
                    if ('text/html' === $oPart->ContentType()) {
                        $iHtmlSize += $oPart->EstimatedSize();
                    } else {
                        $iPlainSize += $oPart->EstimatedSize();
                    }
                }

                $sText = $oFetchResponse->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::BODY . '[' . $oPart->PartID() .
                    ('' !== $sRfc822SubMimeIndex && is_numeric($sRfc822SubMimeIndex) ? '.1' : '') . ']');

                if (is_string($sText) && 0 < strlen($sText)) {
                    $sTextCharset = $oPart->Charset();
                    if (empty($sTextCharset)) {
                        $sTextCharset = $sCharset;
                    }

                    $sTextCharset = \MailSo\Base\Utils::NormalizeCharset($sTextCharset, true);

                    $sText = \MailSo\Base\Utils::DecodeEncodingValue($sText, $oPart->MailEncodingName());
                    $sText = \MailSo\Base\Utils::ConvertEncoding($sText, $sTextCharset, \MailSo\Base\Enumerations\Charset::UTF_8);
                    $sText = \MailSo\Base\Utils::Utf8Clear($sText);

                    if ('text/html' === $oPart->ContentType()) {
                        $sHtmlParts[] = $sText;
                    } else {
                        $sPlainParts[] = $sText;
                    }
                }
            }

            if (0 < count($sHtmlParts)) {
                $this->sHtml = trim(implode('<br />', $sHtmlParts));
                $this->iTextSize = strlen($this->sHtml);
            } else {
                $this->sPlain = trim(implode("\n", $sPlainParts));
                $this->iTextSize = strlen($this->sPlain);
            }

            if (0 === $this->iTextSize) {
                $this->iTextSize = 0 < $iHtmlSize ? $iHtmlSize : $iPlainSize;
            }

            unset($sHtmlParts, $sPlainParts);
        }

        if ($oBodyStructure) {
            $aAttachmentsParts = $oBodyStructure->SearchAttachmentsParts();
            if ($aAttachmentsParts && 0 < count($aAttachmentsParts)) {
                $this->oAttachments = \Aurora\Modules\Mail\Classes\AttachmentCollection::createInstance();
                foreach ($aAttachmentsParts as /* @var $oAttachmentItem \MailSo\Imap\BodyStructure */ $oAttachmentItem) {
                    $this->oAttachments->Add(
                        \Aurora\Modules\Mail\Classes\Attachment::createInstance($this->sFolder, $this->iUid, $oAttachmentItem)
                    );
                }
            }
        }

        return $this;
    }

    protected function setReceivedOrDateTimeStampInUTC($oHeaders)
    {
        $aReceived = $oHeaders->ValuesByName(\MailSo\Mime\Enumerations\Header::RECEIVED);
        $sReceived = !empty($aReceived[0]) ? trim($aReceived[0]) : '';

        $sDate = '';
        if (!empty($sReceived)) {
            $aParts = explode(';', $sReceived);
            if (0 < count($aParts)) {
                $aParts = array_reverse($aParts);
                foreach ($aParts as $sReceiveLine) {
                    $sReceiveLine = trim($sReceiveLine);
                    if (preg_match('/[\d]{4} [\d]{2}:[\d]{2}:[\d]{2} /', $sReceiveLine)) {
                        $sDate = $sReceiveLine;
                        break;
                    }
                }
            }
        }

        if (empty($sDate)) {
            $sDate = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::DATE);
        }

        if (!empty($sDate)) {
            $this->iReceivedOrDateTimeStampInUTC = \MailSo\Base\DateTimeHelper::ParseRFC2822DateString($sDate);
        }
    }

    protected function setSensitivity($oHeaders)
    {
        $this->iSensitivity = \MailSo\Mime\Enumerations\Sensitivity::NOTHING;
        $sSensitivity = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::SENSITIVITY);
        switch (strtolower($sSensitivity)) {
            case 'personal':
                $this->iSensitivity = \MailSo\Mime\Enumerations\Sensitivity::PERSONAL;
                break;
            case 'private':
                $this->iSensitivity = \MailSo\Mime\Enumerations\Sensitivity::PRIVATE_;
                break;
            case 'company-confidential':
                $this->iSensitivity = \MailSo\Mime\Enumerations\Sensitivity::CONFIDENTIAL;
                break;
        }
    }

    protected function setImportance($oHeaders)
    {
        $this->iImportance = \MailSo\Mime\Enumerations\MessagePriority::NORMAL;

        $sImportanceHeader = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::X_MSMAIL_PRIORITY);
        if (0 === strlen($sImportanceHeader)) {
            $sImportanceHeader = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::IMPORTANCE);
        }
        if (0 === strlen($sImportanceHeader)) {
            $sImportanceHeader = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::X_PRIORITY);
        }

        if (0 < strlen($sImportanceHeader)) {
            switch (str_replace(' ', '', strtolower($sImportanceHeader))) {
                case 'high':
                case '1(highest)':
                case '2(high)':
                case '1':
                case '2':
                    $this->iImportance = \MailSo\Mime\Enumerations\MessagePriority::HIGH;
                    break;
                case 'low':
                case '4(low)':
                case '5(lowest)':
                case '4':
                case '5':
                    $this->iImportance = \MailSo\Mime\Enumerations\MessagePriority::LOW;
                    break;
            }
        }
    }

    protected function setReadingConfirmation($oHeaders)
    {
        $this->sReadingConfirmation = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::DISPOSITION_NOTIFICATION_TO);
        if (0 === strlen($this->sReadingConfirmation)) {
            $this->sReadingConfirmation = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::X_CONFIRM_READING_TO);
        }

        $this->sReadingConfirmation = trim($this->sReadingConfirmation);
    }

    protected function setDraftInfo($oHeaders)
    {
        $sDraftInfo = $oHeaders->ValueByName(\MailSo\Mime\Enumerations\Header::X_DRAFT_INFO);
        if (0 < strlen($sDraftInfo)) {
            $sType = '';
            $sFolder = '';
            $sUid = '';

            \MailSo\Mime\ParameterCollection::NewInstance($sDraftInfo)
                ->ForeachList(function ($oParameter) use (&$sType, &$sFolder, &$sUid) {
                    switch (strtolower($oParameter->Name())) {
                        case 'type':
                            $sType = $oParameter->Value();
                            break;
                        case 'uid':
                            $sUid = $oParameter->Value();
                            break;
                        case 'folder':
                            $sFolder = base64_decode($oParameter->Value());
                            break;
                    }
                })
            ;

            if (0 < strlen($sType) && 0 < strlen($sFolder) && 0 < strlen($sUid)) {
                $this->aDraftInfo = array($sType, $sUid, $sFolder);
            }
        }
    }

    public function toResponseArray($aParameters = array())
    {
        if (!$this->iAccountId === null) {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UnknownError);
        }

        $oAttachments = $this->getAttachments();

        $iInternalTimeStampInUTC = $this->getInternalTimeStamp();
        $iReceivedOrDateTimeStampInUTC = $this->getReceivedOrDateTimeStamp();

        $aFlags = $this->getFlagsLowerCase();
        $mResult = array_merge(\Aurora\System\Managers\Response::objectWrapper($this, $aParameters), array(
            'Folder' => $this->getFolder(),
            'Uid' => $this->getUid(),
            'UnifiedUid' => $this->getUnifiedUid(),
            'Subject' => $this->getSubject(),
            'MessageId' => $this->getMessageId(),
            'Size' => $this->getSize(),
            'TextSize' => $this->getTextSize(),
            'Truncated' => $this->bTruncated,
            'InternalTimeStampInUTC' => $iInternalTimeStampInUTC,
            'ReceivedOrDateTimeStampInUTC' => $iReceivedOrDateTimeStampInUTC,
            'TimeStampInUTC' =>	\Aurora\Modules\Mail\Module::getInstance()->oModuleSettings->UseDateFromHeaders && 0 < $iReceivedOrDateTimeStampInUTC ?
                $iReceivedOrDateTimeStampInUTC : $iInternalTimeStampInUTC,
            'From' => \Aurora\System\Managers\Response::GetResponseObject($this->getFrom()),
            'To' => \Aurora\System\Managers\Response::GetResponseObject($this->getTo()),
            'Cc' => \Aurora\System\Managers\Response::GetResponseObject($this->getCc()),
            'Bcc' => \Aurora\System\Managers\Response::GetResponseObject($this->getBcc()),
            'Sender' => \Aurora\System\Managers\Response::GetResponseObject($this->getSender()),
            'ReplyTo' => \Aurora\System\Managers\Response::GetResponseObject($this->getReplyTo()),
            'IsSeen' => in_array('\\seen', $aFlags),
            'IsFlagged' => in_array('\\flagged', $aFlags),
            'IsAnswered' => in_array('\\answered', $aFlags),
            'IsForwarded' => false,
            'HasAttachments' => $oAttachments && $oAttachments->hasNotInlineAttachments(),
            'HasVcardAttachment' => $oAttachments && $oAttachments->hasVcardAttachment(),
            'HasIcalAttachment' => $oAttachments && $oAttachments->hasIcalAttachment(),
            'Importance' => $this->getImportance(),
            'DraftInfo' => $this->getDraftInfo(),
            'Sensitivity' => $this->getSensitivity(),
            'Unsubscribe' => $this->parseUnsubscribeHeaders()
        ));

        $sLowerForwarded = strtolower(\Aurora\Modules\Mail\Module::getInstance()->oModuleSettings->ForwardedFlagName);
        if (!empty($sLowerForwarded)) {
            $mResult['IsForwarded'] = in_array($sLowerForwarded, $aFlags);
        }

        $sHash = \Aurora\System\Api::EncodeKeyValues(array(
            'AccountID' => $this->iAccountId,
            'Folder' => $mResult['Folder'],
            'Uid' => $mResult['Uid'],
            'MimeType' => 'message/rfc822',
            'FileName' => $mResult['Subject'] . '.eml'
        ));
        $mResult['DownloadAsEmlUrl'] = '?mail-attachment/' . $sHash;

        $mResult['Hash'] = $sHash;

        if (isset($aParameters['Method']) && ('GetMessage' === $aParameters['Method'] || 'GetMessagesBodies' === $aParameters['Method'] || 'GetMessageByMessageID' === $aParameters['Method'])) {
            $mResult['Headers'] = \MailSo\Base\Utils::Utf8Clear($this->getHeaders());
            $mResult['InReplyTo'] = $this->getInReplyTo();
            $mResult['References'] = $this->getReferences();
            $mResult['ReadingConfirmationAddressee'] = $this->getReadingConfirmation();

            if (!empty($mResult['ReadingConfirmationAddressee']) && in_array('$readconfirm', $aFlags)) {
                $mResult['ReadingConfirmationAddressee'] = '';
            }

            $bHasExternals = false;
            $aFoundedCIDs = array();

            $sPlain = '';
            $sHtml = trim($this->getHtml());

            if (0 === strlen($sHtml)) {
                $sPlain = $this->getPlain();
            } elseif (5000 > strlen($sHtml)) {
                $mResult['HtmlRaw'] = $sHtml;
            }

            $aContentLocationUrls = array();
            $aFoundedContentLocationUrls = array();

            if ($oAttachments && 0 < $oAttachments->Count()) {
                $aList = &$oAttachments->GetAsArray();
                foreach ($aList as /* @var \afterlogic\common\managers\mail\classes\attachment */ $oAttachment) {
                    if ($oAttachment) {
                        $sContentLocation = $oAttachment->getContentLocation();
                        if ($sContentLocation && 0 < \strlen($sContentLocation)) {
                            $aContentLocationUrls[] = $oAttachment->getContentLocation();
                        }
                    }
                }
            }

            $bCreateHtmlLinksFromTextLinksInDOM = \Aurora\Modules\Mail\Module::getInstance()->oModuleSettings->CreateHtmlLinksFromTextLinksInDOM;
            if (0 < \strlen($sHtml) && \Aurora\Modules\Mail\Module::getInstance()->oModuleSettings->DisplayInlineCss) {
                $sHtml = \MailSo\Base\HtmlUtils::ClearHtml(
                    \Aurora\System\Utils::ConvertCssToInlineStyles($sHtml),
                    $bHasExternals,
                    $aFoundedCIDs,
                    $aContentLocationUrls,
                    $aFoundedContentLocationUrls,
                    false,
                    $bCreateHtmlLinksFromTextLinksInDOM
                );
            } elseif (strlen($sHtml) > 0) {
                $sHtml = \MailSo\Base\HtmlUtils::ClearHtml(
                    $sHtml,
                    $bHasExternals,
                    $aFoundedCIDs,
                    $aContentLocationUrls,
                    $aFoundedContentLocationUrls,
                    false,
                    $bCreateHtmlLinksFromTextLinksInDOM
                );
            }

            $mResult['Html'] = $sHtml;
            $mResult['Plain'] = 0 === strlen($sPlain) ? '' : \MailSo\Base\HtmlUtils::ConvertPlainToHtml($sPlain);
            $mResult['PlainRaw'] = \trim($sPlain);
            $mResult['Rtl'] = 0 < \strlen($mResult['Plain']) ? \MailSo\Base\Utils::IsRTL($mResult['Plain']) : false;

            $mResult['Extend'] = array();
            if (is_array($this->aExtend)) {
                foreach ($this->aExtend as $oExtend) {
                    $mResult['Extend'][] = \Aurora\System\Managers\Response::GetResponseObject($oExtend);
                }
            }

            $mResult['Safety'] = $this->getSafety();
            $mResult['HasExternals'] = $bHasExternals;
            $mResult['FoundedCIDs'] = $aFoundedCIDs;
            $mResult['FoundedContentLocationUrls'] = $aFoundedContentLocationUrls;
            $mResult['Attachments'] = \Aurora\System\Managers\Response::GetResponseObject(
                $oAttachments,
                array(
                    'AccountID' => $this->iAccountId,
                    'FoundedCIDs' => $aFoundedCIDs,
                    'FoundedContentLocationUrls' => $aFoundedContentLocationUrls
                )
            );
        } else {
            $mResult['@Object'] = 'Object/MessageListItem';
            $mResult['Threads'] = $this->getThreads();
        }

        $mResult['Custom'] = \Aurora\System\Managers\Response::GetResponseObject($this->getCustomList());
        $mResult['Subject'] = \MailSo\Base\Utils::Utf8Clear($mResult['Subject']);

        return $mResult;
    }
}
