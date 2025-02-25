<?php

declare (strict_types=1);
namespace Rector\Caching\ValueObject\Storage;

use RectorPrefix20210923\Nette\Utils\Random;
use PHPStan\File\FileWriter;
use Rector\Caching\Contract\ValueObject\Storage\CacheStorageInterface;
use Rector\Caching\ValueObject\CacheFilePaths;
use Rector\Caching\ValueObject\CacheItem;
use RectorPrefix20210923\Symplify\EasyCodingStandard\Caching\Exception\CachingException;
use RectorPrefix20210923\Symplify\SmartFileSystem\SmartFileSystem;
/**
 * Inspired by https://github.com/phpstan/phpstan-src/blob/1e7ceae933f07e5a250b61ed94799e6c2ea8daa2/src/Cache/FileCacheStorage.php
 */
final class FileCacheStorage implements \Rector\Caching\Contract\ValueObject\Storage\CacheStorageInterface
{
    /**
     * @var string
     */
    private $directory;
    /**
     * @var \Symplify\SmartFileSystem\SmartFileSystem
     */
    private $smartFileSystem;
    public function __construct(string $directory, \RectorPrefix20210923\Symplify\SmartFileSystem\SmartFileSystem $smartFileSystem)
    {
        $this->directory = $directory;
        $this->smartFileSystem = $smartFileSystem;
    }
    /**
     * @param string $key
     * @param string $variableKey
     */
    public function load($key, $variableKey)
    {
        return (function (string $key, string $variableKey) {
            $cacheFilePaths = $this->getCacheFilePaths($key);
            $filePath = $cacheFilePaths->getFilePath();
            if (!\is_file($filePath)) {
                return null;
            }
            $cacheItem = (require $filePath);
            if (!$cacheItem instanceof \Rector\Caching\ValueObject\CacheItem) {
                return null;
            }
            if (!$cacheItem->isVariableKeyValid($variableKey)) {
                return null;
            }
            return $cacheItem->getData();
        })($key, $variableKey);
    }
    /**
     * @param string $key
     * @param string $variableKey
     */
    public function save($key, $variableKey, $data) : void
    {
        $cacheFilePaths = $this->getCacheFilePaths($key);
        $this->smartFileSystem->mkdir($cacheFilePaths->getFirstDirectory());
        $this->smartFileSystem->mkdir($cacheFilePaths->getSecondDirectory());
        $path = $cacheFilePaths->getFilePath();
        $tmpPath = \sprintf('%s/%s.tmp', $this->directory, \RectorPrefix20210923\Nette\Utils\Random::generate());
        $errorBefore = \error_get_last();
        $exported = @\var_export(new \Rector\Caching\ValueObject\CacheItem($variableKey, $data), \true);
        $errorAfter = \error_get_last();
        if ($errorAfter !== null && $errorBefore !== $errorAfter) {
            throw new \RectorPrefix20210923\Symplify\EasyCodingStandard\Caching\Exception\CachingException(\sprintf('Error occurred while saving item %s (%s) to cache: %s', $key, $variableKey, $errorAfter['message']));
        }
        // for performance reasons we don't use SmartFileSystem
        \PHPStan\File\FileWriter::write($tmpPath, \sprintf("<?php declare(strict_types = 1);\n\nreturn %s;", $exported));
        $renameSuccess = @\rename($tmpPath, $path);
        if ($renameSuccess) {
            return;
        }
        @\unlink($tmpPath);
        if (\DIRECTORY_SEPARATOR === '/' || !\file_exists($path)) {
            throw new \RectorPrefix20210923\Symplify\EasyCodingStandard\Caching\Exception\CachingException(\sprintf('Could not write data to cache file %s.', $path));
        }
    }
    /**
     * @param string $key
     */
    public function clean($key) : void
    {
        $cacheFilePaths = $this->getCacheFilePaths($key);
        $this->smartFileSystem->remove([$cacheFilePaths->getFirstDirectory(), $cacheFilePaths->getSecondDirectory(), $cacheFilePaths->getFilePath()]);
    }
    public function clear() : void
    {
        $this->smartFileSystem->remove($this->directory);
    }
    private function getCacheFilePaths(string $key) : \Rector\Caching\ValueObject\CacheFilePaths
    {
        $keyHash = \sha1($key);
        $firstDirectory = \sprintf('%s/%s', $this->directory, \substr($keyHash, 0, 2));
        $secondDirectory = \sprintf('%s/%s', $firstDirectory, \substr($keyHash, 2, 2));
        $filePath = \sprintf('%s/%s.php', $secondDirectory, $keyHash);
        return new \Rector\Caching\ValueObject\CacheFilePaths($firstDirectory, $secondDirectory, $filePath);
    }
}
