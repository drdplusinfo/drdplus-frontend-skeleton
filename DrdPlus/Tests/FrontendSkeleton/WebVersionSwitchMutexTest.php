<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\CacheRoot;
use DrdPlus\FrontendSkeleton\Exceptions\CanNotLockVersionMutex;
use DrdPlus\FrontendSkeleton\WebVersionSwitchMutex;
use PHPUnit\Framework\TestCase;

class WebVersionSwitchMutexTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_get_and_release_lock(): void
    {
        $mutex = new WebVersionSwitchMutex(new CacheRoot(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST)));
        self::assertFalse($mutex->isLockedForId('foo'), 'Should not be locked yet');
        self::assertFalse($mutex->isLockedForId('bar'), 'Should not be locked yet');
        self::assertTrue($mutex->lock('foo'), 'Can not get lock via mutex');
        self::assertTrue($mutex->isLockedForId('foo'), 'Should be locked for "foo" version');
        self::assertFalse($mutex->isLockedForId('bar'), 'Should be locked for different version');
        self::assertTrue($mutex->unlock(), 'Can not unlock mutex');
        self::assertFalse($mutex->isLockedForId('foo'), 'Should be already unlocked');
        self::assertFalse($mutex->isLockedForId('bar'), 'Should be already unlocked');
        self::assertFalse($mutex->unlock(), 'Second unlock in a row should NOT be successful');
        self::assertTrue($mutex->lock('foo'), 'Can not get lock via mutex');
        self::assertTrue($mutex->isLockedForId('foo'), 'Should be locked for "foo" version');
        $anotherMutex = new WebVersionSwitchMutex(new CacheRoot(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST)));
        self::assertTrue($anotherMutex->isLockedForId('foo'), 'Should be locked for "foo" version using any mutex instance');
        $mutex->__destruct();
        unset($mutex);
        self::assertFalse($anotherMutex->isLockedForId('foo'), 'Lock should be already released as another instance of mutex has been destroyed');
    }

    /**
     * @test
     */
    public function I_can_not_lock_twice(): void
    {
        $mutex = new WebVersionSwitchMutex(new CacheRoot(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST)));
        $mutex->lock('foo');
        $mutexClass = WebVersionSwitchMutex::class;
        $cacheRootClass = CacheRoot::class;
        $canNotLockClass = CanNotLockVersionMutex::class;
        $message = \exec(<<<PHP
php -r 'require "vendor/autoload.php";
try {
    (new $mutexClass(new $cacheRootClass(".")))->lock("bar" /* locking for different version */, 0 /* no wait */);
} catch($canNotLockClass \$exception) {
    echo \$exception->getMessage();
    exit(0);
}
exit(1);'
PHP
            , $output,
            $returnCode
        );

        self::assertSame(0, $returnCode, $message);
        $mutex->unlock();
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\CanNotWriteLockOfVersionMutex
     * @expectedExceptionMessageRegExp ~/just/a/small/flash/memory/in/another/universe~
     */
    public function I_can_not_get_lock_with_invalid_lock_dir(): void
    {
        $mutex = new WebVersionSwitchMutex(new CacheRoot('/just/a/small/flash/memory/in/another/universe'));
        $mutex->lock('foo');
    }
}
