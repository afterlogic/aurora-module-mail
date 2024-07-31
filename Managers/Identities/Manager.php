<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Identities;

use Aurora\Modules\Mail\Models\Identity;
use Illuminate\Database\Eloquent\Builder;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property \Aurora\Modules\Mail\Module $oModule
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
    /**
     * @param \Aurora\System\Module\AbstractModule $oModule
     */
    public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
    {
        parent::__construct($oModule);
    }

    /**
     * @param int $iUserId
     * @param int $iAccountID
     * @param string $sFriendlyName
     * @param string $sEmail
     * @return boolean
     */
    public function createIdentity($iUserId, $iAccountID, $sFriendlyName, $sEmail)
    {
        try {
            $oIdentity = new Identity();
            $oIdentity->IdUser = $iUserId;
            $oIdentity->IdAccount = $iAccountID;
            $oIdentity->FriendlyName = $sFriendlyName;
            $oIdentity->Email = $sEmail;

            $oIdentity->save();

            return $oIdentity->Id;
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }

        return false;
    }

    /**
     * @param int $iId
     * @param int $iIdAccount
     * @return Identity
     */
    public function getIdentity($iId, $iIdAccount)
    {
        return Identity::where('IdAccount', $iIdAccount)->find($iId);
    }

    /**
     * @param int $iId
     * @param int $iIdAccount
     * @param string $sFriendlyName
     * @param string $sEmail
     * @param boolean $bDefault
     * @return boolean
     */
    public function updateIdentity($iId, $iIdAccount, $sFriendlyName, $sEmail, $bDefault)
    {
        $bResult = false;
        try {
            $oIdentity = Identity::where('IdAccount', $iIdAccount)->findOrFail($iId);
            if ($oIdentity instanceof Identity) {
                $oIdentity->FriendlyName = $sFriendlyName;
                $oIdentity->Email = $sEmail;
                $oIdentity->Default = $bDefault;
                $bResult = $oIdentity->save();
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $oException) {
            \Aurora\Api::LogException($oException);
        }

        return $bResult;
    }

    /**
     * @param int $iId
     * @param int $iIdAccount
     * @param boolean $bUseSignature
     * @param string $sSignature
     * @return boolean
     */
    public function updateIdentitySignature($iId, $iIdAccount, $bUseSignature, $sSignature)
    {
        $bResult = false;
        try {
            $oIdentity = Identity::where('IdAccount', $iIdAccount)->findOrFail($iId);
            if ($oIdentity instanceof Identity) {
                $oIdentity->UseSignature = $bUseSignature;
                $oIdentity->Signature = $sSignature;
                $bResult = $oIdentity->save();
            }
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }

        return $bResult;
    }

    /**
     * @param int $iId
     * @param int $iIdAccount
     * @return boolean
     */
    public function deleteIdentity($iId, $iIdAccount)
    {
        $bResult = false;

        try {
            $bResult = !!Identity::where('IdAccount', $iIdAccount)->find($iId)->delete();
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }

        return $bResult;
    }

    /**
     * @param int $iUserId
     * @param Builder $oFilters null
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getIdentities($iUserId, Builder $oFilters = null)
    {
        $aResult = false;

        $oQuery = isset($oFilters) ? $oFilters : Identity::query();
        $oQuery->where('IdUser', $iUserId);

        try {
            $aResult = $oQuery->orderBy('FriendlyName')->get();
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $aResult = false;
            $this->setLastException($oException);
        }

        return $aResult;
    }

    /**
     * Deletes identities of the account.
     * @param int $iAccountId Account identifier.
     * @return boolean
     */
    public function deleteAccountIdentities($iAccountId)
    {
        return Identity::query()->where('IdAccount', $iAccountId)->delete();
    }

    /**
     * @param int $iUserId
     * @param int $iAccountId
     */
    public function resetDefaultIdentity($iUserId, $iAccountId)
    {
        Identity::where('IdUser', $iUserId)->where('IdAccount', $iAccountId)->where('Default', true)->update(['Default' => false]);
    }
}
