<?php

declare (strict_types=1);
namespace RectorPrefix20210923\Symplify\ComposerJsonManipulator\FileSystem;

use RectorPrefix20210923\Nette\Utils\Json;
use RectorPrefix20210923\Symplify\ComposerJsonManipulator\Json\JsonCleaner;
use RectorPrefix20210923\Symplify\ComposerJsonManipulator\Json\JsonInliner;
use RectorPrefix20210923\Symplify\ComposerJsonManipulator\ValueObject\ComposerJson;
use RectorPrefix20210923\Symplify\PackageBuilder\Configuration\StaticEolConfiguration;
use Symplify\SmartFileSystem\SmartFileInfo;
use RectorPrefix20210923\Symplify\SmartFileSystem\SmartFileSystem;
/**
 * @see \Symplify\MonorepoBuilder\Tests\FileSystem\JsonFileManager\JsonFileManagerTest
 */
final class JsonFileManager
{
    /**
     * @var mixed[]
     */
    private $cachedJSONFiles = [];
    /**
     * @var \Symplify\SmartFileSystem\SmartFileSystem
     */
    private $smartFileSystem;
    /**
     * @var \Symplify\ComposerJsonManipulator\Json\JsonCleaner
     */
    private $jsonCleaner;
    /**
     * @var \Symplify\ComposerJsonManipulator\Json\JsonInliner
     */
    private $jsonInliner;
    public function __construct(\RectorPrefix20210923\Symplify\SmartFileSystem\SmartFileSystem $smartFileSystem, \RectorPrefix20210923\Symplify\ComposerJsonManipulator\Json\JsonCleaner $jsonCleaner, \RectorPrefix20210923\Symplify\ComposerJsonManipulator\Json\JsonInliner $jsonInliner)
    {
        $this->smartFileSystem = $smartFileSystem;
        $this->jsonCleaner = $jsonCleaner;
        $this->jsonInliner = $jsonInliner;
    }
    /**
     * @return mixed[]
     */
    public function loadFromFileInfo(\Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo) : array
    {
        $realPath = $smartFileInfo->getRealPath();
        if (!isset($this->cachedJSONFiles[$realPath])) {
            $this->cachedJSONFiles[$realPath] = \RectorPrefix20210923\Nette\Utils\Json::decode($smartFileInfo->getContents(), \RectorPrefix20210923\Nette\Utils\Json::FORCE_ARRAY);
        }
        return $this->cachedJSONFiles[$realPath];
    }
    /**
     * @return array<string, mixed>
     */
    public function loadFromFilePath(string $filePath) : array
    {
        $fileContent = $this->smartFileSystem->readFile($filePath);
        return \RectorPrefix20210923\Nette\Utils\Json::decode($fileContent, \RectorPrefix20210923\Nette\Utils\Json::FORCE_ARRAY);
    }
    /**
     * @param mixed[] $json
     */
    public function printJsonToFileInfo(array $json, \Symplify\SmartFileSystem\SmartFileInfo $smartFileInfo) : string
    {
        $jsonString = $this->encodeJsonToFileContent($json);
        $this->smartFileSystem->dumpFile($smartFileInfo->getPathname(), $jsonString);
        $realPath = $smartFileInfo->getRealPath();
        unset($this->cachedJSONFiles[$realPath]);
        return $jsonString;
    }
    public function printComposerJsonToFilePath(\RectorPrefix20210923\Symplify\ComposerJsonManipulator\ValueObject\ComposerJson $composerJson, string $filePath) : string
    {
        $jsonString = $this->encodeJsonToFileContent($composerJson->getJsonArray());
        $this->smartFileSystem->dumpFile($filePath, $jsonString);
        return $jsonString;
    }
    /**
     * @param mixed[] $json
     */
    public function encodeJsonToFileContent(array $json) : string
    {
        // Empty arrays may lead to bad encoding since we can't be sure whether they need to be arrays or objects.
        $json = $this->jsonCleaner->removeEmptyKeysFromJsonArray($json);
        $jsonContent = \RectorPrefix20210923\Nette\Utils\Json::encode($json, \RectorPrefix20210923\Nette\Utils\Json::PRETTY) . \RectorPrefix20210923\Symplify\PackageBuilder\Configuration\StaticEolConfiguration::getEolChar();
        return $this->jsonInliner->inlineSections($jsonContent);
    }
}
