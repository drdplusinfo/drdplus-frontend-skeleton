<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\WebVersions;
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
            $this->getTestsConfiguration()->getExpectedLastUnstableVersion(),
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

    /**
     * @test
     * @dataProvider provideRequestSource
     * @param string $source
     */
    public function I_can_switch_to_every_version(string $source): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        foreach ($webVersions->getAllWebVersions() as $webVersion) {
            $post = [];
            $cookies = [];
            $url = $this->getTestsConfiguration()->getLocalUrl();
            if ($source === 'get') {
                $url .= '?version=' . $webVersion;
            } elseif ($source === 'post') {
                $post = ['version' => $webVersion];
            } elseif ($source === 'cookies') {
                $cookies = ['version' => $webVersion];
            }
            $content = $this->fetchContentFromLink($url, true, $post, $cookies)['content'];
            self::assertNotEmpty($content);
            $document = new HtmlDocument($content);
            $versionFromContent = $document->documentElement->getAttribute('data-version');
            self::assertNotNull($versionFromContent, "Can not find attribute 'data-version' in content fetched from $url");
            self::assertSame($webVersion, $versionFromContent, 'Expected different version, seems version switching does not work');
        }
    }

    public function provideRequestSource(): array
    {
        return [
            ['get'],
            ['post'],
            ['cookies'],
        ];
    }
}