<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;

class Server extends Model
{
    protected $table = 'mail_servers';    

    protected $foreignModel = 'Aurora\Modules\Core\Models\Tenant';
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
        'OAuthIconUrl'
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
        'SmtpPassword' => \Aurora\System\Casts\Encrypt::class
    ];

    protected $attributes = [
    ];

    protected $appends = [
        'EntityId',
        'ServerId'
    ];

    public function getServerIdAttribute() {
        return $this->Id;
    }

    public function MailAccounts() {
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
}