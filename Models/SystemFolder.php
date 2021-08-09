<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;

class SystemFolder extends Model
{
    protected $table = 'mail_system_folders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
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