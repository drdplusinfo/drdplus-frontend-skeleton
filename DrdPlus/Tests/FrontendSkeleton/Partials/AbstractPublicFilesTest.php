<?php
namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use DrdPlus\FrontendSkeleton\Partials\AbstractPublicFiles;
use Granam\Tests\Tools\TestWithMockery;
use Mockery\MockInterface;

class AbstractPublicFilesTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_remove_map_files(): void
    {
        /** @var MockInterface|AbstractPublicFiles $abstractPublicFiles */
        $abstractPublicFiles = $this->mockery(AbstractPublicFiles::class);
        $abstractPublicFiles->shouldAllowMockingProtectedMethods();
        $abstractPublicFiles->shouldReceive('removeMapFiles')
            ->passthru();
        $files = ['/foo.js.min', '/foo.js'];
        $withoutMapFiles = $abstractPublicFiles->removeMapFiles($files);
        self::assertSame($files, $withoutMapFiles);
        $filesWithMap = $files;
        $filesWithMap[] = '/foo.js.map';
        $withoutMapFiles = $abstractPublicFiles->removeMapFiles($filesWithMap);
        self::assertSame($files, $withoutMapFiles);
    }

    /**
     * @test
     */
    public function I_can_filter_non_unique_files(): void
    {
        /** @var MockInterface|AbstractPublicFiles $abstractPublicFiles */
        $abstractPublicFiles = $this->mockery(AbstractPublicFiles::class);
        $abstractPublicFiles->shouldReceive('__construct')
            ->passthru();
        $abstractPublicFiles->__construct(true /* prefer minified */);
        $abstractPublicFiles->shouldAllowMockingProtectedMethods();
        $abstractPublicFiles->shouldReceive('filterUniqueFiles')
            ->passthru();
        $files = ['/foo.min.js', '/foo.js', '/foo.js.map'];
        $withoutNonMinified = $abstractPublicFiles->filterUniqueFiles($files);
        self::assertSame(['/foo.min.js', '/foo.js.map'], $withoutNonMinified);
        $abstractPublicFiles->__construct(false /* prefer non-minified */);
        $abstractPublicFiles->shouldAllowMockingProtectedMethods();
        $abstractPublicFiles->shouldReceive('filterUniqueFiles')
            ->passthru();
        $withoutMinified = $abstractPublicFiles->filterUniqueFiles($files);
        self::assertSame(['/foo.js', '/foo.js.map'], $withoutMinified);
    }
}
