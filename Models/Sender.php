<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;

class Sender extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
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