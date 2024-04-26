<?php

namespace Aurora\Modules\Mail\Models;

use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Mail\Module;
use Aurora\System\Classes\Account as SystemAccount;

/**
 * Aurora\Modules\Mail\Models\MailAccount
 *
 * @property integer $Id
 * @property boolean $IsDisabled
 * @property integer $IdUser
 * @property boolean $UseToAuthorize
 * @property string $Email
 * @property string $FriendlyName
 * @property string $IncomingLogin
 * @property string $IncomingPassword
 * @property boolean $IncludeInUnifiedMailbox
 * @property boolean $UseSignature
 * @property mixed|null $Signature
 * @property integer $ServerId
 * @property string|null $FoldersOrder
 * @property boolean $UseThreading
 * @property boolean $SaveRepliesToCurrFolder
 * @property boolean $ShowUnifiedMailboxLabel
 * @property string $UnifiedMailboxLabelText
 * @property string $UnifiedMailboxLabelColor
 * @property string|null $XOAuth
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property array|null $Properties
 * @property-read \Aurora\Modules\Mail\Models\Server $Server
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\MailAccount find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\MailAccount findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\MailAccount first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\MailAccount firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\MailAccount where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereFoldersOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereFriendlyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereIdUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\Mail\Models\MailAccount whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereIncludeInUnifiedMailbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereIncomingLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereIncomingPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereIsDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereSaveRepliesToCurrFolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereShowUnifiedMailboxLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereUnifiedMailboxLabelColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereUnifiedMailboxLabelText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereUseSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereUseThreading($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereUseToAuthorize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailAccount whereXOAuth($value)
 * @mixin \Eloquent
 */
class MailAccount extends SystemAccount
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
        'Properties' => 'array',
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
        if ($oMailModule instanceof Module) {
            return !$oMailModule->getAccountsManager()->useToAuthorizeAccountExists($this->Email, $this->Id);
        } else {
            return false;
        }
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
        if ($oServer instanceof \Aurora\Modules\Mail\Models\Server) {
            $aResponse['Server'] = $oServer->toResponseArray();

            $oMailModule = \Aurora\Modules\Mail\Module::getInstance();
            if ($oServer->EnableSieve && $oMailModule) {
                $aResponse['AllowFilters'] = $oMailModule->oModuleSettings->AllowFilters;
                $aResponse['AllowForward'] = $oMailModule->oModuleSettings->AllowForward;
                $aResponse['AllowAutoresponder'] = $oMailModule->oModuleSettings->AllowAutoresponder;
                $aResponse['EnableAllowBlockLists'] = $oMailModule->oModuleSettings->EnableAllowBlockLists;
            }
        }

        $aResponse['CanBeUsedToAuthorize'] = $this->canBeUsedToAuthorize();

        $aArgs = ['Account' => $this];
        \Aurora\System\Api::GetModule('Core')->broadcastEvent(
            'Mail::Account::ToResponseArray',
            $aArgs,
            $aResponse
        );

        return $aResponse;
    }

    public function getServer()
    {
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

    public function Server()
    {
        return $this->belongsTo(Server::class, 'ServerId', 'Id');
    }
}
