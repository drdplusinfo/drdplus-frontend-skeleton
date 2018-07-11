<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

abstract class Cache extends StrictObject
{
    public const CACHE = 'cache';
    public const DISABLE = 'disable';

    /** @var string */
    private $cacheRoot;
    /** @var WebVersions */
    private $webVersions;
    /** @var string */
    private $cachePrefix;
    /** @var bool */
    private $isInProduction;

    /**
     * @param CacheRoot $cacheRoot
     * @param WebVersions $webVersions
     * @param bool $isInProduction
     * @param string $cachePrefix
     * @throws \RuntimeException
     */
    public function __construct(
        CacheRoot $cacheRoot,
        WebVersions $webVersions,
        bool $isInProduction,
        string $cachePrefix
    )
    {
        $this->cacheRoot = $cacheRoot->getCacheRootDir() . '/' . $webVersions->getCurrentVersion();
        if (!\file_exists($this->cacheRoot)) {
            if (!@\mkdir($this->cacheRoot, 0775, true /* with parents */) && !\is_dir($this->cacheRoot)) {
                throw new \RuntimeException('Can not create directory for page cache ' . $this->cacheRoot);
            }
            if (PHP_SAPI === 'cli') {
                \chgrp($this->cacheRoot, 'www-data');
            }
            \chmod($this->cacheRoot, 0775); // because umask could suppress it
        }
        $this->webVersions = $webVersions;
        $this->isInProduction = $isInProduction;
        $this->cachePrefix = $cachePrefix;
    }

    /**
     * @return string
     */
    public function getCacheRoot(): string
    {
        return $this->cacheRoot;
    }

    public function isInProduction(): bool
    {
        return $this->isInProduction;
    }

    private function getCurrentGetHash(): string
    {
        return \md5(\serialize($_GET));
    }

    /**
     * @return bool
     * @throws \RuntimeException
     */
    public function isCacheValid(): bool
    {
        return ($_GET[static::CACHE] ?? '') !== static::DISABLE && \is_readable($this->getCacheFileName());
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotGetGitStatus
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function getCacheFileName(): string
    {
        return $this->cacheRoot . "/{$this->getCacheFileBaseNamePartWithoutGet()}_{$this->getCurrentGetHash()}.html";
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotGetGitStatus
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function getCacheFileBaseNamePartWithoutGet(): string
    {
        $prefix = \md5($this->getCachePrefix() . $this->getGitStamp());

        return "{$this->webVersions->getCurrentVersion()}_{$prefix}_{$this->webVersions->getCurrentCommitHash()}";
    }

    protected function getCachePrefix(): string
    {
        return $this->cachePrefix;
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotGetGitStatus
     */
    private function getGitStamp(): string
    {
        if ($this->isInProduction()) {
            return 'production';
        }
        // GIT status is same for any working dir, if it sub-dir of GIT project root
        \exec('git diff', $changedRows, $return);
        if ($return !== 0) {
            throw new Exceptions\CanNotGetGitStatus(
                'Can not run `git diff`, got result code ' . $return
            );
        }
        if (\count($changedRows) === 0) {
            return 'unchanged';
        }

        return \md5(\implode($changedRows));
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotReadCachedContent
     * @throws \RuntimeException
     */
    public function getCachedContent(): string
    {
        $cachedContent = \file_get_contents($this->getCacheFileName());
        if ($cachedContent === false) {
            throw new Exceptions\CanNotReadCachedContent("Can not read cached content from '{$this->getCacheFileName()}'");
        }

        return $cachedContent;
    }

    /**
     * @param string $content
     * @throws \RuntimeException
     */
    public function saveContentForDebug(string $content): void
    {
        \file_put_contents($this->getCacheDebugFileName(), $content, \LOCK_EX);
        \chmod($this->getCacheDebugFileName(), 0664);
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    private function getCacheDebugFileName(): string
    {
        return $this->cacheRoot . "/{$this->geCacheDebugFileBaseNamePartWithoutGet()}_{$this->getCurrentGetHash()}.html";
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotGetGitStatus
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function geCacheDebugFileBaseNamePartWithoutGet(): string
    {
        return 'debug_' . $this->getCacheFileBaseNamePartWithoutGet();
    }

    /**
     * @param string $content
     * @throws \RuntimeException
     */
    public function cacheContent(string $content): void
    {
        \file_put_contents($this->getCacheFileName(), $content, \LOCK_EX);
        \chmod($this->getCacheFileName(), 0664);
        $this->clearOldCache();
    }

    /**
     * @throws \RuntimeException
     */
    private function clearOldCache(): void
    {
        $foldersToSkip = ['.', '..', '.gitignore'];
        $currentCacheStamp = $this->webVersions->getCurrentCommitHash();
        $currentVersion = $this->webVersions->getCurrentVersion();
        foreach (\scandir($this->cacheRoot, SCANDIR_SORT_NONE) as $folder) {
            if (\in_array($folder, $foldersToSkip, true)) {
                continue;
            }
            if (\strpos($folder, $currentVersion) === false) { // we will clear old cache only of currently selected version
                continue;
            }
            if (\strpos($folder, $currentCacheStamp) !== false) { // that file is valid
                continue;
            }
            \unlink($this->cacheRoot . '/' . $folder);
        }
    }
}