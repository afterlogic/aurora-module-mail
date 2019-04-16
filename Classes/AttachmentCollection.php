<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * Collection for work with attachments.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 * 
 * @package Mail
 * @subpackage Classes
 */
class AttachmentCollection extends \MailSo\Base\Collection
{
	/**
	 * @return void
	 */
	protected function __construct()
	{
		parent::__construct();
	}

	/**
	 * Creates new instance of the object.
	 * 
	 * @return \Aurora\Modules\Mail\Classes\AttachmentCollection
	 */
	public static function createInstance()
	{
		return new self();
	}

	/**
	 * Returns count of inline attachments in collection.
	 * 
	 * @param bool $bCheckContentID = false. If **true** excludes attachments without content id.
	 * 
	 * @return int
	 */
	public function getInlineCount($bCheckContentID = false)
	{
		$aList = $this->FilterList(function ($oAttachment) use ($bCheckContentID) {
			return $oAttachment && $oAttachment->isInline() &&
				($bCheckContentID ? ($oAttachment->getCid() ? true : false) : true);
		});

		return is_array($aList) ? count($aList) : 0;
	}

	/**
	 * Indicates if collection includes not inline attachments.
	 * 
	 * @return bool
	 */
	public function hasNotInlineAttachments()
	{
		return 0 < $this->Count() && $this->Count() > $this->getInlineCount(true);
	}

	/**
	 * Indicates if collection includes at least one vcard attachment.
	 * 
	 * @return bool
	 */
	public function hasVcardAttachment()
	{
		$aList = $this->FilterList(function ($oAttachment) {
			return $oAttachment && $oAttachment->isVcard();
		});

		return is_array($aList) && 0 < count($aList);
	}

	/**
	 * Indicates if collection includes at least one ical attachment.
	 * 
	 * @return bool
	 */
	public function hasIcalAttachment()
	{
		$aList = $this->FilterList(function ($oAttachment) {
			return $oAttachment && $oAttachment->isIcal();
		});

		return is_array($aList) && 0 < count($aList);
	}
}