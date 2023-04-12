<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\System\Classes\Model;
use Aurora\Modules\Mail\Models\MailAccount;

/**
 * Aurora\Modules\Mail\Models\SystemFolder
 *
 * @property integer $Id
 * @property integer $IdAccount
 * @property string $FolderFullName
 * @property integer $Type
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\SystemFolder find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\SystemFolder findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\SystemFolder first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\SystemFolder firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\SystemFolder where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder whereFolderFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder whereIdAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\SystemFolder whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemFolder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SystemFolder extends Model
{
    protected $table = 'mail_system_folders';

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
    ];

    protected $attributes = [
    ];
}
