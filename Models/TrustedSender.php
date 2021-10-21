<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;
use Aurora\Modules\Core\Models\User;

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