<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\Modules\Contacts\Models\Group;
use \Aurora\System\Classes\Model;
use Aurora\Modules\Core\Models\User;

class MailAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $foreignModel = User::class;
    protected $foreignModelIdColumn = 'IdUser'; // Column that refers to an external table
    protected $fillable = [
        'Id',
        'IsDisabled',
        'IdUser',
        'UseToAuthorize',
        'Email',
        'FriendlyName',
        'IncomingLogin',
        'IncomingPassword',
        'UseSignature',
        'Signature',
        'ServerId',
        'FoldersOrder',
        'UseThreading',
        'SaveRepliesToCurrFolder',
        'IncludeInUnifiedMailbox',
        'ShowUnifiedMailboxLabel',
        'UnifiedMailboxLabelText',
        'UnifiedMailboxLabelColor',
        'XOAuth'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    protected $casts = [
        'IncomingPassword' => \Aurora\System\Casts\Encrypt::class,
        'IsDisabled' => 'boolean',
        'UseToAuthorize' => 'boolean',
        'IncludeInUnifiedMailbox' => 'boolean',
        'UseSignature' => 'boolean',
        'ShowUnifiedMailboxLabel' => 'boolean',
        'UseThreading' => 'boolean',
        'SaveRepliesToCurrFolder' => 'boolean',
		'FoldersOrder' => 'string'
    ];

    protected $attributes = [
    ];

    protected $appends = [
      'EntityId'
    ];

    // public function getIncomingPasswordAttribute()
    // {
    //     $sPassword = '';
    //     if (!$this->attributes['IncomingPassword']) // TODO: Legacy support
    //     {
    //         $sSalt = \Aurora\System\Api::$sSalt;
    //         \Aurora\System\Api::$sSalt = md5($sSalt);
    //         $sPassword = $this->attributes['IncomingPassword'];
    //         \Aurora\System\Api::$sSalt = $sSalt;
    //     }
    //     else
    //     {
    //         $sPassword = $this->attributes['IncomingPassword'];
    //     }

    //     $sPassword = \Aurora\System\Utils::DecryptValue($sPassword);

    //     if ($sPassword !== '' && strpos($sPassword, $this->IncomingLogin . ':') === false)
    //     {
    //         $this->IncomingPassword = $sPassword;
    //         \Aurora\System\Api::GetModule('Mail')->getAccountsManager()->updateAccount($this);
    //     }
    //     else
    //     {
    //         $sPassword = substr($sPassword, strlen($this->IncomingLogin) + 1);
    //     }

    //     return $sPassword;
    // }

    // public function setIncomingPasswordAttribute($sPassword)
    // {
    //     $this->attributes['IncomingPassword'] = \Aurora\System\Utils::EncryptValue($this->IncomingLogin . ':' . $sPassword);
    // }

    public function setPassword($sPassword)
    {
        $this->IncomingPassword = $sPassword;
    }

    public function getPassword()
    {
        return $this->IncomingPassword;
    }

    private function canBeUsedToAuthorize()
    {
        $oMailModule = \Aurora\System\Api::GetModule('Mail');
        return !$oMailModule->getAccountsManager()->useToAuthorizeAccountExists($this->Email, $this->Id);
    }

    public function getDefaultTimeOffset()
    {
        return 0;
    }

    public function toResponseArray()
    {
        $aResponse = parent::toResponseArray();
        $aResponse['AccountID'] = $this->Id;
        $aResponse['AllowFilters'] = false;
        $aResponse['AllowForward'] = false;
        $aResponse['AllowAutoresponder'] = false;
		$aResponse['EnableAllowBlockLists'] = false;

        if (!isset($aResponse['Signature'])) {
            $aResponse['Signature'] = '';
        }

        $oServer = $this->getServer();
        if ($oServer instanceof \Aurora\System\Classes\Model)
        {
            $aResponse['Server'] = $oServer->toResponseArray();

            $oMailModule = \Aurora\System\Api::GetModule('Mail');
            if ($oServer->EnableSieve && $oMailModule)
            {
                $aResponse['AllowFilters'] = $oMailModule->getConfig('AllowFilters', '');
                $aResponse['AllowForward'] = $oMailModule->getConfig('AllowForward', '');
                $aResponse['AllowAutoresponder'] = $oMailModule->getConfig('AllowAutoresponder', '');
                $aResponse['EnableAllowBlockLists'] = $oMailModule->getConfig('EnableAllowBlockLists', false);
            }
        }

        $aResponse['CanBeUsedToAuthorize'] = $this->canBeUsedToAuthorize();
//		unset($aResponse['IncomingPassword']);

        $aArgs = ['Account' => $this];
        \Aurora\System\Api::GetModule('Core')->broadcastEvent(
            'Mail::Account::ToResponseArray',
            $aArgs,
            $aResponse
        );

        return $aResponse;
    }

    public function getServer() {
        return $this->Server;
    }

	public function getLogin()
	{
		$oServer = $this->getServer();
		if ($oServer && !$oServer->UseFullEmailAddressAsLogin) {
			return $this->Email;
		}
		return $this->IncomingLogin;
	}

    public function Server() {
        return $this->belongsTo(Server::class, 'ServerId', 'Id');
    }

}