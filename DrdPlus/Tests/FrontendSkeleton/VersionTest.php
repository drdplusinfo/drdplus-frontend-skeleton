<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class VersionTest extends AbstractContentTest
{
    /**
     * @test
     * @backupGlobals enabled
     */
    public function I_will_get_latest_version_by_default(): void
    {
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertFalse(false, 'Nothing to test, there is just a single version');
        }
        self::assertNotSame(
            'master',
            $this->getTestsConfiguration()->getExpectedLastVersion(),
            'Expected some stable version'
        );
        self::assertSame(
            $this->getTestsConfiguration()->getExpectedLastVersion(),
            $this->fetchHtmlDocumentFromLocalUrl()->documentElement->getAttribute('data-version')
        );
    }

    protected function fetchHtmlDocumentFromLocalUrl(): HtmlDocument
    {
        $content = $this->fetchContentFromLink($this->getTestsConfiguration()->getLocalUrl(), true)['content'];
        self::assertNotEmpty($content);

        return new HtmlDocument($content);
    }
}