<?php

declare (strict_types=1);
namespace RectorPrefix20210923\Symplify\EasyTesting\DataProvider;

use Symplify\SmartFileSystem\SmartFileInfo;
use RectorPrefix20210923\Symplify\SmartFileSystem\SmartFileSystem;
final class StaticFixtureUpdater
{
    /**
     * @param \Symplify\SmartFileSystem\SmartFileInfo|string $originalFileInfo
     */
    public static function updateFixtureContent($originalFileInfo, string $changedContent, \Symplify\SmartFileSystem\SmartFileInfo $fixtureFileInfo) : void
    {
        if (!\getenv('UPDATE_TESTS') && !\getenv('UT')) {
            return;
        }
        $newOriginalContent = self::resolveNewFixtureContent($originalFileInfo, $changedContent);
        self::getSmartFileSystem()->dumpFile($fixtureFileInfo->getRealPath(), $newOriginalContent);
    }
    public static function updateExpectedFixtureContent(string $newOriginalContent, \Symplify\SmartFileSystem\SmartFileInfo $expectedFixtureFileInfo) : void
    {
        if (!\getenv('UPDATE_TESTS') && !\getenv('UT')) {
            return;
        }
        self::getSmartFileSystem()->dumpFile($expectedFixtureFileInfo->getRealPath(), $newOriginalContent);
    }
    private static function getSmartFileSystem() : \RectorPrefix20210923\Symplify\SmartFileSystem\SmartFileSystem
    {
        return new \RectorPrefix20210923\Symplify\SmartFileSystem\SmartFileSystem();
    }
    /**
     * @param \Symplify\SmartFileSystem\SmartFileInfo|string $originalFileInfo
     */
    private static function resolveNewFixtureContent($originalFileInfo, string $changedContent) : string
    {
        if ($originalFileInfo instanceof \Symplify\SmartFileSystem\SmartFileInfo) {
            $originalContent = $originalFileInfo->getContents();
        } else {
            $originalContent = $originalFileInfo;
        }
        if ($originalContent === $changedContent) {
            return $originalContent;
        }
        return $originalContent . '-----' . \PHP_EOL . $changedContent;
    }
}
