<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class CacheTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_will_get_cache_dir_depending_on_current_version(): void
    {
        $webVersions = $this->mockery($this->getWebVersionsClass());
        $webVersions->shouldReceive('getCurrentMinorVersion')
            ->andReturnValues(['master', '9.8.7']); // sequential, returns different value for first and second call
        $dirs = $this->createDirs();
        /** @var WebVersions $webVersions */
        $cacheClass = $this->getCacheClass();
        /** @var Cache $cache */
        $cache = new $cacheClass($webVersions, $dirs, $this->createRequest(), $this->createGit(), false, 'foo');
        self::assertSame($dirs->getCacheRoot() . '/master', $cache->getCacheDir());
        self::assertSame($dirs->getCacheRoot() . '/9.8.7', $cache->getCacheDir());
    }
}