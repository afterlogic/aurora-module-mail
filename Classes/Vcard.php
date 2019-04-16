<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Classes;

/**
 * Vcard class is used for work with attachment that contains contact card.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 * 
 * @internal
 * 
 * @package Mail
 * @subpackage Classes
 */
class Vcard
{
	/**
	 * Contact identifier.
	 * 
	 * @var string
	 */
	public $Uid;

	/**
	 * Temp file name of the .vcf file.
	 * 
	 * @var string
	 */
	public $File;

	/**
	 * If **true** this contact already exists in address book.
	 * 
	 * @var bool
	 */
	public $Exists;

	/**
	 * Contact name.
	 * 
	 * @var string
	 */
	public $Name;

	/**
	 * Contact email.
	 * 
	 * @var string
	 */
	public $Email;

	private function __construct()
	{
		$this->Uid = '';
		$this->File = '';
		$this->Exists = false;
		$this->Name = '';
		$this->Email = '';
	}

	/**
	 * Creates new empty instance.
	 * 
	 * @return Vcard
	 */
	public static function createInstance()
	{
		return new self();
	}
	
	public function toResponseArray($aParameters = array())
	{
		return array(
			'Uid' => $this->Uid,
			'File' => $this->File,
			'Name' => $this->Name,
			'Email' => $this->Email,
			'Exists' => $this->Exists
		);		
	}
}
