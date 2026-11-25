<?php

namespace Aurora\Modules\Mail\Tests\Unit;

use Aurora\Modules\Mail\Module;
use Aurora\System\Enums\SortOrder;
use Aurora\System\Exceptions\ApiException;
use Aurora\System\Notifications;
use PHPUnit\Framework\TestCase;

class MessageSortTest extends TestCase
{
    private function moduleWithSortSettings($settings): Module
    {
        $module = (new \ReflectionClass(Module::class))->newInstanceWithoutConstructor();
        $module->oModuleSettings = (object) ['MessagesSortBy' => $settings];
        return $module;
    }

    public function testSupportedSortKeysAndDefaults(): void
    {
        $module = $this->moduleWithSortSettings(['Allow' => true, 'DefaultSortBy' => 'arrival']);
        foreach (['arrival', 'from', 'to', 'size', 'date', 'cc', 'subject', 'displayfrom', 'displayto'] as $key) {
            $this->assertSame([strtoupper($key), SortOrder::DESC], $module->getSortInfo(ucfirst($key), SortOrder::DESC));
        }
        $this->assertSame(['ARRIVAL', SortOrder::DESC], $module->getSortInfo(null, SortOrder::DESC));
        $this->assertSame(['', SortOrder::ASC], $module->getSortInfo('', SortOrder::DESC));
        $this->assertSame(['', SortOrder::ASC], $this->moduleWithSortSettings(false)->getSortInfo('size', SortOrder::DESC));
    }

    /** @dataProvider invalidSortCases */
    public function testInvalidSortIsRejected($settings, $key): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionCode(Notifications::InvalidInputParameter);
        $this->moduleWithSortSettings($settings)->getSortInfo($key, SortOrder::ASC);
    }

    public static function invalidSortCases(): array
    {
        return [
            [['Allow' => true], "from\r\n"],
            [['Allow' => true], 'from size'],
            [['Allow' => true], ['from']],
            [false, 'unknown'],
            [['Allow' => true, 'DefaultSortBy' => 'unrecognized'], null],
        ];
    }
}
