<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;

class Identity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
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
    ];

    protected $attributes = [
    ];
}