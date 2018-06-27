<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class WebVersionSwitchMutex extends StrictObject
{

    /** @var CacheRoot */
    private $cacheRoot;
    /** @var string */
    private $lockFileLinkName;
    /** @var string|null */
    private $lockedForId;

    public function __construct(CacheRoot $cacheRoot)
    {
        $this->cacheRoot = $cacheRoot;
        $this->lockFileLinkName = $this->createLockFileName('link');
    }

    private function createLockFileName(string $suffix): string
    {
        $lockFileName = $this->cacheRoot->getCacheRootDir() . '/' . \basename(__FILE__, '.php');

        return "{$lockFileName}.{$suffix}.lock";
    }

    /**
     * @param string $lockId
     * @param int $wait
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotWriteLockOfVersionMutex
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLockVersionMutex
     */
    public function lock(string $lockId, int $wait = 2): bool
    {
        $this->lockedForId = $lockId;
        $lockFileWithId = $this->createLockFileName($lockId);
        if (!\file_exists($lockFileWithId)) {
            if (!\file_put_contents($lockFileWithId, $lockId)) {
                throw new Exceptions\CanNotWriteLockOfVersionMutex("Can not create file $lockFileWithId");
            }
            \system('sync', $syncReturn); // flushes any non-yet-written data from memory to disk
            if ($syncReturn !== 0) {
                throw new Exceptions\CanNotWriteBuffersToDisk("'sync' command failed with return value {$syncReturn}");
            }
        }
        $waitUntil = \time() + $wait;
        $attempts = 0;
        do {
            $attempts++;
            if (!\is_link($this->lockFileLinkName)) {
                $locked = @\symlink($lockFileWithId, $this->lockFileLinkName);
                if ($locked) {
                    return true; // we did it!
                } else {
                    die($lockFileWithId . '-' . $this->lockFileLinkName);
                }
            } elseif (\file_get_contents($this->lockFileLinkName) === $lockId) {
                return false; // already locked for required ID, but not by us
            }
            \sleep(1); // let's wait a bit until previous process releases the lock
        } while (\time() < $waitUntil);
        $this->unlock(); // closes file handle
        throw new Exceptions\CanNotLockVersionMutex(
            "Even after {$wait} seconds and {$attempts} attempts the lock has not been obtained on file {$this->lockFileLinkName}"
        );
    }

    public function isLocked(): bool
    {
        return \file_exists($this->lockFileLinkName);
    }

    public function isLockedForId(string $lockId): bool
    {
        return ($this->lockedForId === null // still may be locked by another instance or process
                || $this->lockedForId === $lockId // we locked it
            )
            && \file_exists($this->lockFileLinkName) && \file_exists($this->createLockFileName($lockId));
    }

    public function __destruct()
    {
        $this->unlock();
    }

    public function unlock(): bool
    {
        if ($this->lockedForId === null /* WE have not locked anything */ || !$this->isLockedForId($this->lockedForId)) {
            return false;
        }

        return \unlink($this->lockFileLinkName);
    }
}