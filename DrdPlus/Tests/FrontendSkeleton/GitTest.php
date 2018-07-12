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
        $lastRow = $this->executeCommand('git check-ignore vendor');
        self::assertSame('', $lastRow, 'The vendor dir should be versioned, but is ignored');
    }
}