<?php

use PHPUnit\Framework\TestCase;

class FakeFolder
{
    private $rawFullName;
    private $fullName;
    private $type;

    public function __construct($rawFullName, $fullName = null, $type = null)
    {
        $this->rawFullName = $rawFullName;
        $this->fullName = $fullName === null ? $rawFullName : $fullName;
        $this->type = $type === null ? \Aurora\Modules\Mail\Enums\FolderType::Custom : $type;
    }

    public function getRawFullName()
    {
        return $this->rawFullName;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getType()
    {
        return $this->type;
    }
}

class ManagerFolderSortTest extends TestCase
{
    public function testSortRespectsProvidedFoldersOrder()
    {
        $fA = new FakeFolder('A', 'A', \Aurora\Modules\Mail\Enums\FolderType::Custom);
        $fB = new FakeFolder('B', 'B', \Aurora\Modules\Mail\Enums\FolderType::Custom);
        $fC = new FakeFolder('C', 'C', \Aurora\Modules\Mail\Enums\FolderType::Custom);

        $list = [$fA, $fB, $fC];

        $aFoldersOrderList = ['B', 'A', 'C'];
        $aFoldersOrderFlipped = array_flip($aFoldersOrderList);

        usort($list, function ($oFolderA, $oFolderB) use ($aFoldersOrderFlipped) {
            return \Aurora\Modules\Mail\Managers\Main\Manager::compareFolders($oFolderA, $oFolderB, $aFoldersOrderFlipped);
        });

        $this->assertSame('B', $list[0]->getRawFullName());
        $this->assertSame('A', $list[1]->getRawFullName());
        $this->assertSame('C', $list[2]->getRawFullName());
    }

    public function testFallbackSortByTypeThenName()
    {
        $inbox = new FakeFolder('INBOX', 'Inbox', \Aurora\Modules\Mail\Enums\FolderType::Inbox);
        $custom = new FakeFolder('ZFolder', 'zfolder', \Aurora\Modules\Mail\Enums\FolderType::Custom);

        $list = [$custom, $inbox];

        usort($list, function ($oFolderA, $oFolderB) {
            return \Aurora\Modules\Mail\Managers\Main\Manager::compareFolders($oFolderA, $oFolderB, null);
        });

        $this->assertSame('INBOX', $list[0]->getRawFullName());
        $this->assertSame('ZFolder', $list[1]->getRawFullName());
    }
}
