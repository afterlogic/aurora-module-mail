<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;

class RefreshFolder extends Model
{
    protected $table = 'mail_refresh_folders';    

	protected $foreignModel = 'Aurora\Modules\Mail\Models\MailAccount';
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