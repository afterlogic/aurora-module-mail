<?php

namespace Aurora\Modules\Mail\Tests\Unit;

use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Mail\Enums\ServerOwnerType;
use Aurora\Modules\Mail\Managers\Servers\Manager;
use Aurora\Modules\Mail\Models\Server;
use Aurora\Modules\Mail\Module;
use Aurora\System\Api;
use Aurora\System\Enums\UserRole;
use Aurora\System\Exceptions\ApiException;
use Aurora\System\Notifications;
use PHPUnit\Framework\TestCase;

class UpdateServerAuthorizationTest extends TestCase
{
    /** @dataProvider serverAccessCases */
    public function testUpdateServerEnforcesOwnership($role, $tenantId, $ownerType, $serverTenantId, $allowed): void
    {
        $user = new User();
        $user->Role = $role;
        $user->IdTenant = $tenantId;

        $server = new Server();
        $server->OwnerType = $ownerType;
        $server->TenantId = $serverTenantId;
        $server->Name = 'Original server';

        $manager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getServer', 'updateServer'])
            ->getMock();
        $manager->method('getServer')->with(1)->willReturn($server);
        $manager->expects($allowed ? $this->once() : $this->never())->method('updateServer')
            ->with($this->identicalTo($server))->willReturn(true);

        $module = $this->getMockBuilder(Module::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getServersManager'])
            ->getMock();
        $module->method('getServersManager')->willReturn($manager);

        $authenticatedUser = new \ReflectionProperty(Api::class, 'oAuthenticatedUser');
        $previousUser = $authenticatedUser->isInitialized() ? $authenticatedUser->getValue() : null;
        $authenticatedUser->setValue(null, $user);

        try {
            $result = $module->UpdateServer(
                1,
                'Updated server',
                'imap.example.test',
                993,
                true,
                'smtp.example.test',
                465,
                true,
                0,
                'example.test',
                true,
                false,
                4190,
                SmtpPassword: '*****',
                TenantId: $tenantId
            );
            $this->assertTrue($allowed);
            $this->assertTrue($result);
            $this->assertSame('Updated server', $server->Name);
        } catch (ApiException $exception) {
            $this->assertFalse($allowed);
            $this->assertSame(Notifications::AccessDenied, $exception->getCode());
            $this->assertSame('Original server', $server->Name);
        } finally {
            $authenticatedUser->setValue(null, $previousUser);
        }
    }

    public static function serverAccessCases(): array
    {
        return [
            'tenant admin and own tenant server' => [UserRole::TenantAdmin, 1, ServerOwnerType::Tenant, 1, true],
            'tenant admin and another tenant server' => [UserRole::TenantAdmin, 1, ServerOwnerType::Tenant, 2, false],
            'tenant admin and global server' => [UserRole::TenantAdmin, 1, ServerOwnerType::SuperAdmin, 0, false],
            'normal user and own tenant server' => [UserRole::NormalUser, 1, ServerOwnerType::Tenant, 1, false],
            'superadmin and global server' => [UserRole::SuperAdmin, 0, ServerOwnerType::SuperAdmin, 0, true],
        ];
    }
}
