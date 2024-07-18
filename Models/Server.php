<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\Modules\Core\Models\Tenant;
use Aurora\Modules\Mail\Models\MailAccount;
use Aurora\System\Classes\Model;

/**
 * Aurora\Modules\Mail\Models\Server
 *
 * @property integer $Id
 * @property integer $TenantId
 * @property string $Name
 * @property string $IncomingServer
 * @property integer $IncomingPort
 * @property boolean $IncomingUseSsl
 * @property string $OutgoingServer
 * @property integer $OutgoingPort
 * @property boolean $OutgoingUseSsl
 * @property string $SmtpAuthType
 * @property string $SmtpLogin
 * @property string $SmtpPassword
 * @property string $OwnerType
 * @property string|null $Domains
 * @property boolean $EnableSieve
 * @property integer $SievePort
 * @property boolean $EnableThreading
 * @property boolean $UseFullEmailAddressAsLogin
 * @property boolean $SetExternalAccessServers
 * @property string $ExternalAccessImapServer
 * @property integer $ExternalAccessImapPort
 * @property integer $ExternalAccessImapAlterPort
 * @property string $ExternalAccessSmtpServer
 * @property integer $ExternalAccessSmtpPort
 * @property integer $ExternalAccessSmtpAlterPort
 * @property string $ExternalAccessPop3Server
 * @property integer $ExternalAccessPop3Port
 * @property integer $ExternalAccessPop3AlterPort
 * @property boolean $OAuthEnable
 * @property string $OAuthName
 * @property string $OAuthType
 * @property string $OAuthIconUrl
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property boolean $ExternalAccessImapUseSsl
 * @property boolean $ExternalAccessPop3UseSsl
 * @property boolean $ExternalAccessSmtpUseSsl
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MailAccount> $MailAccounts
 * @property-read int|null $mail_accounts_count
 * @property-read mixed $entity_id
 * @property-read mixed $server_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|Server find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|Server findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Server first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|Server firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Server newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Server newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Server query()
 * @method static \Illuminate\Database\Eloquent\Builder|Server leftJoin(string $table, \Closure|string $first, string|null $operator = null, string|null $second = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Server where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|Server orWhere(\Closure|array|string|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereDomains($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereEnableSieve($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereEnableThreading($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessImapAlterPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessImapPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessImapServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessImapUseSsl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessPop3AlterPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessPop3Port($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessPop3Server($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessPop3UseSsl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessSmtpAlterPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessSmtpPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessSmtpServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereExternalAccessSmtpUseSsl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereIncomingPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereIncomingServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereIncomingUseSsl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOAuthEnable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOAuthIconUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOAuthName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOAuthType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOutgoingPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOutgoingServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOutgoingUseSsl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereSetExternalAccessServers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereSievePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereSmtpAuthType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereSmtpLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereSmtpPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Server whereUseFullEmailAddressAsLogin($value)
 * @mixin \Eloquent
 */
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
        'ExternalAccessImapUseSsl',
        'ExternalAccessPop3Server',
        'ExternalAccessPop3Port',
        'ExternalAccessPop3AlterPort',
        'ExternalAccessPop3UseSsl',
        'ExternalAccessSmtpServer',
        'ExternalAccessSmtpPort',
        'ExternalAccessSmtpAlterPort',
        'ExternalAccessSmtpUseSsl',

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
        'Properties' => 'array',
        'IncomingUseSsl' => 'boolean',
        'OutgoingUseSsl' => 'boolean',
        'EnableSieve' => 'boolean',
        'EnableThreading' => 'boolean',
        'UseFullEmailAddressAsLogin' => 'boolean',
        'SetExternalAccessServers' => 'boolean',
        'OAuthEnable' => 'boolean',
        'SmtpPassword' => \Aurora\System\Casts\Encrypt::class,
        'ExternalAccessImapUseSsl' => 'boolean',
        'ExternalAccessPop3UseSsl' => 'boolean',
        'ExternalAccessSmtpUseSsl' => 'boolean',
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
            return ['status' => -1, 'message' => 'Foreign field doesn\'t exist'];
        }
        $tableName = $this->getTable();
        $foreignObject = new $this->foreignModel();
        $foreignTable = $foreignObject->getTable();
        $foreignPK = $foreignObject->primaryKey;

        // DB::enableQueryLog();
        $oAccount = new MailAccount();
        $accountTable = $oAccount->getTable();

        $serversWithoutAccount = self::leftJoin($accountTable, "$accountTable.ServerId", '=', "$tableName.$this->primaryKey")->where('OwnerType', '=', 'account')->whereNull("$accountTable.Id")->groupBy("$tableName.$this->primaryKey")->pluck("$tableName.$this->primaryKey")->all();
        $orphanIds = self::where('OwnerType', '=', 'tenant')->pluck($this->primaryKey)->diff(
            self::leftJoin($foreignTable, "$tableName.$this->foreignModelIdColumn", '=', "$foreignTable.$foreignPK")->whereNotNull("$foreignTable.$foreignPK")->pluck("$tableName.$this->primaryKey")
        )->union($serversWithoutAccount)->all();
        $message = $orphanIds ? "$tableName table has orphans." : "Orphans were not found.";
        $oResult = ['status' => $orphanIds ? 1 : 0, 'message' => $message, 'orphansIds' => $orphanIds];
        // dd(DB::getQueryLog());

        return $oResult;
    }
}
