<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Sieve;

use Aurora\Api;
use Aurora\Modules\Mail\Module;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Module $oModule
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
    /**
     * @var bool
     */
    public const AutoSave = true;

    /**
     * @var string
     */
    protected $sSieveFileName;

    /**
     * @var array
     */
    protected $aSectionsData;

    /**
     * @var array
     */
    protected $aSectionsOrders;

    /**
     * @var string
     */
    protected $aBaseRequitements;

    /**
     * @var array
     */
    protected $aSectionsRequitements;

    /**
     * @var array
     */
    protected $aSieves;

    /**
     * @var string
     */
    protected $sGeneralPassword;

    /**
     * @var bool
     */
    protected $bSieveCheckScript;

    /**
     * @var string
     */
    protected $sSieveFolderCharset;

    /**
     * @var bool
     */
    protected $bSectionsParsed;

    /**
     * @param \Aurora\Modules\Mail\Module $oModule
     */
    public function __construct(\Aurora\System\Module\AbstractModule $oModule)
    {
        parent::__construct($oModule);

        $this->aSieves = array();
        $this->sGeneralPassword = '';
        $this->sSieveFileName = $oModule->oModuleSettings->SieveFileName;
        $this->sSieveFolderCharset = $oModule->oModuleSettings->SieveFiltersFolderCharset;
        $this->bSieveCheckScript = $oModule->oModuleSettings->SieveCheckScript;
        $this->bSectionsParsed = false;
        $this->aSectionsData = array();
        $this->aSectionsOrders = array(
            'forward',
            'autoresponder',
            'filters',
            'allow_block_lists'
        );
        $this->aBaseRequitements = ["fileinto", "copy", "vacation", "regex", "include", "envelope", "imap4flags", "relational", "comparator-i;ascii-numeric"];
        $this->aSectionsRequitements = array();

    }

    /**
     * @param string $sValue
     * @return string
     */
    private function _quoteValue($sValue)
    {
        return str_replace('"', '\\"', trim($sValue));
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @return array
     */
    public function getAutoresponder($oAccount)
    {
        $this->_parseSectionsData($oAccount);
        $sData = $this->_getSectionData('autoresponder');

        $bEnabled = false;
        $sSubject = '';
        $sText = '';

        $aMatch = array();
        if (!empty($sData) && preg_match('/#data=([\d])~([^\n]+)/', $sData, $aMatch) && isset($aMatch[1]) && isset($aMatch[2])) {
            $bEnabled = '1' === (string) $aMatch[1];
            $aParts = explode("\x0", base64_decode($aMatch[2]), 2);
            if (is_array($aParts) && 2 === count($aParts)) {
                $sSubject = $aParts[0];
                $sText = $aParts[1];
            }
        }

        return array(
            'Enable' => $bEnabled,
            'Subject' => $sSubject,
            'Message' => $sText
        );
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param string $sSubject
     * @param string $sText
     * @param bool $bEnabled
     * @return bool
     */
    public function setAutoresponder($oAccount, $sSubject, $sText, $bEnabled = true)
    {
        $sSubject = str_replace(array("\r", "\n", "\t"), ' ', trim($sSubject));
        $sText = str_replace(array("\r"), '', trim($sText));

        $sData = '';
        if (!empty($sSubject) || !empty($sText)) {
            $sData = '#data=' . ($bEnabled ? '1' : '0') . '~' . base64_encode($sSubject . "\x0" . $sText) . "\n";

            $sSubject = addslashes($sSubject);
            $sText = addslashes($sText);

            $sScriptText = 'vacation :days 1 :subject "' . $this->_quoteValue($sSubject) . '" "' . $this->_quoteValue($sText) . '";';

            if ($bEnabled) {
                $sData .= $sScriptText;
            } else {
                $sData .= '#' . implode("\n#", explode("\n", $sScriptText));
            }
        }

        $this->_parseSectionsData($oAccount);
        $this->_setSectionData('autoresponder', $sData);

        if (self::AutoSave) {
            return $this->_resaveSectionsData($oAccount);
        }

        return true;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return bool
     */
    //	public function disableAutoresponder($oAccount)
    //	{
    //		$aData = $this->getAutoresponder($oAccount);
    //
    //		$sText = '';
    //		$sSubject = '';
    //
    //		if ($aData && isset($aData[1], $aData[2]))
    //		{
    //			$sText = $aData[2];
    //			$sSubject = $aData[1];
    //		}
    //
    //		return $this->setAutoresponder($oAccount, $sText, $sSubject, false);
    //	}

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return array
     */
    public function getForward($oAccount)
    {
        $this->_parseSectionsData($oAccount);
        $sData = $this->_getSectionData('forward');

        $bEnabled = false;
        $bKeepMessageCopy = true;
        $sForward = '';

        $aMatch = array();
        if (!empty($sData) && preg_match('/#data=(\d)(?:~(\d))?~([^\n]+)/', $sData, $aMatch) && isset($aMatch[1]) && isset($aMatch[3])) {
            $bEnabled = (string) $aMatch[1] === '1';
            $bKeepMessageCopy = (string) $aMatch[2] === '0' ? false : true;
            $sForward = base64_decode($aMatch[3]);
        }

        return array(
            'Enable' => $bEnabled,
            'KeepMessageCopy' => $bKeepMessageCopy,
            'Email' => $sForward
        );
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param string $sForwardToEmail
     * @param bool $bEnabled Default true
     * @param bool $bKeepMessageCopy Indicates if message should be kept after forwarding. Default true
     *
     * @return bool
     */
    public function setForward($oAccount, $sForwardToEmail, $bEnabled = true, $bKeepMessageCopy = true)
    {
        $sData = '';
        if (!empty($sForwardToEmail)) {
            $sData =
                '#data=' . ($bEnabled ? '1' : '0') . '~' . ($bKeepMessageCopy ? '1' : '0') . '~' . base64_encode($sForwardToEmail) . "\n" .
                ($bEnabled ? '' : '#') . 'redirect ' . ($bKeepMessageCopy ? ':copy ' : '') . '"' . $this->_quoteValue($sForwardToEmail) . '";';
        }
        $this->_parseSectionsData($oAccount);
        $this->_setSectionData('forward', $sData);

        if (self::AutoSave) {
            return $this->_resaveSectionsData($oAccount);
        }

        return true;
    }

    public function createFilterInstance(\Aurora\Modules\Mail\Models\MailAccount $oAccount, $aData)
    {
        $oFilter = null;

        if (is_array($aData)) {
            $oFilter = new \Aurora\Modules\Mail\Classes\SieveFilter($oAccount);

            $oFilter->Enable = (bool) trim($aData['Enable']);
            $oFilter->Field = (int) trim($aData['Field']);
            $oFilter->Condition = (int) trim($aData['Condition']);
            $oFilter->Action = (int) trim($aData['Action']);
            $oFilter->Filter = (string) trim($aData['Filter']);
            $oFilter->Email = (string) trim($aData['Email']);

            if (\Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder === $oFilter->Action && isset($aData['FolderFullName'])) {
                $oFilter->FolderFullName = \Aurora\System\Utils::ConvertEncoding(
                    $aData['FolderFullName'],
                    $this->sSieveFolderCharset,
                    'utf7-imap'
                );
            }
        }

        return $oFilter;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return array|false
     */
    public function getSieveFilters($oAccount)
    {
        $mResult = false;
        $sScript = $this->getFiltersRawData($oAccount);

        if (false !== $sScript) {
            $mResult = array();

            $aFilters = explode("\n", $sScript);

            foreach ($aFilters as $sFilter) {
                $sPattern = '#sieve_filter:';
                if (strpos($sFilter, $sPattern) !== false) {
                    $sFilter = substr($sFilter, strlen($sPattern));

                    $aFilter = explode(";", $sFilter);

                    //					if (is_array($aFilter) && 5 < count($aFilter))
                    if (is_array($aFilter)) {
                        $aFilterData = array(
                            'Enable' => $aFilter[0],
                            'Field' => $aFilter[2],
                            'Condition' => $aFilter[1],
                            'Action' => $aFilter[4],
                            'Filter' => $aFilter[3],
                            'FolderFullName' => $aFilter[5],
                            'Email' => isset($aFilter[6]) ? $aFilter[6] : ''
                        );

                        $oFilter = $this->createFilterInstance($oAccount, $aFilterData);

                        if ($oFilter) {
                            $mResult[] = $oFilter;
                        }
                    }

                    unset($oFilter);
                }
            }
        }

        return $mResult;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param array $aFilters
     *
     * @return bool
     */
    public function updateSieveFilters($oAccount, $aFilters)
    {
        $sFilters = "";

        if ($oAccount) {
            foreach ($aFilters as /* @var $oFilter SieveFilter */ $oFilter) {
                if ('' === trim($oFilter->Filter)) {
                    continue;
                }

                if (\Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder === $oFilter->Action && '' === trim($oFilter->FolderFullName)) {
                    continue;
                }

                // $sFilters .= "#start sieve filter\n";

                $aFields = array();
                switch($oFilter->Field) {
                    default:
                    case \Aurora\Modules\Mail\Enums\FilterFields::From:
                        $aFields[] = 'From';
                        break;
                    case \Aurora\Modules\Mail\Enums\FilterFields::To:
                        $aFields[] = 'To';
                        $aFields[] = 'CC';
                        break;
                    case \Aurora\Modules\Mail\Enums\FilterFields::Subject:
                        $aFields[] = 'Subject';
                        break;
                }

                // condition
                foreach ($aFields as $iIndex => $sField) {
                    $aFields[$iIndex] = '"' . $this->_quoteValue($sField) . '"';
                }

                $sCondition = '';
                $sFields = implode(',', $aFields);
                switch ($oFilter->Condition) {
                    case \Aurora\Modules\Mail\Enums\FilterCondition::ContainSubstring:
                        $sCondition = 'if header :contains [' . $sFields . '] "' . $this->_quoteValue($oFilter->Filter) . '" {';
                        break;
                    case \Aurora\Modules\Mail\Enums\FilterCondition::ContainExactPhrase:
                        $sCondition = 'if header :is [' . $sFields . '] "' . $this->_quoteValue($oFilter->Filter) . '" {';
                        break;
                    case \Aurora\Modules\Mail\Enums\FilterCondition::NotContainSubstring:
                        $sCondition = 'if not header :contains [' . $sFields . '] "' . $this->_quoteValue($oFilter->Filter) . '" {';
                        break;
                }

                // folder
                $sFolderFullName = '';
                if (\Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder === $oFilter->Action) {
                    $sFolderFullName = \Aurora\System\Utils::ConvertEncoding(
                        $oFilter->FolderFullName,
                        'utf7-imap',
                        $this->sSieveFolderCharset
                    );
                }

                // redirect
                $sEmail = '';
                if (\Aurora\Modules\Mail\Enums\FilterAction::Redirect === $oFilter->Action) {
                    $sEmail = $oFilter->Email;
                }

                // Adding action
                // !IMPORTANT Action must be a one line string! Otherwise it will not be commended out in case filter is disabled
                $sAction = '';
                switch($oFilter->Action) {
                    case \Aurora\Modules\Mail\Enums\FilterAction::DeleteFromServerImmediately:
                        $sAction = '    discard;';
                        $sAction .= ' stop;';
                        break;
                    case \Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder:
                        $sAction = '    fileinto "' . $this->_quoteValue($sFolderFullName) . '";';
                        $sAction .= ' stop;';
                        break;
                    case \Aurora\Modules\Mail\Enums\FilterAction::Redirect:
                        $sAction = '    redirect :copy "' . $this->_quoteValue($sEmail) . '";';
                        $sAction .= ' stop;';
                        break;
                }

                $sEnd = '}';

                if (!$oFilter->Enable) {
                    $sCondition = '#' . $sCondition;
                    $sAction = '#' . $sAction;
                    $sEnd = '#' . $sEnd;
                }

                $sFilters .= '#sieve_filter:' . implode(';', array(
                    $oFilter->Enable ? '1' : '0', $oFilter->Condition, $oFilter->Field,
                    $oFilter->Filter, $oFilter->Action, $sFolderFullName, $sEmail)) . "\n";

                $sFilters .= $sCondition . "\n";
                $sFilters .= $sAction . "\n";
                $sFilters .= $sEnd . "\n";

                // $sFilters .= '#end sieve filter' . "\n";
            }

            return $this->setFiltersRawData($oAccount, $sFilters);
        }

        return false;
    }

    /**
     * @deprecated since version 9.7.5. Not used anywhere.
     *
     * @param  \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return bool
     */
    public function disableForward($oAccount)
    {
        $sForwardToEmail = '';
        $bKeepMessageCopy = true;
        $aData = $this->getForward($oAccount);

        if ($aData) {
            $sForwardToEmail = $aData['Email'];
            $bKeepMessageCopy = $aData['KeepMessageCopy'];
        }

        return $this->setForward($oAccount, $sForwardToEmail, false, $bKeepMessageCopy);
    }

    public function getAllowBlockLists($oAccount)
    {
        $mResult = [
            'AllowList' => [],
            'BlockList' => [],
            'SpamScore' => 0
        ];
        $this->_parseSectionsData($oAccount);
        $sData = $this->_getSectionData('allow_block_lists');

        $aMatch = array();
        if (!empty($sData) && preg_match('/#data=([^\n]+)~([^\n]+)~([^\n]+)/', $sData, $aMatch) && isset($aMatch[1]) && isset($aMatch[2]) && isset($aMatch[3])) {
            $mResult['AllowList'] = \json_decode(\base64_decode($aMatch[1]));
            $mResult['BlockList'] = \json_decode(\base64_decode($aMatch[2]));
            $mResult['SpamScore'] = (int) $aMatch[3];
        }
        return $mResult;
    }

    public function setAllowBlockLists($oAccount, $aAllowList, $aBlockList, $iSpamScore = null)
    {
        if (!is_array($aAllowList)) {
            $aAllowList = [];
        }
        if (!is_array($aBlockList)) {
            $aBlockList = [];
        }
        $sAllowList = \base64_encode(\json_encode($aAllowList));
        $sBlockList = \base64_encode(\json_encode($aBlockList));

        $aAllowListStr = [];
        foreach ($aAllowList as $sItem) {
            if (!empty($sItem)) {
                if (strpos($sItem, '@') !== false) {
                    $aAllowListStr[] = '	address :is "from" "' . $sItem . '"';
                } else {
                    $aAllowListStr[] = '	address :domain "from" "' . $sItem . '"';
                }
            }
        }

        $aBlockListStr = [];
        foreach ($aBlockList as $sItem) {
            if (!empty($sItem)) {
                if (strpos($sItem, '@') !== false) {
                    $aBlockListStr[] = '	address :is "from" "' . $sItem . '"';
                } else {
                    $aBlockListStr[] = '	address :domain "from" "' . $sItem . '"';
                }
            }
        }

        $sAllowListScript = "";
        if (count($aAllowListStr) > 0) {
            $sAllowListScript = "if anyof ( \n" . \implode(",\n", $aAllowListStr) . "\n" .
        ")
{
	keep;
	stop;
}\n";
        }

        $sBlockListScript = '';
        if (count($aBlockListStr) > 0) {
            $sBlockListScript = "if anyof ( \n" . \implode(",\n", $aBlockListStr) . "\n" .
        ")
{
	fileinto \"Spam\" ;
	stop;
}\n";
        }

        if (!isset($iSpamScore)) {
            $iSpamScore = $this->oModule->oModuleSettings->DefaultSpamScore;
        }
        $mSpamValue = $iSpamScore;
        if ($this->oModule->oModuleSettings->ConvertSpamScoreToSpamLevel) {
            $mSpamValue = str_pad('', $iSpamScore, "*");
        }
        $SieveSpamRuleCondition = $this->oModule->oModuleSettings->SieveSpamRuleCondition;
        $SieveSpamRuleCondition = str_replace('{{Value}}', $mSpamValue, $SieveSpamRuleCondition);

        $sData = '#data=' . $sAllowList . '~' . $sBlockList . '~' . $iSpamScore . "\n" . $sAllowListScript . $sBlockListScript . "\n";

        if (!empty($SieveSpamRuleCondition)) {
            $sData .= "
#copy email with X-Spam-Score greater than certain value to Spam folder
if " . $SieveSpamRuleCondition . " {
    fileinto \"Spam\";
    stop;
}";
        } else {
            Api::Log('"SieveSpamRuleCondition" settings has not yet been set.');
        }

        $this->_parseSectionsData($oAccount);
        $this->_setSectionData('allow_block_lists', $sData);
        if (self::AutoSave) {
            return $this->_resaveSectionsData($oAccount);
        }
        return true;
    }

    public function addRowToAllowList($oAccount, $sRow)
    {
        $aLists = $this->getAllowBlockLists($oAccount);

        $aAllowList = $aLists['AllowList'];
        $aAllowList[] = $sRow;

        $aBlockList = array_unique($aLists['BlockList']);
        $iRowIndex = \array_search($sRow, $aBlockList, true);
        if (is_int($iRowIndex)) {
            \array_splice($aBlockList, $iRowIndex, 1);
        }
        $iSpamScore = $aLists['SpamScore'];
        return $this->setAllowBlockLists($oAccount, array_unique($aAllowList), $aBlockList, $iSpamScore);
    }

    public function addRowToBlockList($oAccount, $sRow)
    {
        $aLists = $this->getAllowBlockLists($oAccount);

        $aAllowList = array_unique($aLists['AllowList']);
        $iRowIndex = \array_search($sRow, $aAllowList, true);
        if (is_int($iRowIndex)) {
            \array_splice($aAllowList, $iRowIndex, 1);
        }

        $aBlockList = $aLists['BlockList'];
        $aBlockList[] = $sRow;

        $iSpamScore = $aLists['SpamScore'];
        return $this->setAllowBlockLists($oAccount, $aAllowList, array_unique($aBlockList), $iSpamScore);
    }

    /**
     * @deprecated since version 9.7.5
     *
     * @param  \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param string $sSectionName Default ''
     * @param string $sSectionData Default ''
     *
     * @return bool
     */
    public function resave($oAccount, $sSectionName = '', $sSectionData = '')
    {
        $this->_parseSectionsData($oAccount);
        if (!empty($sSectionName) && !empty($sSectionData)) {
            $this->_setSectionData($sSectionName, $sSectionData);
        }

        return $this->_resaveSectionsData($oAccount);
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @return string
     */
    public function getFiltersRawData($oAccount)
    {
        $this->_parseSectionsData($oAccount);
        return $this->_getSectionData('filters');
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param string $sFiltersRawData
     * @return bool
     */
    public function setFiltersRawData($oAccount, $sFiltersRawData)
    {
        $this->_parseSectionsData($oAccount);
        $this->_setSectionData('filters', $sFiltersRawData);

        if (self::AutoSave) {
            return $this->_resaveSectionsData($oAccount);
        }
        return true;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @return \MailSo\Sieve\ManageSieveClient|false
     */
    protected function _getSieveDriver(\Aurora\Modules\Mail\Models\MailAccount $oAccount)
    {
        $oSieve = false;
        if ($oAccount instanceof \Aurora\Modules\Mail\Models\MailAccount) {
            if (!isset($this->aSieves[$oAccount->Email])) {
                $oSieve = \MailSo\Sieve\ManageSieveClient::NewInstance();
                $oSieve->SetLogger(\Aurora\System\Api::SystemLogger());

                $this->aSieves[$oAccount->Email] = $oSieve;
            } else {
                $oSieve = $this->aSieves[$oAccount->Email];
            }
        }

        return $oSieve;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return \MailSo\Sieve\ManageSieveClient|false
     */
    protected function _connectSieve($oAccount)
    {
        $bResult = false;
        $oSieve = $this->_getSieveDriver($oAccount);

        if ($oSieve) {
            if (!$oSieve->IsConnected()) {
                // $oMailModule = \Aurora\System\Api::GetModule('Mail');
                // if ($oMailModule instanceof Module) {
                $sGeneralPassword = $this->oModule->oModuleSettings->SieveGeneralPassword;

                $oServer = $this->oModule->getServersManager()->getServer($oAccount->ServerId);

                $sHost = $this->oModule->oModuleSettings->OverriddenSieveHost;
                if (empty($sHost)) {
                    $sHost = $oServer->IncomingServer;
                }

                $iPort = $oServer->SievePort;
                $sPassword = 0 === strlen($sGeneralPassword) ? $oAccount->getPassword() : $sGeneralPassword;
                $bUseStarttls = $this->oModule->oModuleSettings->SieveUseStarttls;
                $bResult = $oSieve
                    ->Connect($sHost, $iPort, $bUseStarttls ? \MailSo\Net\Enumerations\ConnectionSecurityType::STARTTLS : \MailSo\Net\Enumerations\ConnectionSecurityType::NONE)
                    ->Login($oAccount->IncomingLogin, $sPassword)
                ;
                // }
            } else {
                $bResult = true;
            }

            if ($oSieve) {
                return $oSieve;
            }
        }

        return $bResult;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return string|false
     */
    protected function _getSieveFile($oAccount)
    {
        $sResult = false;

        try {
            $oSieve = $this->_connectSieve($oAccount);
            if ($oSieve) {
                if ($oSieve->IsActiveScript($this->sSieveFileName)) {
                    $sResult = $oSieve->GetScript($this->sSieveFileName);
                }
            }
        } catch (\Exception $oException) {
            $sResult = false;
        }

        return is_string($sResult) ? str_replace("\r", '', $sResult) : false;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param string $sText
     *
     * @return bool
     */
    protected function _setSieveFile($oAccount, $sText)
    {
        $sText = str_replace("\r", '', $sText);
        $bResult = false;

        try {
            $oSieve = $this->_connectSieve($oAccount);

            if ($oSieve) {
                if ($this->bSieveCheckScript) {
                    $oSieve->CheckScript($sText);
                }

                $oSieve->PutScript($this->sSieveFileName, $sText);
                $oSieve->SetActiveScript($this->sSieveFileName);

                $bResult = true;
            } else {
                throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountUpdateFailed);
            }
        } catch (\Exception $oException) {
            throw $oException;
        }

        return $bResult;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return bool
     */
    protected function _resaveSectionsData($oAccount)
    {
        $this->bSectionsParsed = false;
        return $this->_setSieveFile($oAccount, $this->_selectionsDataToString());
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param bool $bForced Default false
     */
    protected function _parseSectionsData($oAccount, $bForced = false)
    {
        if (!$this->bSectionsParsed || $bForced) {
            $sText = $this->_getSieveFile($oAccount);
            if (false !== $sText) {
                if (is_array($this->aSectionsOrders)) {
                    foreach ($this->aSectionsOrders as $sSectionName) {
                        $aParams = $this->_getSectionParams($sSectionName, $sText);
                        if ($aParams) {
                            $this->aSectionsData[$sSectionName] = trim(substr(
                                $sText,
                                $aParams[0] + strlen($aParams[2]),
                                $aParams[1] - $aParams[0] - strlen($aParams[2])
                            ));
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function _selectionsDataToString()
    {
        $sResult = "# Sieve filter\n";
        // $sResult .= 'require ["fileinto", "copy", "vacation", "regex", "include", "envelope", "imap4flags", "relational", "comparator-i;ascii-numeric"] ;' . "\n";
        $sResult .= $this->_getRequirements();

        if (is_array($this->aSectionsOrders)) {
            foreach ($this->aSectionsOrders as $sSectionName) {
                if (!empty($this->aSectionsData[$sSectionName])) {
                    $sResult .= "\n" .
                        $this->_getComment($sSectionName, true) . "\n" .
                        $this->aSectionsData[$sSectionName] . "\n" .
                        $this->_getComment($sSectionName, false) . "\n";
                };
            }
        }

        // Removed 'keep' because it should be controlled by a specific sieve rule.
        // Currently, overall 'keep' doesn't work in combination with 'forward', which may not keep forwarded messages.
        //$sResult .= "keep;\n";
        return $sResult;
    }

    /**
     * @return string
     */
    protected function _getRequirements()
    {
        $aFullRequirements = $this->aBaseRequitements ? $this->aBaseRequitements : [];
        foreach ($this->aSectionsRequitements as $aRequirements) {
            foreach ($aRequirements as $sRequirement) {
                $aFullRequirements[] = $sRequirement;
            }
        }

        return "require [\"" . implode('","', $aFullRequirements) . "\"];\n";
    }

    /**
     * @param string $sSectionName
     * @param string $sRequirement
     */
    protected function _addRequirement($sSectionName, $sRequirement)
    {
        if (in_array($sSectionName, $this->aSectionsOrders)) {
            if (!isset($this->aSectionsRequitements[$sSectionName]) || !is_array($this->aSectionsRequitements[$sSectionName])) {
                $this->aSectionsRequitements[$sSectionName] = array();
            }

            $this->aSectionsRequitements[$sSectionName][] = $sRequirement;
        }
    }


    /**
     * @param string $sSectionName
     *
     * @return string
     */
    protected function _getSectionData($sSectionName)
    {
        if (in_array($sSectionName, $this->aSectionsOrders) && !empty($this->aSectionsData[$sSectionName])) {
            return $this->aSectionsData[$sSectionName];
        }

        return '';
    }

    /**
     * @param string $sSectionName
     * @param string $sData
     */
    protected function _setSectionData($sSectionName, $sData)
    {
        if (in_array($sSectionName, $this->aSectionsOrders)) {
            $this->aSectionsData[$sSectionName] = $sData;
        }
    }

    /**
     *
     * @param string $sSectionName
     * @param bool $bIsBeginComment Default true
     *
     * @return string
     */
    protected function _getComment($sSectionName, $bIsBeginComment = true)
    {
        return '#' . ($bIsBeginComment ? 'begin' : 'end') . ' = ' . $sSectionName . ' =';
    }

    /**
     *
     * @param string $sSectionName
     * @param string $sText
     *
     * @return array|false
     */
    protected function _getSectionParams($sSectionName, $sText)
    {
        $aResult = false;

        if (!empty($sText)) {
            $sBeginComment = $this->_getComment($sSectionName, true);
            $sEndComment = $this->_getComment($sSectionName, false);

            $iBegin = strpos($sText, $sBeginComment);
            if (false !== $iBegin) {
                $iEnd = strpos($sText, $sEndComment, $iBegin);
                if (false !== $iEnd) {
                    $aResult = array($iBegin, $iEnd, $sBeginComment, $sEndComment);
                }
            }
        }

        return $aResult;
    }
}
