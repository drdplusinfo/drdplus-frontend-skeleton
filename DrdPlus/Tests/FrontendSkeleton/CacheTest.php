<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\Dirs;
use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\Tests\FrontendSkeleton\Partials\DirsForTestsTrait;
use Granam\Tests\Tools\TestWithMockery;
use Mockery\MockInterface;

class CacheTest extends TestWithMockery
{
    use DirsForTestsTrait;

    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_will_get_cache_dir_depending_on_current_version(): void
    {
        $webVersions = $this->mockery(WebVersions::class);
        $webVersions->shouldReceive('getCurrentVersion')
            ->andReturnValues(['master', '9.8.7']); // sequential, returns different value for first and second call
        $dirs = $this->createDirs();
        /** @var WebVersions $webVersions */
        $cache = $this->createSut($webVersions, $dirs);
        self::assertSame($dirs->getCacheRoot() . '/master', $cache->getCacheDir());
        self::assertSame($dirs->getCacheRoot() . '/9.8.7', $cache->getCacheDir());
    }

    /**
     * @param WebVersions $webVersions
     * @param Dirs $dirs
     * @return Cache|MockInterface
     * @throws \ReflectionException
     */
    private function createSut(WebVersions $webVersions, Dirs $dirs): Cache
    {
        $cache = $this->mockery(static::getSutClass());
        $cacheReflection = new \ReflectionClass(static::getSutClass());
        $constructor = $cacheReflection->getMethod('__construct');
        $constructor->invoke($cache, $webVersions, $dirs, false, 'foo');
        $cache->makePartial();

        return $cache;
    }
}