<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Dirs;
use Granam\Tests\Tools\TestWithMockery;

class DirsTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $dirsClass = self::getSutClass();
        /** @var Dirs $dirs */
        $dirs = new $dirsClass(
            'some document root',
            'some web root',
            'some vendor root',
            'some parts root',
            'some generic parts root',
            'some dir for versions'
        );
        self::assertSame('some document root', $dirs->getDocumentRoot());
        self::assertSame('some web root', $dirs->getWebRoot());
        self::assertSame('some vendor root', $dirs->getVendorRoot());
        self::assertSame('some parts root', $dirs->getPartsRoot());
        self::assertSame('some generic parts root', $dirs->getGenericPartsRoot());
        self::assertSame('some dir for versions', $dirs->getDirForVersions());
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_rewrite_every_dir_in_child_class(): void
    {
        $reflection = new \ReflectionClass(self::getSutClass());
        foreach ($reflection->getProperties() as $property) {
            self::assertTrue(
                $property->isProtected(),
                self::getSutClass() . '::' . $property->getName() . ' should be protected'
            );
        }
    }
}