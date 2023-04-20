<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Managers\Servers;

use Aurora\Modules\Mail\Models\Server;
use Aurora\System\Enums\SortOrder;
use Illuminate\Database\Eloquent\Builder;

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
     * @param \Aurora\Modules\Mail\Models\Server $oServer
     * @return int|boolean
     * @throws \Aurora\System\Exceptions\ManagerException
     */
    public function createServer(\Aurora\Modules\Mail\Models\Server $oServer)
    {
        try {
            if (!$oServer->save()) {
                throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::UsersManager_UserCreateFailed);
            }
            return $oServer->Id;
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }

        return false;
    }

    /**
     *
     * @param int $iServerId
     * @param int $iTenantId
     * @return boolean
     */
    public function deleteServer($iServerId, $iTenantId = 0)
    {
        $bResult = false;

        try {
            $oServer = $this->getServer($iServerId);
            if ($oServer && ($oServer->OwnerType !== \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant || $oServer->TenantId === $iTenantId)) {
                $bResult = $oServer->delete();
            }
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }

        return $bResult;
    }

    /**
     *
     * @param int $iServerId
     * @return Server
     */
    public function getServer($iServerId)
    {
        $oServer = false;

        try {
            $oServer = Server::whereId($iServerId)->first();
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $oServer = false;
            $this->setLastException($oException);
        }

        return $oServer;
    }

    public function trimDomains($sDomains)
    {
        $aDomains = array_filter(array_map("trim", explode("\n", $sDomains)));
        return join("\n", $aDomains);
    }

    /**
     *
     * @param int $sDomain
     * @return Server|boolean
     */
    public function getServerByDomain($sDomain)
    {
        $oServer = false;

        try {
            $oTenant = \Aurora\System\Api::getCurrentTenant();

            $query = Server::query();
            if ($oTenant) {
                $aFilters = $query->where(function ($q) use ($oTenant) {
                    $q->where('OwnerType', \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin)
                        ->orWhere(function ($q) use ($oTenant) {
                            $q->where([
                                ['TenantId', '=', $oTenant->Id],
                                ['OwnerType', '=', \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant],
                            ]);
                        });
                })->where('Domains', 'LIKE', '%' . $sDomain . '%');
            } else {
                $aFilters = $query->orWhere([
                    ['OwnerType', '=', \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin],
                    ['Domains', 'LIKE', '%' . $sDomain . '%'],
                ])->orWhere([
                    ['OwnerType', '=', \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant],
                    ['Domains', 'LIKE', '%' . $sDomain . '%'],
                ]);
            }

            $aResult = $aFilters->get();
            if ($aResult->count() > 0) {
                foreach ($aResult as $oTempServer) {
                    /** @var Server $oTempServer */
                    $sTrimmedDomains = $this->trimDomains($oTempServer->Domains);
                    $aDomains = explode("\n", $sTrimmedDomains);
                    if (in_array($sDomain, $aDomains)) {
                        $oServer = $oTempServer;
                        break;
                    }
                }
            }
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $oServer = false;
            $this->setLastException($oException);
        }

        return $oServer;
    }

    /**
     * @param int $iTenantId
     * @param string $sSearch
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function _getServersFilters($iTenantId = 0, $sSearch = '')
    {
        $query = Server::query();

        $aFilters = [];
        if ($iTenantId === 0) {
            $aFilters = $query->where('OwnerType', '<>', \Aurora\Modules\Mail\Enums\ServerOwnerType::Account);
        } else {
            $aFilters = $query->where(function ($q) use ($iTenantId) {
                $q->where(function ($q) use ($iTenantId) {
                    $q->where([['OwnerType', '=', \Aurora\Modules\Mail\Enums\ServerOwnerType::SuperAdmin]])
                        ->orWhere(function ($q) use ($iTenantId) {
                            $q->where([
                                ['TenantId', '=', $iTenantId],
                                ['OwnerType', '=', \Aurora\Modules\Mail\Enums\ServerOwnerType::Tenant],
                            ]);
                        });
                });
            });
        }

        if ($sSearch !== '') {
            $aFilters = $query->where('Name', 'LIKE', '%' . $sSearch . '%');
        }

        return $aFilters;
    }

    /**
     * @param int $iTenantId
     * @param string $sSearch
     * @return int
     */
    public function getServersCount($iTenantId = 0, $sSearch = '')
    {
        return $this->_getServersFilters($iTenantId, $sSearch)->count();
    }

    /**
     * @param int $iTenantId
     * @param int $iOffset
     * @param int $iLimit
     * @param string $sSearch
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getServerList($iTenantId = 0, $iOffset = 0, $iLimit = 0, $sSearch = '')
    {
        $sOrderBy = 'Name';
        $iOrderType = \Aurora\System\Enums\SortOrder::ASC;

        $query = $this->_getServersFilters($iTenantId, $sSearch);

        if ($iOffset > 0) {
            $query = $query->offset($iOffset);
        }

        if ($iLimit > 0) {
            $query = $query->limit($iLimit);
        }

        return $query->orderBy($sOrderBy, $iOrderType === SortOrder::ASC ? 'asc' : 'desc')->get();
    }

    /**
     *
     * @param \Aurora\Modules\Mail\Models\Server $oServer
     * @return boolean
     * @throws \Aurora\System\Exceptions\ManagerException
     */
    public function updateServer(\Aurora\Modules\Mail\Models\Server $oServer)
    {
        $bResult = false;

        try {
            if (!$oServer->save()) {
                throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::UsersManager_UserCreateFailed);
            }
            $bResult = true;
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $bResult = false;
            $this->setLastException($oException);
        }

        return $bResult;
    }

    /**
     * @param Builder|null $aFilters
     * @return Server|false|\Illuminate\Database\Eloquent\Model
     */
    public function getServerByFilter(Builder $aFilters = null)
    {
        $oServer = false;

        $aFilters = ($aFilters instanceof Builder) ? $aFilters : Server::query();
        $oData = $aFilters->first();

        if ($oData instanceof Server) {
            $oServer = $oData;
        }

        return $oServer;
    }

    /**
     * @param Builder|null $aFilters
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getServerListByFilter(Builder $aFilters = null)
    {
        $aFilters = ($aFilters instanceof Builder) ? $aFilters : Server::query();

        return $aFilters->get();
    }
}
