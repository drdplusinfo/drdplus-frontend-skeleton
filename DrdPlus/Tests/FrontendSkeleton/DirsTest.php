<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

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
        $dirs = new $dirsClass($this->getMasterDocumentRoot(), $this->getDocumentRoot());
        self::assertSame(\realpath($this->getDocumentRoot()), \realpath($dirs->getDocumentRoot()));
        self::assertSame(\realpath($this->getWebRoot()), \realpath($dirs->getWebRoot()));
        self::assertSame(\realpath($this->getVendorRoot()), \realpath($dirs->getVendorRoot()));
        self::assertSame(\realpath($this->getPartsRoot()), \realpath($dirs->getPartsRoot()));
        self::assertSame(\realpath($this->getGenericPartsRoot()), \realpath($dirs->getGenericPartsRoot()));
        self::assertSame(\realpath($this->getDirForVersions()), \realpath($dirs->getDirForVersions()));
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

    public function I_can_create_it_with_custom_document_root(): void
    {
        $dirsClass = static::getSutClass();
        /** @var Dirs $dirs */
        $dirs = new $dirsClass('foo', 'bar');
        self::assertSame('foo', $dirs->getMasterDocumentRoot());
        self::assertSame('bar', $dirs->getDocumentRoot());
        self::assertSame('bar/web', $dirs->getWebRoot());
        self::assertSame('bar/vendor', $dirs->getVendorRoot());
        self::assertSame('bar/parts', $dirs->getPartsRoot());
        self::assertSame($this->getGenericPartsRoot(), $dirs->getGenericPartsRoot());
        self::assertSame('bar/versions', $dirs->getDirForVersions());
    }

    /**
     * @test
     */
    public function _I_can_get_cache_root(): void
    {
        $dirsClass = static::getSutClass();
        /** @var Dirs $dirs */
        $dirs = new $dirsClass('foo', 'bar');
        self::assertSame('bar/cache/' . \PHP_SAPI, $dirs->getCacheRoot());
    }
}