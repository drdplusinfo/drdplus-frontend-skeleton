<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\CacheRoot;
use Granam\Tests\Tools\TestWithMockery;

class CacheRootTest extends TestWithMockery
{
    /**
     * @test
     */
    public function _I_can_get_cache_root_dir(): void
    {
        $cacheRootClass = static::getSutClass();
        /** @var CacheRoot $cacheRoot */
        $cacheRoot = new $cacheRootClass(\sys_get_temp_dir());
        self::assertSame(\sys_get_temp_dir() . '/cache/' . \PHP_SAPI, $cacheRoot->getCacheRootDir());
    }
}