<?php

namespace Aurora\Modules\Mail;

require_once dirname(__file__)."/../../../system/autoload.php";
\Aurora\System\Api::Init();

if (Module::getInstance()->oModuleSettings->AllowScheduledAutoresponder) {
    $accounts = Models\MailAccount::where('Properties->' . 'Mail::Scheduled', true)
        ->where('Properties->'. 'Mail::Start', '<', time())->get();

    $sieveManager = Module::getInstance()->getSieveManager();

    foreach ($accounts as $account) {
        $end = $account->getExtendedProp('Mail::End');
        $disableAutoResponder = ($end !== null && $end < time());
        if ($disableAutoResponder) {
            $account->setExtendedProp('Mail::Scheduled', false);
            $account->save();
        }

        $autoResponder = $sieveManager->getAutoresponder($account);
        if ($autoResponder) {
            if ($disableAutoResponder) {
                $sieveManager->setAutoresponder($account, $autoResponder['Subject'], $autoResponder['Message'], false);
            } elseif (!$autoResponder['Enable']) {
                $sieveManager->setAutoresponder($account, $autoResponder['Subject'], $autoResponder['Message'], true);
            }
        }
    }
}