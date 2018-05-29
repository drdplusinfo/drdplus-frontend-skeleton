<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\CacheRoot;
use PHPUnit\Framework\TestCase;

class CacheRootTest extends TestCase
{
    /**
     * @test
     */
    public function _I_can_get_cache_root_dir(): void
    {
        self::assertSame(\sys_get_temp_dir() . '/cache/' . \PHP_SAPI, (new CacheRoot(\sys_get_temp_dir()))->getCacheRootDir());
    }
}
