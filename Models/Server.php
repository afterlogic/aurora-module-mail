<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\Modules\Core\Models\Tenant;
use Aurora\Modules\Mail\Models\MailAccount;
use \Aurora\System\Classes\Model;

class Server extends Model
{
    protected $table = 'mail_servers';

    protected $foreignModel = Tenant::class;
    protected $foreignModelIdColumn = 'TenantId'; // Column that refers to an external table

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Id',
        'TenantId',
        'Name',
        'IncomingServer',
        'IncomingPort',
        'IncomingUseSsl',
        'OutgoingServer',
        'OutgoingPort',
        'OutgoingUseSsl',
        'SmtpAuthType',
        'SmtpLogin',
        'SmtpPassword',
        'OwnerType',
        'Domains',
        'EnableSieve',
        'SievePort',
        'EnableThreading',
        'UseFullEmailAddressAsLogin',

        'SetExternalAccessServers',
        'ExternalAccessImapServer',
        'ExternalAccessImapPort',
        'ExternalAccessImapAlterPort',
        'ExternalAccessPop3Server',
        'ExternalAccessPop3Port',
        'ExternalAccessPop3AlterPort',
        'ExternalAccessSmtpServer',
        'ExternalAccessSmtpPort',
        'ExternalAccessSmtpAlterPort',

        'OAuthEnable',
        'OAuthName',
        'OAuthType',
        'OAuthIconUrl',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    protected $casts = [
        'IncomingUseSsl' => 'boolean',
        'OutgoingUseSsl' => 'boolean',
        'EnableSieve' => 'boolean',
        'EnableThreading' => 'boolean',
        'UseFullEmailAddressAsLogin' => 'boolean',
        'SetExternalAccessServers' => 'boolean',
        'OAuthEnable' => 'boolean',
        'SmtpPassword' => \Aurora\System\Casts\Encrypt::class,
    ];

    protected $attributes = [
    ];

    protected $appends = [
        'EntityId',
        'ServerId',
    ];

    public function getServerIdAttribute()
    {
        return $this->Id;
    }

    public function MailAccounts()
    {
        return $this->hasMany(MailAccount::class, 'ServerId', 'Id');
    }

    public function toResponseArray()
    {
        $aResponse = parent::toResponseArray();
        $aResponse['ServerId'] = $this->Id;

        $aArgs = [];
        \Aurora\System\Api::GetModule('Mail')->broadcastEvent(
            'ServerToResponseArray',
            $aArgs,
            $aResponse
        );
        return $aResponse;
    }

    public function getOrphanIds()
    {
        if (!$this->foreignModel || !$this->foreignModelIdColumn) {
            return ['status' => -1, 'message' => 'Foreign fields doesnt exist'];
        }
        $tableName = $this->getTable();
        $foreignObject = new $this->foreignModel;
        $foreignTable = $foreignObject->getTable();
        $foreignPK = $foreignObject->primaryKey;

        // DB::enableQueryLog();
        $oAccount = new MailAccount;
        $accountTable = $oAccount->getTable();
        $serversWithoutAccount = self::leftJoin($accountTable, "$accountTable.ServerId", '=', "$tableName.$this->primaryKey")->where('OwnerType', '=', 'account')->whereNull("$accountTable.Id")->groupBy("$tableName.$this->primaryKey")->pluck("$tableName.$this->primaryKey")->all();
        $orphanIds = self::where('OwnerType', '=', 'tenant')->pluck($this->primaryKey)->diff(
            self::leftJoin($foreignTable, "$tableName.$this->foreignModelIdColumn", '=', "$foreignTable.$foreignPK")->whereNotNull("$foreignTable.$foreignPK")->pluck("$tableName.$this->primaryKey")
        )->union($serversWithoutAccount)->all();
        $message = $orphanIds ? "$tableName table has orphans." : "Orphans didnt found.";
        $oResult = ['status' => $orphanIds ? 1 : 0, 'message' => $message, 'orphansIds' => $orphanIds];
        // dd(DB::getQueryLog());

        return $oResult;
    }
}
