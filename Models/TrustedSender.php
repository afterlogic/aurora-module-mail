<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\System\Classes\Model;
use Aurora\Modules\Core\Models\User;

/**
 * Aurora\Modules\Mail\Models\TrustedSender
 *
 * @property integer $Id
 * @property integer $IdUser
 * @property string $Email
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\TrustedSender find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\TrustedSender findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\TrustedSender first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\TrustedSender firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\TrustedSender where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender whereIdUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\TrustedSender whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|TrustedSender whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TrustedSender extends Model
{
    protected $table = 'mail_trusted_senders';

    protected $foreignModel = User::class;
    protected $foreignModelIdColumn = 'IdUser'; // Column that refers to an external table

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Id',
        'IdUser',
        'Email'
    ];

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
    ];

    protected $casts = [
    ];

    protected $attributes = [
    ];
}
