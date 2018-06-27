<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

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