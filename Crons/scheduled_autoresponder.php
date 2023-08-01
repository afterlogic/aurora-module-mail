<?php

namespace Aurora\Modules\Mail;

require_once dirname(__file__)."/../../system/autoload.php";
\Aurora\System\Api::Init();

$accounts = Models\MailAccount::where('Properties->' . self::GetName() . '::Scheduled', true)
    ->where('Properties->'.self::GetName() . '::Start', '<', time())->get();

$sieveManager = Module::getInstance()->getSieveManager();

foreach ($accounts as $account) {
    $end = $account->getExtendedProp(self::GetName() . '::End');
    $disableAutoResponder = ($end !== null && $end > time());
    if ($disableAutoResponder) {
        $account->setExtendedProp(self::GetName() . '::Scheduled', false);
        $account->save();
    }

    $autoResponder = $sieveManager->getAutoresponder($account);    
    if ($autoResponder) {
        if ($disableAutoResponder)  {
            $sieveManager->setAutoresponder($account, $autoResponder['Subject'], $autoResponder['Text'], false);
        } else if (!$autoResponder['Enable']) {
            $sieveManager->setAutoresponder($account, $autoResponder['Subject'], $autoResponder['Text'], true);
        }
    }
}