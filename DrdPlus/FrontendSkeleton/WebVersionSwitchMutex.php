<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class WebVersionSwitchMutex extends StrictObject
{

    /** @var string */
    private $lockFile;
    /** @var null|resource */
    private $lockFileHandle;

    public function __construct(CacheRoot $cacheRoot)
    {
        $this->lockFile = $cacheRoot->getCacheRootDir() . \basename(__FILE__, '.php') . '.lock';
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
        $waitUntil = \time() + $wait;
        $locked = null;
        $handle = $this->getLockFileHandle();
        $attempts = 0;
        do {
            $attempts++;
            if ($locked !== null) {
                \sleep(1);
            }
            $locked = \flock($handle, LOCK_EX | LOCK_NB);
        } while (!$locked && \time() < $waitUntil);
        if (!$locked) {
            $this->unlock(); // closes file handle
            throw new Exceptions\CanNotLockVersionMutex(
                "Even after {$wait} seconds and {$attempts} attempts the lock has not been obtained on file {$this->lockFile}"
            );
        }
        \fwrite($handle, $lockId);

        return true;
    }

    /**
     * @return resource
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotWriteLockOfVersionMutex
     */
    private function getLockFileHandle()
    {
        if (!$this->lockFileHandle) {
            $this->lockFileHandle = @\fopen($this->lockFile, 'ab');
            if (!$this->lockFileHandle) {
                throw new Exceptions\CanNotWriteLockOfVersionMutex(
                    "Can not use {$this->lockFile} as a lock file, can not write to it"
                );
            }
        }

        return $this->lockFileHandle;
    }

    public function isLockedForId(string $lockId): bool
    {
        return \file_exists($this->lockFile) && \file_get_contents($this->lockFile) === $lockId;
    }

    public function __destruct()
    {
        $this->unlock();
    }

    public function unlock(): bool
    {
        if (!$this->lockFileHandle) {
            return false;
        }
        $unlocked = \flock($this->lockFileHandle, LOCK_UN); // it is no harm to unlock it even if it was not locked
        \fclose($this->lockFileHandle);
        $this->lockFileHandle = null;
        if (\file_exists($this->lockFile)) {
            \unlink($this->lockFile);
        }

        return $unlocked;
    }
}