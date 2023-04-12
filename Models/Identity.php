<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\System\Classes\Model;
use Aurora\Modules\Mail\Models\MailAccount;

/**
 * Aurora\Modules\Mail\Models\Identity
 *
 * @property integer $Id
 * @property integer $IdUser
 * @property integer $IdAccount
 * @property boolean $Default
 * @property string $Email
 * @property string $FriendlyName
 * @property boolean $UseSignature
 * @property string|null $Signature
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Identity find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Identity findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Identity first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Identity firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Identity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Identity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Identity query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Identity where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereFriendlyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereIdAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereIdUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\Identity whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity whereUseSignature($value)
 * @mixin \Eloquent
 */
class Identity extends Model
{
    protected $table = 'mail_identities';

    protected $foreignModel = MailAccount::class;
    protected $foreignModelIdColumn = 'IdAccount'; // Column that refers to an external table

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Id',
        'IdUser',
        'IdAccount',
        'Default',
        'Email',
        'FriendlyName',
        'UseSignature',
        'Signature'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    protected $casts = [
        'Default' => 'boolean',
        'UseSignature' => 'boolean',
        'Signature' => 'string'
    ];

    protected $attributes = [
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
        $aResponse['EntityId'] = $this->Id;

        return $aResponse;
    }
}
