<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;

class Sender extends Model
{
    protected $table = 'mail_trusted_senders';
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