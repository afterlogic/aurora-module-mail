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
    /**
     * Reimplementation of the comparator used in Managers\Main\Manager::getFolders()
     * but accepting the already flipped folders-order map.
     */
    private function comparator($aFoldersOrderFlipped)
    {
        return function ($oFolderA, $oFolderB) use ($aFoldersOrderFlipped) {
            if (!$aFoldersOrderFlipped) {
                if (\Aurora\Modules\Mail\Enums\FolderType::Custom !== $oFolderA->getType() || \Aurora\Modules\Mail\Enums\FolderType::Custom !== $oFolderB->getType()) {
                    if ($oFolderA->getType() === $oFolderB->getType()) {
                        return 0;
                    }

                    return $oFolderA->getType() < $oFolderB->getType() ? -1 : 1;
                }
            } else {
                $iPosA = isset($aFoldersOrderFlipped[$oFolderA->getRawFullName()]) ? $aFoldersOrderFlipped[$oFolderA->getRawFullName()] : false;
                $iPosB = isset($aFoldersOrderFlipped[$oFolderB->getRawFullName()]) ? $aFoldersOrderFlipped[$oFolderB->getRawFullName()] : false;
                if (is_int($iPosA) && is_int($iPosB)) {
                    return $iPosA < $iPosB ? -1 : 1;
                } elseif (is_int($iPosA)) {
                    return -1;
                } elseif (is_int($iPosB)) {
                    return 1;
                }
            }

            return strnatcmp(strtolower($oFolderA->getFullName()), strtolower($oFolderB->getFullName()));
        };
    }

    public function testSortRespectsProvidedFoldersOrder()
    {
        $fA = new FakeFolder('A', 'A', \Aurora\Modules\Mail\Enums\FolderType::Custom);
        $fB = new FakeFolder('B', 'B', \Aurora\Modules\Mail\Enums\FolderType::Custom);
        $fC = new FakeFolder('C', 'C', \Aurora\Modules\Mail\Enums\FolderType::Custom);

        $list = [$fA, $fB, $fC];

        // Suppose saved order is B, A, C
        $aFoldersOrderList = ['B', 'A', 'C'];
        $aFoldersOrderFlipped = array_flip($aFoldersOrderList);

        usort($list, $this->comparator($aFoldersOrderFlipped));

        $this->assertSame('B', $list[0]->getRawFullName());
        $this->assertSame('A', $list[1]->getRawFullName());
        $this->assertSame('C', $list[2]->getRawFullName());
    }

    public function testFallbackSortByTypeThenName()
    {
        // Inbox (type 1) should come before Custom (type 10)
        $inbox = new FakeFolder('INBOX', 'Inbox', \Aurora\Modules\Mail\Enums\FolderType::Inbox);
        $custom = new FakeFolder('ZFolder', 'zfolder', \Aurora\Modules\Mail\Enums\FolderType::Custom);

        $list = [$custom, $inbox];

        // No order list -> fallback behaviour
        usort($list, $this->comparator(null));

        $this->assertSame('INBOX', $list[0]->getRawFullName());
        $this->assertSame('ZFolder', $list[1]->getRawFullName());
    }
}
