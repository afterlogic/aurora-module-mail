<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Mail;

if (PHP_SAPI !== 'cli') {
    exit("Use the console for running this script");
}

require_once \dirname(__file__) . "/../../../system/autoload.php";
\Aurora\System\Api::Init();

function log($message)
{
    \Aurora\System\Api::Log($message, \Aurora\System\Enums\LogLevel::Full, 'scheduled-autoresponder-');
}

if (Module::getInstance()->oModuleSettings->AllowScheduledAutoresponder) {
    $accounts = Models\MailAccount::where('Properties->' . 'Mail::AutoresponderScheduled', true)
        ->where('Properties->' . 'Mail::AutoresponderStart', '<', time())->get();

    $sieveManager = Module::getInstance()->getSieveManager();

    foreach ($accounts as $account) {
        /** @var \Aurora\Modules\Mail\Models\MailAccount $account */
        log('Process account: ' . $account->Id);
        $end = $account->getExtendedProp('Mail::AutoresponderEnd');
        $disableAutoResponder = ($end !== null && $end < time());
        if ($disableAutoResponder) {
            log('Disable scheduled autoresponder');
            $account->setExtendedProp('Mail::AutoresponderScheduled', false);
            $account->save();
        }

        $autoResponder = $sieveManager->getAutoresponder($account);
        if ($autoResponder) {
            if ($disableAutoResponder) {
                log('Disable autoresponder');
                $sieveManager->setAutoresponder($account, $autoResponder['Subject'], $autoResponder['Message'], false);
            } elseif (!$autoResponder['Enable']) {
                log('Enable autoresponder');
                $sieveManager->setAutoresponder($account, $autoResponder['Subject'], $autoResponder['Message'], true);
            }
        } else {
            log('Autoresponder not found');
        }
    }
} else {
    log('Scheduled autoresponder disabled');
}
