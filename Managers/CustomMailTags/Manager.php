<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\CustomMailTags;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2021, Afterlogic Corp.
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @var \Aurora\System\Managers\Eav
	 */
	public $oEavManager = null;
	
	/**
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule);
		
		$this->oEavManager = \Aurora\System\Managers\Eav::getInstance();
	}

	/**
	 * 
	 * @param \Aurora\Modules\Core\Classes\User $oUser
	 * @return array
	 */
	public function getTags($oUser)
	{
		$sCustomMailTagsFieldName = \Aurora\Modules\Mail\Module::GetName() . '::CustomMailTags';
		return	isset($oUser->{$sCustomMailTagsFieldName})
				? json_decode($oUser->{$sCustomMailTagsFieldName})
				: [];
	}
	
	/**
	 * 
	 * @param string $sLabel
	 * @param string $sColor
	 * @return bool
	 */
	public function addTag($sLabel, $sColor)
	{
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser instanceof \Aurora\Modules\Core\Classes\User)
		{
			$sCustomMailTagsFieldName = \Aurora\Modules\Mail\Module::GetName() . '::CustomMailTags';
			$aCustomMailTags =	isset($oUser->{$sCustomMailTagsFieldName})
								? json_decode($oUser->{$sCustomMailTagsFieldName})
								: [];
			$aCustomMailTags[] = [
				'label' => $sLabel,
				'color' => $sColor,
			];
			$oUser->{$sCustomMailTagsFieldName} = json_encode($aCustomMailTags);
			return $oUser->saveAttribute($sCustomMailTagsFieldName);
		}
		return false;
	}
	
	public function updateTag($sLabel, $sNewLabel, $sNewColor)
	{
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser instanceof \Aurora\Modules\Core\Classes\User)
		{
			$sCustomMailTagsFieldName = \Aurora\Modules\Mail\Module::GetName() . '::CustomMailTags';
			$aCustomMailTags =	isset($oUser->{$sCustomMailTagsFieldName})
								? json_decode($oUser->{$sCustomMailTagsFieldName})
								: [];
			$iUpdateTagKey = array_search($sLabel, array_column($aCustomMailTags, 'label'));
			if (is_int($iUpdateTagKey)) {
				$aCustomMailTags[$iUpdateTagKey] = [
					'label' => $sNewLabel,
					'color' => $sNewColor,
				];
				$oUser->{$sCustomMailTagsFieldName} = json_encode($aCustomMailTags);
				return $oUser->saveAttribute($sCustomMailTagsFieldName);
			}
		}
		return false;
	}

	public function deleteTag($sLabel)
	{
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser instanceof \Aurora\Modules\Core\Classes\User)
		{
			$sCustomMailTagsFieldName = \Aurora\Modules\Mail\Module::GetName() . '::CustomMailTags';
			$aCustomMailTags =	isset($oUser->{$sCustomMailTagsFieldName})
								? json_decode($oUser->{$sCustomMailTagsFieldName})
								: [];
			$iRemoveTagKey = array_search($sLabel, array_column($aCustomMailTags, 'label'));
			if (is_int($iRemoveTagKey)) {
				\array_splice($aCustomMailTags, $iRemoveTagKey, 1);
				$oUser->{$sCustomMailTagsFieldName} = json_encode($aCustomMailTags);
				return $oUser->saveAttribute($sCustomMailTagsFieldName);
			}
		}
		return false;
	}
}
