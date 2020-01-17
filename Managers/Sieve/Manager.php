<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Sieve;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @var bool
	 */
	const AutoSave = true;

	/**
	 * @var CApiSieveProtocol
	 */
	protected $oSieve;

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
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule)
	{
		parent::__construct($oModule);

		$this->aSieves = array();
		$this->sGeneralPassword = '';
		$this->sSieveFileName = $oModule->getConfig('SieveFileName', 'sieve');
		$this->sSieveFolderCharset = $oModule->getConfig('SieveFiltersFolderCharset', 'utf-8');
		$this->bSieveCheckScript = $oModule->getConfig('SieveCheckScript', false);
		$this->bSectionsParsed = false;
		$this->aSectionsData = array();
		$this->aSectionsOrders = array(
			'forward',
			'autoresponder',
			'filters'
		);
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
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
		if (!empty($sData) && preg_match('/#data=([\d])~([^\n]+)/', $sData, $aMatch) && isset($aMatch[1]) && isset($aMatch[2]))
		{
			$bEnabled = '1' === (string) $aMatch[1];
			$aParts = explode("\x0", base64_decode($aMatch[2]), 2);
			if (is_array($aParts) && 2 === count($aParts))
			{
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param string $sSubject
	 * @param string $sText
	 * @param bool $bEnabled
	 * @return bool
	 */
	public function setAutoresponder($oAccount, $sSubject, $sText, $bEnabled = true)
	{
		$sSubject = str_replace(array("\r", "\n", "\t"), ' ', trim($sSubject));
		$sText = str_replace(array("\r"), '', trim($sText));

		$sData = '#data='.($bEnabled ? '1' : '0').'~'.base64_encode($sSubject."\x0".$sText)."\n";
		$sScriptText = 'vacation :days 1 :subject "'.$this->_quoteValue($sSubject).'" "'.$this->_quoteValue($sText).'";';

		if ($bEnabled)
		{
			$sData .= $sScriptText;
		}
		else
		{
			$sData .= '#'.implode("\n#", explode("\n", $sScriptText));
		}

		$this->_parseSectionsData($oAccount);
		$this->_setSectionData('autoresponder', $sData);

		if (self::AutoSave)
		{
			return $this->_resaveSectionsData($oAccount);
		}

		return true;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * 
	 * @return array
	 */
	public function getForward($oAccount)
	{
		$this->_parseSectionsData($oAccount);
		$sData = $this->_getSectionData('forward');

		$bEnabled = false;
		$sForward = '';

		$aMatch = array();
		if (!empty($sData) && preg_match('/#data=([\d])~([^\n]+)/', $sData, $aMatch) && isset($aMatch[1]) && isset($aMatch[2]))
		{
			$bEnabled = '1' === (string) $aMatch[1];
			$sForward = base64_decode($aMatch[2]);
		}
		
		return array(
			'Enable' => $bEnabled,
			'Email' => $sForward
		);
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param string $sForward
	 * @param bool $bEnabled Default true
	 * 
	 * @return bool
	 */
	public function setForward($oAccount, $sForward, $bEnabled = true)
	{
		$sData =
			'#data='.($bEnabled ? '1' : '0').'~'.base64_encode($sForward)."\n".
			($bEnabled ? '' : '#').'redirect :copy "'.$this->_quoteValue($sForward).'";'."\n";

		$this->_parseSectionsData($oAccount);
		$this->_setSectionData('forward', $sData);

		if (self::AutoSave)
		{
			return $this->_resaveSectionsData($oAccount);
		}

		return true;
	}
	
	public function createFilterInstance(\Aurora\Modules\Mail\Classes\Account $oAccount, $aData)
	{
		$oFilter = null;
		
		if (is_array($aData))
		{
			$oFilter = new \Aurora\Modules\Mail\Classes\SieveFilter($oAccount);
		
			$oFilter->Enable = (bool) trim($aData['Enable']);
			$oFilter->Field = (int) trim($aData['Field']);
			$oFilter->Condition = (int) trim($aData['Condition']);
			$oFilter->Action = (int) trim($aData['Action']);
			$oFilter->Filter = (string) trim($aData['Filter']);

			if (\Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder === $oFilter->Action && isset($aData['FolderFullName']))
			{
				$oFilter->FolderFullName = \Aurora\System\Utils::ConvertEncoding($aData['FolderFullName'],
					$this->sSieveFolderCharset, 'utf7-imap');
			}
		}
		
		return $oFilter;
	}
	
	/**
	 * @param CAcount $oAccount
	 *
	 * @return array|false
	 */
	public function getSieveFilters($oAccount)
	{
		$mResult = false;
		$sScript = $this->getFiltersRawData($oAccount);
		
		if (false !== $sScript)
		{
			$mResult = array();
			
			$aFilters = explode("\n", $sScript);

			foreach ($aFilters as $sFilter)
			{
				$sPattern = '#sieve_filter:';
				if (strpos($sFilter, $sPattern) !== false)
				{
					$sFilter = substr($sFilter, strlen($sPattern));

					$aFilter = explode(";", $sFilter);

//					if (is_array($aFilter) && 5 < count($aFilter))
					if (is_array($aFilter))
					{
						$aFilterData = array(
							'Enable' => $aFilter[0],
							'Field' => $aFilter[2],
							'Condition' => $aFilter[1],
							'Action' => $aFilter[4],
							'Filter' => $aFilter[3],
							'FolderFullName' => $aFilter[5]
						);
						
						$oFilter = $this->createFilterInstance($oAccount, $aFilterData);
						
						if ($oFilter)
						{
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
	 * @param CAcount $oAccount
	 * @param array $aFilters
	 *
	 * @return bool
	 */
	public function updateSieveFilters($oAccount, $aFilters)
	{
		$sFilters = "#sieve filter\n\n";

		if ($oAccount)
		{
			foreach ($aFilters as /* @var $oFilter SieveFilter */ $oFilter)
			{
				if  ('' === trim($oFilter->Filter))
				{
					continue;
				}

				if  (\Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder === $oFilter->Action && '' === trim($oFilter->FolderFullName))
				{
					continue;
				}

				$aFields = array();
				switch($oFilter->Field)
				{
					default :
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
				foreach ($aFields as $iIndex => $sField)
				{
					$aFields[$iIndex] = '"'.$this->_quoteValue($sField).'"';
				}

				$sCondition = '';
				$sFields = implode(',', $aFields);
				switch ($oFilter->Condition)
				{
					case \Aurora\Modules\Mail\Enums\FilterCondition::ContainSubstring:
						$sCondition = 'if header :contains ['.$sFields.'] "'.$this->_quoteValue($oFilter->Filter).'" {';
						break;
					case \Aurora\Modules\Mail\Enums\FilterCondition::ContainExactPhrase:
						$sCondition = 'if header :is ['.$sFields.'] "'.$this->_quoteValue($oFilter->Filter).'" {';
						break;
					case \Aurora\Modules\Mail\Enums\FilterCondition::NotContainSubstring:
						$sCondition = 'if not header :contains ['.$sFields.'] "'.$this->_quoteValue($oFilter->Filter).'" {';
						break;
				}

				// folder
				$sFolderFullName = '';
				if (\Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder === $oFilter->Action)
				{
					$sFolderFullName = \Aurora\System\Utils::ConvertEncoding($oFilter->FolderFullName,
						'utf7-imap', $this->sSieveFolderCharset);
				}

				// action
				$sAction = '';
				switch($oFilter->Action)
				{
					case \Aurora\Modules\Mail\Enums\FilterAction::DeleteFromServerImmediately:
						$sAction = 'discard ;';
						$sAction .= 'stop ;';
						break;
					case \Aurora\Modules\Mail\Enums\FilterAction::MoveToFolder:
						$sAction = 'fileinto "'.$this->_quoteValue($sFolderFullName).'" ;'."\n";
						$sAction .= 'stop ;';
						break;
				}

				$sEnd = '}';

				if (!$oFilter->Enable)
				{
					$sCondition = '#'.$sCondition;
					$sAction = '#'.$sAction;
					$sEnd = '#'.$sEnd;
				}

				$sFilters .= "\n".'#sieve_filter:'.implode(';', array(
					$oFilter->Enable ? '1' : '0', $oFilter->Condition, $oFilter->Field,
					$oFilter->Filter, $oFilter->Action, $sFolderFullName))."\n";

				$sFilters .= $sCondition."\n";
				$sFilters .= $sAction."\n";
				$sFilters .= $sEnd."\n";
			}

			$sFilters = $sFilters."\n".'#end sieve filter'."\n";
			
			return $this->setFiltersRawData($oAccount, $sFilters);
		}

		return false;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param string $sForward
	 * @param bool $bEnabled = true
	 * 
	 * @return bool
	 */
	public function disableForward($oAccount)
	{
		$sForward = '';
		$aData = $this->getForward($oAccount);

		if ($aData && isset($aData[1]))
		{
			$sForward = $aData[1];
		}

		return $this->setForward($oAccount, $sForward, false);
	}

	/**
	 * @depricated
	 * 
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param string $sSectionName Default ''
	 * @param string $sSectionData Default ''
	 * 
	 * @return bool
	 */
	public function resave($oAccount, $sSectionName = '', $sSectionData = '')
	{
		$this->_parseSectionsData($oAccount);
		if (!empty($sSectionName) && !empty($sSectionData))
		{
			$this->_setSectionData($sSectionName, $sSectionData);
		}

		return $this->_resaveSectionsData($oAccount);
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @return string
	 */
	public function getFiltersRawData($oAccount)
	{
		$this->_parseSectionsData($oAccount);
		return $this->_getSectionData('filters');
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param string $sFiltersRawData
	 * @return bool
	 */
	public function setFiltersRawData($oAccount, $sFiltersRawData)
	{
		$this->_parseSectionsData($oAccount);
		$this->_setSectionData('filters', $sFiltersRawData);

		if (self::AutoSave)
		{
			return $this->_resaveSectionsData($oAccount);
		}
		return true;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @return \MailSo\Sieve\ManageSieveClient|false
	 */
	protected function _getSieveDriver(\Aurora\Modules\Mail\Classes\Account $oAccount)
	{
		$oSieve = false;
		if ($oAccount instanceof \Aurora\Modules\Mail\Classes\Account)
		{
			if (!isset($this->aSieves[$oAccount->Email]))
			{
				$oSieve = \MailSo\Sieve\ManageSieveClient::NewInstance();
				$oSieve->SetLogger(\Aurora\System\Api::SystemLogger());

				$this->aSieves[$oAccount->Email] = $oSieve;
			}
			else
			{
				$oSieve = $this->aSieves[$oAccount->Email];
			}
		}

		return $oSieve;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * 
	 * @return \MailSo\Sieve\ManageSieveClient|false
	 */
	protected function _connectSieve($oAccount)
	{
		$bResult = false;
		$oSieve = $this->_getSieveDriver($oAccount);

		if ($oSieve)
		{
			if (!$oSieve->IsConnected())
			{
				$oMailModule = \Aurora\System\Api::GetModule('Mail');
				$sGeneralPassword = $oMailModule->getConfig('SieveGeneralPassword', '');
				
				$oServer = $oMailModule->getServersManager()->getServer($oAccount->ServerId);
				
				$sHost = $oMailModule->getConfig('OverriddenSieveHost', '');
				if (empty($sHost))
				{
					$sHost = $oServer->IncomingServer;
				}

				$iPort = $oServer->SievePort;
				$sPassword = 0 === strlen($sGeneralPassword) ? $oAccount->getPassword() : $sGeneralPassword;
				$bUseStarttls = $this->GetModule()->getConfig('SieveUseStarttls', false);
				$bResult = $oSieve
					->Connect($sHost, $iPort, $bUseStarttls ? \MailSo\Net\Enumerations\ConnectionSecurityType::STARTTLS : \MailSo\Net\Enumerations\ConnectionSecurityType::NONE)
					->Login($oAccount->IncomingLogin, $sPassword)
				;
			}
			else
			{
				$bResult = true;
			}

			if ($oSieve)
			{
				return $oSieve;
			}
		}

		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * 
	 * @return string|false
	 */
	protected function _getSieveFile($oAccount)
	{
		$sResult = false;
		
		try
		{
			$oSieve = $this->_connectSieve($oAccount);
			if ($oSieve)
			{
				if ($oSieve->IsActiveScript($this->sSieveFileName))
				{
					$sResult = $oSieve->GetScript($this->sSieveFileName);
				}
			}
		}
		catch (\Exception $oException)
		{
			$sResult = false;
		}

		return is_string($sResult) ? str_replace("\r", '', $sResult) : false;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param string $sText
	 * 
	 * @return bool
	 */
	protected function _setSieveFile($oAccount, $sText)
	{
		$sText = str_replace("\r", '', $sText);
		$sText = rtrim(str_replace("\n", "\r\n", $sText));
		$bResult = false;
		
		try
		{
			$oSieve = $this->_connectSieve($oAccount);
			
			if ($oSieve)
			{
				if ($this->bSieveCheckScript)
				{
					$oSieve->CheckScript($sText);
				}
				
				$oSieve->PutScript($this->sSieveFileName, $sText);
				$oSieve->SetActiveScript($this->sSieveFileName);

				$bResult = true;
			}
			else
			{
				throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountUpdateFailed);
			}
		}
		catch (\Exception $oException)
		{
			throw $oException;
		}

		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * 
	 * @return bool
	 */
	protected function _resaveSectionsData($oAccount)
	{
		$this->bSectionsParsed = false;
		return $this->_setSieveFile($oAccount, $this->_selectionsDataToString());
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * 
	 * @param bool $bForced Default false
	 */
	protected function _parseSectionsData($oAccount, $bForced = false)
	{
		if (!$this->bSectionsParsed || $bForced)
		{
			$sText = $this->_getSieveFile($oAccount);
			if (false !== $sText)
			{
				if (is_array($this->aSectionsOrders))
				{
					foreach ($this->aSectionsOrders as $sSectionName)
					{
						$aParams = $this->_getSectionParams($sSectionName, $sText);
						if ($aParams)
						{
							$this->aSectionsData[$sSectionName] = trim(substr($sText,
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
		$sResult = '';
		if (is_array($this->aSectionsOrders))
		{
			foreach ($this->aSectionsOrders as $sSectionName)
			{
				if (!empty($this->aSectionsData[$sSectionName]))
				{
					$sResult .= "\n".
						$this->_getComment($sSectionName, true)."\n".
						$this->aSectionsData[$sSectionName]."\n".
						$this->_getComment($sSectionName, false)."\n";
				};
			}
		}

		$sResult = 'require ["fileinto", "copy", "vacation"] ;'."\n".$sResult;
		$sResult = "# Sieve filter\n".$sResult;
		$sResult .= "keep ;\n";
		return $sResult;
	}

	/**
	 * @param string $sSectionName
	 * 
	 * @return string
	 */
	protected function _getSectionData($sSectionName)
	{
		if (in_array($sSectionName, $this->aSectionsOrders) && !empty($this->aSectionsData[$sSectionName]))
		{
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
		if (in_array($sSectionName, $this->aSectionsOrders))
		{
			$this->aSectionsData[$sSectionName] = $sData;
		}
	}

	/**
	 * 
	 * @param type $sSectionName
	 * @param type $bIsBeginComment Default true
	 * 
	 * @return string
	 */
	protected function _getComment($sSectionName, $bIsBeginComment = true)
	{
		return '#'.($bIsBeginComment ? 'begin' : 'end').' = '.$sSectionName.' =';
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

		if (!empty($sText))
		{
			$sBeginComment = $this->_getComment($sSectionName, true);
			$sEndComment = $this->_getComment($sSectionName, false);

			$iBegin = strpos($sText, $sBeginComment);
			if (false !== $iBegin)
			{
				$iEnd = strpos($sText, $sEndComment, $iBegin);
				if (false !== $iEnd)
				{
					$aResult = array($iBegin, $iEnd, $sBeginComment, $sEndComment);
				}
			}
		}

		return $aResult;
	}

}
