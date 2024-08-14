<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail\Models;

/**
 * Aurora\Modules\Mail\Models\Fetcher
 *
 * @property integer $Id
 * @property integer $IdUser
 * @property integer $IdAccount
 * @property integer $IsEnabled
 * @property string $IncomingServer
 * @property integer $IncomingPort
 * @property integer $IncomingMailSecurity
 * @property string $IncomingLogin
 * @property string $IncomingPassword
 * @property integer $LeaveMessagesOnServer
 * @property string $Folder
 * @property integer $IsOutgoingEnabled
 * @property string $Name
 * @property string $Email
 * @property string $OutgoingServer
 * @property integer $OutgoingPort
 * @property integer $OutgoingMailSecurity
 * @property integer $OutgoingUseAuth
 * @property integer $UseSignature
 * @property string $Signature
 * @property integer $IsLocked
 * @property integer $CheckInterval
 * @property integer $CheckLastTime
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Fetcher find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Fetcher findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Fetcher first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Fetcher firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Fetcher where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereCheckInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereCheckLastTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereFolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIdAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIdUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Fetcher whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIncomingLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIncomingMailSecurity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIncomingPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIncomingPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIncomingServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereIsOutgoingEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereLeaveMessagesOnServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereOutgoingMailSecurity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereOutgoingPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereOutgoingServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereOutgoingUseAuth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fetcher whereUseSignature($value)
 * @mixin \Eloquent
 */
class Fetcher extends \Aurora\System\Classes\Model
{
    protected $table = 'mail_fetchers';
    protected $fillable = [
        'Id',
        'IdUser',
        'IdAccount',

        'IsEnabled',
        'IncomingServer',
        'IncomingPort',
        'IncomingMailSecurity',
        'IncomingLogin',
        'IncomingPassword',
        'LeaveMessagesOnServer',
        'Folder',

        'IsOutgoingEnabled',
        'Name',
        'Email',
        'OutgoingServer',
        'OutgoingPort',
        'OutgoingMailSecurity',
        'OutgoingUseAuth',

        'UseSignature',
        'Signature',

        'IsLocked',
        'CheckInterval',
        'CheckLastTime'
    ];

    protected $casts = [
        'Properties' => 'array',
        'IncomingPassword' => \Aurora\System\Casts\Encrypt::class
    ];

    protected $appends = [
        'EntityId'
    ];

    public function getEntityIdAttribute()
    {
        return $this->Id;
    }

    public function toResponseArray()
    {
        $aResponse = parent::toResponseArray();
        $aResponse['IncomingUseSsl'] = $aResponse['IncomingMailSecurity'] === \MailSo\Net\Enumerations\ConnectionSecurityType::SSL;
        unset($aResponse['IncomingMailSecurity']);
        $aResponse['OutgoingUseSsl'] = $aResponse['OutgoingMailSecurity'] === \MailSo\Net\Enumerations\ConnectionSecurityType::SSL;
        unset($aResponse['OutgoingMailSecurity']);
        unset($aResponse['IncomingPassword']);
        return $aResponse;
    }
}
