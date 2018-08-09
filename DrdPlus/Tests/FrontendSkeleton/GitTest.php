<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class GitTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function Generic_assets_are_versioned(): void
    {
        foreach (['css/generic', 'images/generic', 'js/generic'] as $assetsDir) {
            ['output' => $output, 'result' => $result] = $this->getDirVersioning($assetsDir);
            self::assertLessThanOrEqual(1, $result); // GIT results into 1 if dir is not ignored
            self::assertSame([], $output, "The $assetsDir dir should be versioned, but is ignored");
        }
    }

    /**
     * @test
     */
    public function Vendor_dir_is_versioned_as_well(): void
    {
        ['output' => $output, 'result' => $result] = $this->getDirVersioning($this->getVendorRoot());
        if ($this->isSkeletonChecked()) {
            self::assertSame(0, $result);
            self::assertSame([$this->getVendorRoot()], $output, 'The vendor dir should be ignored for skeleton');
        } else {
            self::assertLessThanOrEqual(1, $result); // GIT results into 1 if dir is not ignored
            self::assertSame([], $output, 'The vendor dir should be versioned, but is ignored');
        }
    }
}