<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Dirs;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class DirsTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $dirsClass = static::getSutClass();
        /** @var Dirs $dirs */
        $dirs = new $dirsClass('foo');
        self::assertSame('foo', $dirs->getDocumentRoot());
        self::assertSame('foo/vendor', $dirs->getVendorRoot());
        self::assertSame('foo/parts', $dirs->getPartsRoot());
        self::assertSame($this->unifyPath($this->getGenericPartsRoot()), $this->unifyPath($dirs->getGenericPartsRoot()));
        self::assertSame('foo/versions', $dirs->getDirForVersions());
        self::assertSame('foo/cache/' . \PHP_SAPI, $dirs->getCacheRoot());
        self::assertSame('foo/versions/1.2', $dirs->getVersionRoot('1.2'));
        self::assertSame('foo/versions/1.2/web', $dirs->getVersionWebRoot('1.2'));
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_rewrite_every_dir_in_child_class(): void
    {
        $reflection = new \ReflectionClass(static::getSutClass());
        foreach ($reflection->getProperties() as $property) {
            self::assertTrue(
                $property->isProtected(),
                static::getSutClass() . '::' . $property->getName() . ' should be protected'
            );
        }
    }
}