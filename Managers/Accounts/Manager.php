<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Accounts;

use Aurora\Modules\Mail\Models\MailAccount;
use Aurora\System\Notifications;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as Capsule;

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
     * @param \Aurora\System\Module\AbstractModule $oModule
     */
    public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
    {
        parent::__construct($oModule);
    }

    /**
     *
     * @param int $iAccountId
     * @return boolean|\Aurora\Modules\Mail\Models\MailAccount
     * @throws \Aurora\System\Exceptions\BaseException
     */
    public function getAccountById($iAccountId)
    {
        $mAccount = false;
        try {
            if (is_numeric($iAccountId)) {
                $mAccount = MailAccount::with('Server')->find((int) $iAccountId);
            } else {
                throw new \Aurora\System\Exceptions\ApiException(Notifications::InvalidInputParameter);
            }
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $mAccount = false;
            $this->setLastException($oException);
        }
        return $mAccount;
    }

    /**
     * Obtains an account with the specified email and for the specified user.
     * @param string $sEmail Email of the account.
     * @param int $iUserId Identifier of the user which owns the account.
     * @return boolean|\Aurora\Modules\Mail\Models\MailAccount
     * @throws \Aurora\System\Exceptions\BaseException
     */
    public function getAccountByEmail($sEmail, $iUserId)
    {
        $aFilters = [
            'Email' => $sEmail,
            'IsDisabled' => false,
            'IdUser' => $iUserId,
        ];

        $mAccount = MailAccount::with('Server')->where($aFilters)->first();

        return $mAccount;
    }

    /**
     * Obtains an account with specified email. The account must be allowed to authenticate its user.
     * @param string $sEmail Email of the account.
     * @return \Aurora\Modules\Mail\Models\MailAccount|boolean
     */
    public function getAccountUsedToAuthorize($sEmail)
    {
        $aFilters = [
            'Email' => $sEmail,
            'IsDisabled' => false,
            'UseToAuthorize' => true
        ];
        $mAccount = MailAccount::where($aFilters)->first();

        return $mAccount;
    }

    /**
     * @param string $sEmail
     * @param int $iExceptId
     * @return bool
     */
    public function useToAuthorizeAccountExists($sEmail, $iExceptId = 0)
    {
        $bExists = false;

        try {
            $query = MailAccount::where([
                'Email' => $sEmail,
                'UseToAuthorize' => true
            ]);

            if ($iExceptId > 0) {
                $query->where('Id', '!=', $iExceptId);
            }

            $bExists = $query->exists();
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }

        return $bExists;
    }

    /**
     * @param $iUserId
     * @return Collection|false
     */
    public function getUserAccounts($iUserId)
    {
        $mResult = false;
        try {
            $mResult = MailAccount::with('Server')->where([
                'IdUser' => $iUserId,
                'IsDisabled' => false
            ])->get();
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }

        return $mResult;
    }

    /**
     * Obtains mail accounts with specified parameters.
     * @param Array $aFilters
     * @return Array|false
     */
    public function getAccounts($aFilters)
    {
        return MailAccount::with('Server')->where($aFilters)->get();
    }

    /**
     * @param int $iUserId
     *
     * @return int
     */
    public function getUserAccountsCount($iUserId)
    {
        return MailAccount::where(['IdUser' => $iUserId])->count();
    }

    /**
     * @param int $iUserId
     *
     * @return bool
     */
    public function canCreate($iUserId)
    {
        $bResult = false;

        $iAccounts = $this->getUserAccountsCount($iUserId);

        if ($iAccounts < 1) {
            $bResult = true;
        }

        return $bResult;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return bool
     */
    public function isExists(MailAccount $oAccount)
    {
        return MailAccount::where([
            'Email' => $oAccount->Email,
            'IdUser' => $oAccount->IdUser
        ])->exists();
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return bool
     */
    public function createAccount(MailAccount &$oAccount)
    {
        $bResult = false;

        if ($oAccount->validate() && $this->canCreate($oAccount->IdUser)) {

            Capsule::connection()->transaction(
                function () use ($oAccount) {
                    if (!MailAccount::where([
                            'Email' => $oAccount->Email,
                            'IdUser' => $oAccount->IdUser
                        ])->sharedLock()->exists()) {
                        if (!$oAccount->save()) {
                            throw new \Aurora\System\Exceptions\ManagerException(Notifications::CanNotCreateAccount);
                        }
                    } else {
                        throw new \Aurora\System\Exceptions\ApiException(Notifications::AccountExists);
                    }
                }
            );

            $bResult = true;
        }

        return $bResult;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return bool
     */
    public function updateAccount(MailAccount &$oAccount)
    {
        $bResult = false;
        try {
            if ($oAccount->validate()) {
                if (!$oAccount->save()) {
                    throw new \Aurora\System\Exceptions\ManagerException(Notifications::CanNotUpdateAccount);
                }
            }

            $bResult = true;
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $bResult = false;
            $this->setLastException($oException);
        }

        return $bResult;
    }

    /**
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     *
     * @return bool
     */
    public function deleteAccount(MailAccount $oAccount)
    {
        return $oAccount->delete();
    }
}
