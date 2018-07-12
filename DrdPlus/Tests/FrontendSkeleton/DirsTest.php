<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Dirs;
use PHPUnit\Framework\TestCase;

class DirsTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $dirs = new Dirs(
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
}