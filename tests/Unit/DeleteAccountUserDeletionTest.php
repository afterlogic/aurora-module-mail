<?php

namespace Aurora\Modules\Mail\Tests\Unit;

use Aurora\Modules\Mail\Module;
use Aurora\Modules\Mail\Managers\Accounts\Manager as AccountsManager;
use Aurora\Modules\Mail\Models\MailAccount;
use Aurora\System\Api;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class FakeCoreDecorator
{
    public $deleteUserCalls = [];

    public function DeleteUser($UserId)
    {
        $this->deleteUserCalls[] = $UserId;
        return true;
    }
}

class StubAccountsManager extends AccountsManager
{
    public $accounts = [];
    public $deletedAccounts = [];

    public function __construct()
    {
    }

    public function getAccountById($iAccountId)
    {
        return $this->accounts[$iAccountId] ?? null;
    }

    public function getAccounts($aFilters)
    {
        $results = [];
        foreach ($this->accounts as $oAccount) {
            $bMatch = true;
            foreach ($aFilters as $sKey => $mValue) {
                if (isset($oAccount->{$sKey})) {
                    if ($oAccount->{$sKey} !== $mValue) {
                        $bMatch = false;
                        break;
                    }
                }
            }
            if ($bMatch) {
                $results[] = $oAccount;
            }
        }
        return new Collection($results);
    }

    public function deleteAccount(MailAccount $oAccount)
    {
        $this->deletedAccounts[] = $oAccount->Id;
        unset($this->accounts[$oAccount->Id]);
        return true;
    }
}

class StubServersManager
{
    public $deletedServers = [];

    public function deleteServer($iServerId, $iTenantId = 0)
    {
        $this->deletedServers[] = $iServerId;
        return true;
    }
}

class StubIdentitiesManager
{
    public function deleteAccountIdentities($iAccountId)
    {
    }
}

class StubMailManager
{
    public function deleteSystemFolderNames($iAccountId)
    {
    }
}

class FakeUser
{
    public $Id;
    public $IdTenant;
    public $PublicId;
    public $Role;

    public function __construct($id, $publicId, $role = 0)
    {
        $this->Id = $id;
        $this->PublicId = $publicId;
        $this->Role = $role;
    }

    public function getExtendedProp($key)
    {
        return null;
    }
}

class TestableMailModule extends Module
{
    public $accountsManager;
    public $identitiesManager;
    public $mailManager;
    public $serversManager;

    public function __construct($sPath = '', $sVersion = '1.0')
    {
        if (empty($sPath)) {
            $sPath = dirname(__DIR__, 4) . '/';
        }
        parent::__construct($sPath, $sVersion);
    }

    public function init()
    {
    }

    public function getAccountsManager()
    {
        return $this->accountsManager;
    }

    public function getIdentitiesManager()
    {
        return $this->identitiesManager;
    }

    public function getMailManager()
    {
        return $this->mailManager;
    }

    public function getServersManager()
    {
        return $this->serversManager;
    }

    public function broadcastEvent($sEvent, &$aArguments = [], &$mResult = null)
    {
    }

    public function updateAllocatedTenantSpace($iTenantId, $iQuota, $iNewQuota = 0)
    {
    }

    public function DeleteServer($ServerId, $TenantId = 0)
    {
        $mAccounts = $this->getAccountsManager()->getAccounts(['ServerId' => $ServerId]);
        if ($mAccounts) {
            foreach ($mAccounts as $oAccount) {
                $this->DeleteAccount($oAccount->Id);
            }
        }

        return $this->getServersManager()->deleteServer($ServerId, $TenantId);
    }
}

class DeleteAccountUserDeletionTest extends TestCase
{
    private $fakeCoreDecorator;
    private $testableModule;
    private $accountsManager;

    protected function setUp(): void
    {
        Api::skipCheckUserRole(true);
        Api::GetModuleManager();

        $this->fakeCoreDecorator = new FakeCoreDecorator();
        Api::$aModuleDecorators['Core'] = $this->fakeCoreDecorator;

        $this->accountsManager = new StubAccountsManager();

        $this->testableModule = new TestableMailModule();
        $this->testableModule->accountsManager = $this->accountsManager;
        $this->testableModule->identitiesManager = new StubIdentitiesManager();
        $this->testableModule->mailManager = new StubMailManager();
        $this->testableModule->serversManager = new StubServersManager();

        Api::$aModuleDecorators['Mail'] = $this->testableModule;

        $this->setUsersCache([
            100 => new FakeUser(100, 'user@example.com', 0)
        ]);
    }

    protected function tearDown(): void
    {
        Api::skipCheckUserRole(false);
        Api::$aModuleDecorators['Core'] = null;
        Api::$aModuleDecorators['Mail'] = null;
        $this->clearUsersCache();
    }

    private function setUsersCache($users)
    {
        $ref = new \ReflectionProperty(Api::class, 'usersCache');
        $ref->setAccessible(true);
        $ref->setValue(null, $users);
    }

    private function clearUsersCache()
    {
        $ref = new \ReflectionProperty(Api::class, 'usersCache');
        $ref->setAccessible(true);
        $ref->setValue(null, []);
    }

    private function createMockAccount($id, $idUser, $useToAuthorize, $email, $isDisabled = false)
    {
        $oAccount = new class extends MailAccount {
            public function __construct() {}
            public function getServer() { return null; }
        };

        $oAccount->Id = $id;
        $oAccount->IdUser = $idUser;
        $oAccount->UseToAuthorize = $useToAuthorize;
        $oAccount->Email = $email;
        $oAccount->IsDisabled = $isDisabled;
        $oAccount->ServerId = 1;

        $this->accountsManager->accounts[$id] = $oAccount;
        return $oAccount;
    }

    public function testDeleteAccountDeletesUserWhenLastUseToAuthorizeAccount()
    {
        $this->createMockAccount(1, 100, true, 'user@example.com');

        $result = $this->testableModule->DeleteAccount(1);

        $this->assertTrue($result);
        $this->assertCount(1, $this->fakeCoreDecorator->deleteUserCalls);
        $this->assertEquals(100, $this->fakeCoreDecorator->deleteUserCalls[0]);
    }

    public function testDeleteAccountDoesNotDeleteUserWhenOtherUseToAuthorizeAccountsExist()
    {
        $this->createMockAccount(1, 100, true, 'user@example.com');
        $this->createMockAccount(2, 100, true, 'secondary@example.com');

        $result = $this->testableModule->DeleteAccount(1);

        $this->assertTrue($result);
        $this->assertCount(0, $this->fakeCoreDecorator->deleteUserCalls);
    }

    public function testDeleteAccountDoesNotDeleteUserWhenAccountIsNotUseToAuthorize()
    {
        $this->createMockAccount(1, 100, false, 'user@example.com');

        $result = $this->testableModule->DeleteAccount(1);

        $this->assertTrue($result);
        $this->assertCount(0, $this->fakeCoreDecorator->deleteUserCalls);
    }

    public function testDeleteServerDeletesAllAccountsWithoutDirectlyDeletingUsers()
    {
        $this->createMockAccount(1, 100, true, 'user@example.com');
        $this->createMockAccount(2, 200, false, 'user2@example.com');

        $this->setUsersCache([
            100 => new FakeUser(100, 'user@example.com', 0),
            200 => new FakeUser(200, 'user2@example.com', 0)
        ]);

        $result = $this->testableModule->DeleteServer(1, 0);

        $this->assertTrue($result);
        $this->assertEquals([1, 2], $this->accountsManager->deletedAccounts);
        $this->assertEquals([1], $this->testableModule->getServersManager()->deletedServers);
    }

    public function testDeleteServerDeletesUserWhenLastUseToAuthorizeAccountRemoved()
    {
        $this->createMockAccount(1, 100, true, 'user@example.com');

        $result = $this->testableModule->DeleteServer(1, 0);

        $this->assertTrue($result);
        $this->assertEquals([1], $this->accountsManager->deletedAccounts);
        $this->assertCount(1, $this->fakeCoreDecorator->deleteUserCalls);
        $this->assertEquals(100, $this->fakeCoreDecorator->deleteUserCalls[0]);
    }

    public function testDeleteServerDoesNotDeleteUserWhenOtherUseToAuthorizeAccountsRemain()
    {
        // User 100 has UseToAuthorize account on server 1 (being deleted)
        // and UseToAuthorize account on server 2 (not being deleted)
        $this->createMockAccount(1, 100, true, 'user@example.com');
        $this->createMockAccount(2, 100, true, 'other@example.com');
        $account2 = $this->accountsManager->accounts[2];
        $account2->ServerId = 2;

        $result = $this->testableModule->DeleteServer(1, 0);

        $this->assertTrue($result);
        $this->assertEquals([1], $this->accountsManager->deletedAccounts);
        $this->assertCount(0, $this->fakeCoreDecorator->deleteUserCalls);
    }
}
