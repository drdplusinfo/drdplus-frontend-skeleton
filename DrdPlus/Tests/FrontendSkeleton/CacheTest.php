<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\CacheRoot;
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
    public function I_will_get_cache_root_depending_on_current_version(): void
    {
        $webVersions = $this->mockery(WebVersions::class);
        $webVersions->shouldReceive('getCurrentVersion')
            ->andReturnValues(['master', '9.8.7']); // sequential, returns different value for first and second call
        $cacheRoot = new CacheRoot($this->getDocumentRoot());
        /** @var WebVersions $webVersions */
        $cache = $this->createSut($cacheRoot, $webVersions);
        self::assertSame($cacheRoot->getCacheRootDir(). '/master', $cache->getCacheRoot());
        self::assertSame($cacheRoot->getCacheRootDir() . '/9.8.7', $cache->getCacheRoot());
    }

    /**
     * @param CacheRoot $cacheRoot
     * @param WebVersions $webVersions
     * @return Cache|MockInterface
     * @throws \ReflectionException
     */
    private function createSut(CacheRoot $cacheRoot, WebVersions $webVersions): Cache
    {
        $cache = $this->mockery(static::getSutClass());
        $cacheReflection = new \ReflectionClass(static::getSutClass());
        $constructor = $cacheReflection->getMethod('__construct');
        $constructor->invoke($cache, $cacheRoot, $webVersions, false, 'foo');
        $cache->makePartial();

        return $cache;
    }
}