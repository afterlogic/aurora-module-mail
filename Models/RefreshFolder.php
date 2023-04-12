<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\System\Classes\Model;
use Aurora\Modules\Mail\Models\MailAccount;

/**
 * Aurora\Modules\Mail\Models\RefreshFolder
 *
 * @property integer $Id
 * @property integer $IdAccount
 * @property string $FolderFullName
 * @property boolean $AlwaysRefresh
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\RefreshFolder find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\RefreshFolder findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\RefreshFolder first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\RefreshFolder firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\RefreshFolder where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder whereAlwaysRefresh($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder whereFolderFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder whereIdAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\RefreshFolder whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|RefreshFolder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RefreshFolder extends Model
{
    protected $table = 'mail_refresh_folders';

    protected $foreignModel = MailAccount::class;
    protected $foreignModelIdColumn = 'IdAccount'; // Column that refers to an external table

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Id',
        'IdAccount',
        'FolderFullName',
        'Type'
    ];

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
    ];

    protected $casts = [
        'AlwaysRefresh' => 'boolean'
    ];

    protected $attributes = [
    ];
}
