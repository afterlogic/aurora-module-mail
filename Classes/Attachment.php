<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * \Aurora\Modules\Mail\Classes\Attachment class is used for work with attachment.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 * 
 * @package Mail
 * @subpackage Classes
 */
class Attachment
{
	/**
	 * Full name of the folder in which there is the message that contains the attachment.
	 * 
	 * @var string
	 */
	protected $sFolder;

	/**
	 * Uid of the message that contains the attachment.
	 * 
	 * @var int
	 */
	protected $iUid;

	/**
	 * Content of the attachment. It is used only for files with .asc extension.
	 * 
	 * @var string
	 */
	protected $sContent;

	/**
	 * Body structure of the message part that contains the attachment.
	 * 
	 * @var \MailSo\Imap\BodyStructure
	 */
	protected $oBodyStructure;

	/**
	 * @return void
	 */
	protected function __construct()
	{
		$this->clear();
	}

	/**
	 * Clears all fields of the object.
	 * 
	 * @return \Aurora\Modules\Mail\Classes\Attachment
	 */
	public function clear()
	{
		$this->sFolder = '';
		$this->iUid = 0;
		$this->oBodyStructure = null;
		$this->sContent = '';

		return $this;
	}

	/**
	 * Returns full name of the folder in which there is the message that contains the attachment.
	 * 
	 * @return string
	 */
	public function getFolder()
	{
		return $this->sFolder;
	}

	/**
	 * Returns uid of the message that contains the attachment.
	 * 
	 * @return int
	 */
	public function getUid()
	{
		return $this->iUid;
	}

	/**
	 * Returns content of the attachment. It is used only for files with .asc extension.
	 * 
	 * @return string
	 */
	public function getContent()
	{
		return $this->sContent;
	}

	/**
	 * Fills content of the attachment. It is used only for files with .asc extension.
	 * 
	 * @param string $sContent
	 */
	public function setContent($sContent)
	{
		$this->sContent = $sContent;
	}

	/**
	 * Returns part identifier of the attachment in the message.
	 * 
	 * @return string
	 */
	public function getMimeIndex()
	{
		return $this->oBodyStructure ? $this->oBodyStructure->PartID() : '';
	}

	/**
	 * Returns file name of the attachment.
	 * 
	 * @return string
	 */
	public function getFileName($bCalculateOnEmpty = false)
	{
		$sFileName = '';
		if ($this->oBodyStructure)
		{
			$sFileName = $this->oBodyStructure->FileName();
			if ($bCalculateOnEmpty && 0 === strlen(trim($sFileName)))
			{
				$sMimeType = strtolower(trim($this->getMimeType()));
				if ('message/rfc822' === $sMimeType)
				{
					$sFileName = 'message'.$this->getMimeIndex().'.eml';
				}
				else if ('text/calendar' === $sMimeType)
				{
					$sFileName = 'calendar'.$this->getMimeIndex().'.ics';
				}
				else if ('text/vcard' === $sMimeType || 'text/x-vcard' === $sMimeType)
				{
					$sFileName = 'contact'.$this->getMimeIndex().'.vcf';
				}
				else if (!empty($sMimeType))
				{
					$sFileName = str_replace('/', $this->getMimeIndex().'.', $sMimeType);
				}
			}
		}

		return $sFileName;
	}

	/**
	 * Returns mime type of the attachment.
	 * 
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->oBodyStructure ? $this->oBodyStructure->ContentType() : '';
	}

	/**
	 * Returns encoding that encodes content of the attachment.
	 * 
	 * @return string
	 */
	public function getEncoding()
	{
		return $this->oBodyStructure ? $this->oBodyStructure->MailEncodingName() : '';
	}

	/**
	 * Returns estimated size of decoded attachment content.
	 * 
	 * @return int
	 */
	public function getEstimatedSize()
	{
		return $this->oBodyStructure ? $this->oBodyStructure->EstimatedSize() : 0;
	}

	/**
	 * Returns content identifier of the attachment.
	 * 
	 * @return string
	 */
	public function getCid()
	{
		return $this->oBodyStructure ? $this->oBodyStructure->ContentID() : '';
	}

	/**
	 * Returns content location of the attachment.
	 * 
	 * @return string
	 */
	public function getContentLocation()
	{
		return $this->oBodyStructure ? $this->oBodyStructure->ContentLocation() : '';
	}

	/**
	 * Returns **true** if the attachment is marked as inline attachment in it's  headers.
	 * 
	 * @return bool
	 */
	public function isInline()
	{
		return $this->oBodyStructure ? $this->oBodyStructure->IsInline() : false;
	}

	/**
	 * Returns **true** if the attachment is contact card.
	 * 
	 * @return bool
	 */
	public function isVcard()
	{
		return in_array($this->getMimeType(), array('text/vcard', 'text/x-vcard'));
	}

	/**
	 * Returns **true** if the attachment is calendar event or calendar appointment.
	 * 
	 * @return bool
	 */
	public function isIcal()
	{
		return in_array($this->getMimeType(), array('text/calendar', 'text/x-calendar'));
	}

	/**
	 * Creates new empty instance.
	 * 
	 * @return \Aurora\Modules\Mail\Classes\Attachment
	 */
	public static function createEmptyInstance()
	{
		return new self();
	}

	/**
	 * Creates and initializes new instance.
	 * 
	 * @param string $sFolder Full name of the folder in which there is the message that contains the attachment.
	 * @param int $iUid Uid of the message that contains the attachment.
	 * @param \MailSo\Imap\BodyStructure $oBodyStructure Body structure of the message part that contains the attachment.
	 *
	 * @return \Aurora\Modules\Mail\Classes\Attachment
	 */
	public static function createInstance($sFolder, $iUid, $oBodyStructure)
	{
		return self::createEmptyInstance()->initialize($sFolder, $iUid, $oBodyStructure);
	}

	/**
	 * Initializes object fields.
	 * 
	 * @param string $sFolder Full name of the folder in which there is the message that contains the attachment.
	 * @param int $iUid Uid of the message that contains the attachment.
	 * @param \MailSo\Imap\BodyStructure $oBodyStructure Body structure of the message part that contains the attachment.
	 *
	 * @return \Aurora\Modules\Mail\Classes\Attachment
	 */
	public function initialize($sFolder, $iUid, $oBodyStructure)
	{
		$this->sFolder = $sFolder;
		$this->iUid = $iUid;
		$this->oBodyStructure = $oBodyStructure;
		return $this;
	}
	
	public function toResponseArray($aParameters = array())
	{
		$iAccountID = isset($aParameters['AccountID']) ? $aParameters['AccountID'] : null;
		$mFoundedCIDs = isset($aParameters['FoundedCIDs']) && is_array($aParameters['FoundedCIDs'])
			? $aParameters['FoundedCIDs'] : null;

		$mFoundedContentLocationUrls = isset($aParameters['FoundedContentLocationUrls']) &&
			\is_array($aParameters['FoundedContentLocationUrls']) &&
			0 < \count($aParameters['FoundedContentLocationUrls']) ?
				$aParameters['FoundedContentLocationUrls'] : null;

		if ($mFoundedCIDs || $mFoundedContentLocationUrls)
		{
			$aFoundedCIDs = \array_merge($mFoundedCIDs ? $mFoundedCIDs : array(),
				$mFoundedContentLocationUrls ? $mFoundedContentLocationUrls : array());

			$aFoundedCIDs = 0 < \count($mFoundedCIDs) ? $mFoundedCIDs : null;
		}

		$sMimeType = strtolower(trim($this->getMimeType()));
		$sMimeIndex = strtolower(trim($this->getMimeIndex()));
		$sContentTransferEncoding = strtolower(trim($this->getEncoding()));

		$sFileName = $this->getFileName(true);
		$iEstimatedSize = $this->getEstimatedSize();
		
		$oSettings =& \Aurora\System\Api::GetSettings();
		$iThumbnailLimit = ((int) $oSettings->GetValue('ThumbnailMaxFileSizeMb', 5)) * 1024 * 1024;

		if (in_array($sMimeType, array('application/octet-stream')))
		{
			$sMimeType = \MailSo\Base\Utils::MimeContentType($sFileName);
		}

		$sCid = \trim(\trim($this->getCid()), '<>');

		$mResult = array_merge(\Aurora\System\Managers\Response::objectWrapper($this, $aParameters), array(
			'FileName' => $sFileName,
			'MimeType' => $sMimeType,
			'MimePartIndex' => ('message/rfc822' === $sMimeType && ('base64' === $sContentTransferEncoding || 'quoted-printable' === $sContentTransferEncoding))
				? '' :  $sMimeIndex,
			'EstimatedSize' => $iEstimatedSize,
			'CID' => $sCid,
			'ContentLocation' => $this->getContentLocation(),
			'Content' => $this->getContent(),
			'IsInline' => $this->isInline(),
			'IsLinked' => (!empty($sCid) && $mFoundedCIDs && \in_array($sCid, $mFoundedCIDs)) ||
				($mFoundedContentLocationUrls && \in_array(\trim($this->getContentLocation()), $mFoundedContentLocationUrls))
		));
		
		$sHash = \Aurora\System\Api::EncodeKeyValues(array(
			'AccountID' => $iAccountID, 
			'Folder' => $this->getFolder(),
			'Uid' => $this->getUid(),
			'MimeIndex' => $sMimeIndex,
			'MimeType' =>  $sMimeType,
			'FileName' => $this->getFileName(true)
		));		 
		$mResult['Hash'] = $sHash;

		$mResult['Actions']['view'] = [
			'url' => '?mail-attachment/' . $sHash . '/view'
		];
		$mResult['Actions']['download'] = [
			'url' => '?mail-attachment/' . $sHash
		];
		
		$oSettings =& \Aurora\System\Api::GetSettings();
		if ($oSettings->GetValue('AllowThumbnail', true) &&
				$iEstimatedSize < $iThumbnailLimit && \Aurora\System\Utils::IsGDImageMimeTypeSuppoted($sMimeType, $sFileName))
		{
			$mResult['ThumbnailUrl'] = '?mail-attachment/' . $sHash . '/thumb';
		}
		
		
		return $mResult;
	}
			
}
