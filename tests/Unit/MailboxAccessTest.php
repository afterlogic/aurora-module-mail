<?php

namespace Aurora\Modules\Mail\Tests\Unit;

use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Mail\Models\MailAccount;
use Aurora\Modules\Mail\Module;
use Aurora\System\Api;
use Aurora\System\Enums\UserRole;
use Aurora\System\Exceptions\ApiException;
use Aurora\System\Notifications;
use PHPUnit\Framework\TestCase;

class MailboxAccessTest extends TestCase
{
    /** @dataProvider mailboxOwners */
    public function testMailboxAccess($role, $ownerId, $allowed): void
    {
        $user = new User();
        $user->Id = 7;
        $user->Role = $role;
        $account = new MailAccount();
        $account->IdUser = $ownerId;
        $session = new \ReflectionProperty(Api::class, 'oAuthenticatedUser');
        $previous = $session->getValue();
        $session->setValue(null, $user);
        try {
            if (!$allowed) {
                $this->expectException(ApiException::class);
                $this->expectExceptionCode(Notifications::AccessDenied);
            }
            Module::checkAccountAccess($account);
            $this->assertTrue($allowed);
        } finally {
            $session->setValue(null, $previous);
        }
    }

    public static function mailboxOwners(): array
    {
        return [
            [UserRole::TenantAdmin, 7, true],
            [UserRole::TenantAdmin, 8, false],
            [UserRole::NormalUser, 7, true],
            [UserRole::NormalUser, 8, false],
            [UserRole::SuperAdmin, 8, true],
        ];
    }
}
