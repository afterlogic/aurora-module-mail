<?php

namespace Aurora\Modules\Mail\Models;

use \Aurora\System\Classes\Model;
use Aurora\Modules\Mail\Models\MailAccount;

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

    public function getEntityIdAttribute() {
        return $this->Id;
    }


    public function toResponseArray()
    {
        $aResponse = parent::toResponseArray();
        $aResponse['EntityId'] = $this->Id;

        return $aResponse;
    }
}