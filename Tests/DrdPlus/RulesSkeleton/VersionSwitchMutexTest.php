<?php
namespace Tests\DrdPlus\RulesSkeleton;

use DrdPlus\FrontendSkeleton\VersionSwitchMutex;
use PHPUnit\Framework\TestCase;

class VersionSwitchMutexTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_get_and_release_lock(): void
    {
        $mutex = new VersionSwitchMutex();
        self::assertFalse($mutex->isLockedForId('foo'), 'Should not be locked yet');
        self::assertFalse($mutex->isLockedForId('bar'), 'Should not be locked yet');
        self::assertTrue($mutex->lock('foo'), 'Can not get lock via mutex');
        self::assertTrue($mutex->isLockedForId('foo'), 'Should be locked for "foo" version');
        self::assertFalse($mutex->isLockedForId('bar'), 'Should be locked for different version');
        self::assertTrue($mutex->unlock(), 'Can not unlock mutex');
        self::assertFalse($mutex->isLockedForId('foo'), 'Should be already unlocked');
        self::assertFalse($mutex->isLockedForId('bar'), 'Should be already unlocked');
        self::assertFalse($mutex->unlock(), 'Second unlock in a row should NOT be successful');
    }

    /**
     * @test
     */
    public function I_can_not_lock_twice(): void
    {
        $mutex = new VersionSwitchMutex();
        $mutex->lock('foo');
        $message = \exec(<<<'PHP'
php -r 'include "vendor/autoload.php";
try {
    (new DrdPlus\RulesSkeleton\VersionSwitchMutex)->lock("bar", 0 /* no wait */);
} catch(\DrdPlus\RulesSkeleton\Exceptions\CanNotLockVersionMutex $exception) {
    echo $exception->getMessage();
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
    public function I_can_not_get_lock_with_invalid_lock_dir()
    {
        $mutex = new VersionSwitchMutex('/just/a/small/flash/memory/in/another/universe');
        $mutex->lock('foo');
    }
}
