<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\CookiesService;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Gt\Dom\Element;

class WebContentVersionTest extends AbstractContentTest
{
    /**
     * @test
     * @backupGlobals enabled
     */
    public function I_will_get_latest_version_by_default(): void
    {
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertFalse(false, 'Nothing to test, there is just a single version');

            return;
        }
        self::assertNotSame(
            $this->getTestsConfiguration()->getExpectedLastUnstableVersion(),
            $this->getTestsConfiguration()->getExpectedLastVersion(),
            'Expected some stable version'
        );
        $version = $this->fetchHtmlDocumentFromLocalUrl()->documentElement->getAttribute('data-content-version');
        self::assertNotEmpty($version, 'No version get from document data-version attribute');
        if ($this->getTestsConfiguration()->getExpectedLastVersion() === $this->getTestsConfiguration()->getExpectedLastUnstableVersion()) {
            self::assertSame(
                $this->getTestsConfiguration()->getExpectedLastUnstableVersion(),
                $version,
                'Expected different unstable version due to tests config'
            );
        } else {
            self::assertStringStartsWith(
                $this->getTestsConfiguration()->getExpectedLastVersion() . '.',
                $version,
                'Expected different version due to tests config'
            );
        }
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
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        foreach ($webVersions->getAllMinorVersions() as $webVersion) {
            $post = [];
            $cookies = [];
            $url = $this->getTestsConfiguration()->getLocalUrl();
            if ($source === 'get') {
                $url .= '?' . Request::VERSION . '=' . $webVersion;
            } elseif ($source === 'post') {
                $post = [Request::VERSION => $webVersion];
            } elseif ($source === 'cookies') {
                $cookies = [Request::VERSION => $webVersion];
            }
            $content = $this->fetchContentFromLink($url, true, $post, $cookies)['content'];
            self::assertNotEmpty($content);
            $document = new HtmlDocument($content);
            $versionFromContent = $document->documentElement->getAttribute('data-content-version');
            self::assertNotNull($versionFromContent, "Can not find attribute 'data-content-version' in content fetched from $url");
            if ($webVersion === $this->getTestsConfiguration()->getExpectedLastUnstableVersion()) {
                self::assertSame($webVersion, $versionFromContent, 'Expected different version, seems version switching does not work');
            } else {
                self::assertStringStartsWith("$webVersion.", $versionFromContent, 'Expected different version, seems version switching does not work');
            }
            $cachedAtString = $this->fetchHtmlDocumentFromLocalUrl()->documentElement->getAttribute('data-cached-at');
            $cachedAt = new \DateTime($cachedAtString);
            self::assertLessThanOrEqual(new \DateTime(), $cachedAt, 'Expected data-cached-at from presence or past, not future');
            self::assertSame($cachedAtString, $cachedAt->format(\DATE_ATOM), 'Expected data-cached-at in format Atom: ' . \DATE_ATOM);
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

    /**
     * @test
     */
    public function Every_version_like_branch_has_detailed_version_tags(): void
    {
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertFalse(false, 'Nothing to test here');

            return;
        }
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $tags = $this->runCommand('git tag | grep -P "([[:digit:]]+[.]){2}[[:alnum:]]+([.][[:digit:]]+)?" --only-matching');
        self::assertNotEmpty(
            $tags,
            'Some patch-version tags expected for versions: '
            . \implode(',', $webVersions->getAllStableMinorVersions())
        );
        foreach ($webVersions->getAllStableMinorVersions() as $stableVersion) {
            $stableVersionTags = [];
            foreach ($tags as $tag) {
                if (\strpos($tag, $stableVersion) === 0) {
                    $stableVersionTags[] = $tag;
                }
            }
            self::assertNotEmpty($stableVersionTags, "No tags found for $stableVersion, got only " . \print_r($tags, true));
        }
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function Current_version_is_written_into_cookie(): void
    {
        unset($_COOKIE[CookiesService::VERSION]);
        $this->fetchNonCachedContent(null, false /* keep changed globals */);
        self::assertArrayHasKey(CookiesService::VERSION, $_COOKIE, "Missing '" . CookiesService::VERSION . "' in cookie");
        // unstable version is forced by test, it should be stable version by default
        self::assertSame($this->getTestsConfiguration()->getExpectedLastVersion(), $_COOKIE[CookiesService::VERSION]);
    }

    /**
     * @test
     */
    public function Stable_versions_have_valid_asset_links(): void
    {
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertFalse(false, 'Nothing to test here');

            return;
        }
        $webVersions = new WebVersions($configuration = $this->getConfiguration(), $this->createRequest());
        $documentRoot = $configuration->getDirs()->getDocumentRoot();
        $checked = 0;
        foreach ($webVersions->getAllStableMinorVersions() as $stableVersion) {
            $htmlDocument = $this->getHtmlDocument([Request::VERSION => $stableVersion]);
            foreach ($htmlDocument->getElementsByTagName('img') as $image) {
                $checked += $this->Asset_file_exists($image, 'src', $documentRoot);
            }
            foreach ($htmlDocument->getElementsByTagName('link') as $link) {
                $checked += $this->Asset_file_exists($link, 'href', $documentRoot);
            }
            foreach ($htmlDocument->getElementsByTagName('script') as $script) {
                $checked += $this->Asset_file_exists($script, 'src', $documentRoot);
            }
        }
    }

    private function Asset_file_exists(Element $element, string $parameterName, string $masterDocumentRoot): int
    {
        $urlParts = \parse_url($element->getAttribute($parameterName));
        if (!empty($urlParts['host'])) {
            return 0;
        }
        $path = $urlParts['path'];
        self::assertFileExists($masterDocumentRoot . '/' . \ltrim($path, '/'), $element->outerHTML);

        return 1;
    }

    /**
     * @test
     */
    public function I_will_get_content_of_last_stable_version_if_requested_does_not_exists(): void
    {
        $patchVersion = $this->getHtmlDocument([Request::VERSION => '999.9'])->documentElement->getAttribute('data-content-version');
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        self::assertSame($webVersions->getLastStablePatchVersion(), $patchVersion);
    }
}