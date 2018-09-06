<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Granam\String\StringTools;

class CacheTest extends AbstractContentTest
{
    /** @var string */
    protected $temporaryRootDir;

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->temporaryRootDir) {
            \exec('rm -fr ' . \escapeshellarg($this->temporaryRootDir));
        }
    }

    /**
     * @test
     * @dataProvider provideVersions
     * @param string $version
     */
    public function I_will_get_cache_dir_depending_on_current_version(string $version): void
    {
        $webVersions = $this->mockery($this->getWebVersionsClass());
        $webVersions->shouldReceive('getCurrentMinorVersion')
            ->andReturn($version);
        // using temporary NON-existing dir to use more code
        $dirs = $this->createDirs($this->getTemporaryRootDir());
        /** @var WebVersions $webVersions */
        $cacheClass = $this->getCacheClass();
        /** @var Cache $cache */
        $cache = new $cacheClass($webVersions, $dirs, $this->createRequest(), $this->createGit(), false, 'foo');
        self::assertSame($dirs->getCacheRoot() . '/' . $version, $cache->getCacheDir());
    }

    protected function getTemporaryRootDir(): string
    {
        if ($this->temporaryRootDir === null) {
            $this->temporaryRootDir = \sys_get_temp_dir() . '/' . \uniqid(StringTools::getClassBaseName(static::class), true);
        }

        return $this->temporaryRootDir;
    }

    public function provideVersions(): array
    {
        return [
            ['master'],
            ['9.8.7'],
        ];
    }
}