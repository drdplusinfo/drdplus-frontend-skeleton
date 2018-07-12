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
    public function Vendor_dir_is_versioned_as_well(): void
    {
        $documentRootEscaped = \escapeshellarg($this->getDocumentRoot());
        $vendorRootEscaped = \escapeshellarg($this->getVendorRoot());
        \exec("git -C $documentRootEscaped check-ignore $vendorRootEscaped 2>&1", $output, $result);
        if ($this->isSkeletonChecked()) {
            self::assertSame(0, $result);
            self::assertSame([$this->getVendorRoot()], $output, 'The vendor dir should be ignored for skeleton');
        } else {
            self::assertLessThanOrEqual(1, $result); // GIT results into 1 if dir is not ignored
            self::assertSame([], $output, 'The vendor dir should be versioned, but is ignored');
        }
    }
}