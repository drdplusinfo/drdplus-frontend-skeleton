<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Granam\String\StringTools;
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;

class AnchorsTest extends AbstractContentTest
{

    private const ID_WITH_ALLOWED_ELEMENTS_ONLY = 'with_allowed_elements_only';

    /** @var HTMLDocument[]|array */
    private static $externalHtmlDocuments;

    /**
     * @test
     */
    public function All_anchors_point_to_syntactically_valid_links(): void
    {
        $invalidAnchors = $this->parseInvalidAnchors($this->getContent());
        self::assertCount(
            0,
            $invalidAnchors,
            'Some anchors from content points to invalid links ' . implode(',', $invalidAnchors)
        );
    }

    /**
     * @param string $content
     * @return array
     */
    protected function parseInvalidAnchors(string $content): array
    {
        \preg_match_all('~(?<invalidAnchors><a[^>]+href="(?:(?![#?]|https?|[.]?/|mailto).)+[^>]+>)~', $content, $matches);

        return $matches['invalidAnchors'];
    }

    /**
     * @test1
     */
    public function Local_anchors_with_hashes_point_to_existing_ids(): void
    {
        $html = $this->getHtmlDocument();
        $localAnchors = $this->getLocalAnchors();
        if (!$this->getTestsConfiguration()->hasLocalLinks()) {
            self::assertCount(
                0,
                $localAnchors,
                'No local anchors expected as tests config says there are no IDs to make anchors from: '
                . "\n" . \implode("\n", \array_map(function (Element $anchor) {
                    return $anchor->getAttribute('href');
                }, $localAnchors))
            );

            return;
        }
        self::assertNotEmpty($localAnchors, 'Some local anchors expected');
        foreach ($this->getLocalAnchors() as $localAnchor) {
            $expectedId = \substr($localAnchor->getAttribute('href'), 1); // just remove leading #
            /** @var Element $target */
            $target = $html->getElementById($expectedId);
            self::assertNotEmpty($target, 'No element found by ID ' . $expectedId);
            foreach ($this->classesAllowingInnerLinksTobeHidden() as $classAllowingInnerLinksTobeHidden) {
                if ($target->classList->contains($classAllowingInnerLinksTobeHidden)) {
                    return;
                }
            }
            self::assertNotContains('hidden', (string)$target->className, "Inner link of ID $expectedId should not be hidden");
            self::assertNotRegExp('~(display:\s*none|visibility:\s*hidden)~', (string)$target->getAttribute('style'));
        }
    }

    protected function classesAllowingInnerLinksTobeHidden(): array
    {
        return [];
    }

    /**
     * @return array|Element[]
     */
    private function getLocalAnchors(): array
    {
        $html = $this->getHtmlDocument();
        $localAnchors = [];
        /** @var Element $anchor */
        foreach ($html->getElementsByTagName('a') as $anchor) {
            if (\strpos($anchor->getAttribute('href'), '#') === 0) {
                $localAnchors[] = $anchor;
            }
        }

        return $localAnchors;
    }

    private static $checkedExternalAnchors = [];

    /**
     * @test1
     */
    public function All_external_anchors_can_be_reached(): void
    {
        $skippedExternalUrls = [];
        foreach ($this->getExternalAnchors() as $originalLink) {
            $link = $this->turnToLocalLink($originalLink);
            if (\in_array($link, self::$checkedExternalAnchors, true)) {
                continue;
            }
            $weAreOffline = $this->isLinkAccessible($originalLink, $link);
            if ($weAreOffline) {
                $skippedExternalUrls[] = $link;
            } else {
                $responseHttpCode = false;
                $redirectUrl = '';
                $error = '';
                $isDrdPlus = $this->isDrdPlusLink($link);
                $tempFileName = 'external_anchor_response_code_' . \md5($link) . '.tmp';
                if (!$isDrdPlus) {
                    $responseHttpCode = (int)$this->getFromCache($tempFileName);
                }
                if (!$responseHttpCode) {
                    [
                        'responseHttpCode' => $responseHttpCode,
                        'redirectUrl' => $redirectUrl,
                        'error' => $error
                    ] = $this->fetchContentFromLink($link, false /* just headers*/);
                    if (!$isDrdPlus && $responseHttpCode >= 200 && $responseHttpCode < 300) {
                        $this->cacheContent((string)$responseHttpCode, $tempFileName, $isDrdPlus, $responseHttpCode);
                    }
                }
                self::assertTrue(
                    $responseHttpCode >= 200 && $responseHttpCode < 300,
                    "Could not reach $link, got response code $responseHttpCode and redirect URL '$redirectUrl' ($error)"
                );
            }
            self::$checkedExternalAnchors[] = $link;
        }
        if ($skippedExternalUrls) {
            self::markTestSkipped(
                'Some external URLs have been skipped as we are probably offline: ' .
                \print_r($skippedExternalUrls, true)
            );
        }
    }

    private function isDrdPlusLink(string $link): bool
    {
        return \strpos($link, 'drdplus.loc') !== false || \strpos($link, 'drdplus.info') !== false;
    }

    private function isLinkAccessible(string $originalLink, string $localizedLink): bool
    {
        if ($originalLink !== $localizedLink) {
            return false; // nothing changed so it is not an drdplus.info link and is still external
        }
        $host = \parse_url($localizedLink, \PHP_URL_HOST);

        return $host !== false
            && !\filter_var($host, \FILTER_VALIDATE_IP)
            && \gethostbyname($host) === $host; // instead of IP address we got again the site name
    }

    private function getFromCache(string $cacheFileBaseName): string
    {
        $tempFile = \sys_get_temp_dir() . '/' . $cacheFileBaseName;
        if (!\file_exists($tempFile)) {
            return '';
        }
        if (\filemtime($tempFile) > (\time() - 3600)) {
            return \file_get_contents($tempFile);
        }
        \unlink($tempFile);

        return '';
    }

    private function cacheContent(string $content, string $cacheFileBaseName, bool $isDrdPlus, int $responseCode): bool
    {
        if ($isDrdPlus || $responseCode < 200 || $responseCode >= 300) {
            return false;
        }
        self::assertNotSame('', $content, 'Given content to cache is empty');
        $tempDir = \sys_get_temp_dir() . '/frontend-skeleton';
        self::assertTrue(
            \file_exists($tempDir) || \mkdir($tempDir, 0775) || \is_dir($tempDir),
            "Can not create dir for cached test content $tempDir"
        );
        $tempFile = $tempDir . '/' . $cacheFileBaseName;
        self::assertNotEmpty(\file_put_contents($tempFile, $content), "Nothing has been saved to $tempFile");

        return true;
    }

    /**
     * @return array|string[]
     */
    protected function getExternalAnchors(): array
    {
        static $externalAnchors = [];
        if (!$externalAnchors) {
            $html = $this->getHtmlDocument();
            /** @var Element $anchor */
            foreach ($html->getElementsByTagName('a') as $anchor) {
                $link = $anchor->getAttribute('href');
                if (\preg_match('~^(http|//)~', $link)) {
                    $externalAnchors[] = $link;
                }
            }
        }

        return $externalAnchors;
    }

    /**
     * @test1
     */
    public function External_anchors_with_hashes_point_to_existing_ids(): void
    {
        $externalAnchorsWithHash = $this->getExternalAnchorsWithHash();
        if (!$this->getTestsConfiguration()->hasExternalAnchorsWithHashes()) {
            self::assertCount(
                0,
                $externalAnchorsWithHash,
                'No external anchors expected according to tests config'
            );

            return;
        }
        self::assertNotEmpty($externalAnchorsWithHash, 'Some external anchors expected');
        $skippedExternalUrls = [];
        foreach ($externalAnchorsWithHash as $originalLink) {
            $link = $this->turnToLocalLink($originalLink);
            if ($this->isLinkAccessible($originalLink, $link)) {
                $skippedExternalUrls[] = $link;
                continue;
            }
            $html = $this->getExternalHtmlDocument($link);
            $expectedId = \substr($link, \strpos($link, '#') + 1); // just remove leading #
            /** @var Element $target */
            $target = $html->getElementById($expectedId);
            self::assertNotEmpty(
                $target,
                'No element found by ID ' . $expectedId . ' in a document with URL ' . $link
                . ($link !== $originalLink ? ' (originally ' . $originalLink . ')' : '')
            );
            self::assertNotRegExp('~(display:\s*none|visibility:\s*hidden)~', (string)$target->getAttribute('style'));
        }
        if ($skippedExternalUrls) {
            self::markTestSkipped(
                'Some external URLs have been skipped as we are probably offline: ' .
                \print_r($skippedExternalUrls, true)
            );
        }
    }

    /**
     * @return array|string[]
     */
    private function getExternalAnchorsWithHash(): array
    {
        $externalAnchorsWithHash = [];
        foreach ($this->getExternalAnchors() as $anchor) {
            if (\strpos($anchor, '#') > 0) {
                $externalAnchorsWithHash[] = $anchor;
            }
        }

        return $externalAnchorsWithHash;
    }

    private function getExternalHtmlDocument(string $href): HTMLDocument
    {
        $link = \substr($href, 0, \strpos($href, '#') ?: null);
        if ((self::$externalHtmlDocuments[$link] ?? null) === null) {
            $isDrdPlus = false;
            if ($this->isDrdPlusLink($link)) {
                self::assertNotEmpty(
                    \preg_match('~//(?<subDomain>[^.]+([.][^.]+)*)\.drdplus\.~', $link),
                    "Expected some sub-domain in link $link"
                );
                $isDrdPlus = true;
            }
            $content = '';
            $tempFileName = 'external_anchor_content_' . \md5($link) . '.tmp';
            if (!$isDrdPlus) {
                $content = $this->getFromCache($tempFileName);
            }
            if (!$content) {
                [
                    'responseHttpCode' => $responseHttpCode,
                    'redirectUrl' => $redirectUrl,
                    'error' => $error,
                    'content' => $content
                ] = $this->fetchContentFromLink($link, true /* fetch body */, $this->getPostDataToFetchContent($isDrdPlus));
                self::assertTrue(
                    $responseHttpCode >= 200 && $responseHttpCode < 300,
                    "Could not reach $link, got response code $responseHttpCode and redirect URL '$redirectUrl' ($error)"
                );
                self::assertNotEmpty($content, 'Nothing has been fetched from URL ' . $link);
                $this->cacheContent($content, $tempFileName, $isDrdPlus, $responseHttpCode);
            }
            self::$externalHtmlDocuments[$link] = @new HTMLDocument($content);
            if ($isDrdPlus) {
                self::assertCount(
                    0,
                    self::$externalHtmlDocuments[$link]->getElementsByTagName('form'),
                    'Seems we have not passed ownership check for ' . $href
                );
            }
        }

        return self::$externalHtmlDocuments[$link];
    }

    protected function getPostDataToFetchContent(/** @noinspection PhpUnusedParameterInspection */
        bool $isDrdPlus): array
    {
        return [];
    }

    /**
     * @test1
     */
    public function Anchor_to_ID_self_is_not_created_if_contains_anchor_element(): void
    {
        $document = $this->getHtmlDocument();
        $noAnchorsForMe = $document->getElementById(StringTools::toConstantLikeValue('no-anchor-for-me'));
        if (!$noAnchorsForMe && !$this->isSkeletonChecked()) {
            self::assertFalse(false, 'Nothing to test here');

            return;
        }
        self::assertNotEmpty($noAnchorsForMe, "Missing testing element with ID 'no-anchor-for-me'");
        $links = $noAnchorsForMe->getElementsByTagName('a');
        self::assertNotEmpty($links);
        /** @var \DOMElement $noAnchorsForMe */
        $idLink = '#' . $noAnchorsForMe->getAttribute('id');
        /** @var \DOMElement $link */
        foreach ($links as $link) {
            self::assertNotSame($idLink, $link->getAttribute('href'), "No anchor pointing to ID self expected: $idLink");
        }
    }

    /**
     * @test1
     */
    public function Original_ids_do_not_have_links_to_self(): void
    {
        $document = $this->getHtmlDocument();
        $originalIds = $document->getElementsByClassName(HtmlHelper::INVISIBLE_ID_CLASS);
        if (!$this->getTestsConfiguration()->hasIds()) {
            self::assertCount(
                0,
                $originalIds,
                'No original IDs, identified by CSS class ' . HtmlHelper::INVISIBLE_ID_CLASS . ' expected, got '
                . \implode("\n", \array_map(function (Element $element) {
                    return $element->outerHTML;
                }, $this->collectionToArray($originalIds)))
            );

            return;
        }
        self::assertNotEmpty($originalIds);
        foreach ($originalIds as $originalId) {
            self::assertSame('', $originalId->innerHTML);
        }
    }

    protected function collectionToArray(\Iterator $collection): array
    {
        $array = [];
        foreach ($collection as $item) {
            $array[] = $item;
        }

        return $array;
    }

    /**
     * @test1
     */
    public function Only_allowed_elements_are_moved_into_injected_link(): void
    {
        $document = $this->getHtmlDocument();
        $withAllowedElementsOnly = $document->getElementById(self::ID_WITH_ALLOWED_ELEMENTS_ONLY);
        if (!$withAllowedElementsOnly && !$this->isSkeletonChecked()) {
            self::assertFalse(false, 'Nothing to test here');

            return;
        }
        self::assertNotEmpty(
            $withAllowedElementsOnly,
            'Missing testing HTML element with ID ' . self::ID_WITH_ALLOWED_ELEMENTS_ONLY
        );
        $anchors = $withAllowedElementsOnly->getElementsByTagName('a');
        self::assertCount(1, $anchors);
        $anchor = $anchors->item(0);
        self::assertNotNull($anchor);
        self::assertSame('#' . self::ID_WITH_ALLOWED_ELEMENTS_ONLY, $anchor->getAttribute('href'));
        foreach ($anchor->childNodes as $childNode) {
            self::assertContains($childNode->nodeName, ['#text', 'span', 'b', 'strong', 'i']);
        }
    }

    /**
     * @test1
     */
    public function I_can_navigate_to_every_calculation_as_it_has_its_id_with_anchor(): void
    {
        $document = $this->getHtmlDocument();
        $calculations = $document->getElementsByClassName(HtmlHelper::CALCULATION_CLASS);
        if (\count($calculations) === 0 && !$this->isSkeletonChecked()) {
            self::assertFalse(false, 'No calculations in current document');

            return;
        }
        self::assertNotEmpty($calculations);
        $allowedCalculationIdPrefixes = $this->getTestsConfiguration()->getAllowedCalculationIdPrefixes();
        $allowedCalculationIdPrefixesRegexp = $this->toRegexpOr($allowedCalculationIdPrefixes);
        $allowedCalculationIdConstantLikePrefixes = \array_map(function (string $allowedPrefix) {
            return StringTools::toConstantLikeValue($allowedPrefix);
        }, $allowedCalculationIdPrefixes);
        $allowedCalculationIdConstantLikePrefixesRegexp = $this->toRegexpOr($allowedCalculationIdConstantLikePrefixes);
        foreach ($calculations as $calculation) {
            self::assertNotEmpty($calculation->id, 'Missing ID for calculation: ' . \trim($calculation->innerHTML));
            self::assertRegExp("~^($allowedCalculationIdPrefixesRegexp) ~u", $calculation->getAttribute('data-original-id'));
            self::assertRegExp("~^($allowedCalculationIdConstantLikePrefixesRegexp)_~u", $calculation->id);
        }
    }

    private function toRegexpOr(array $values, string $regexpDelimiter = '~'): string
    {
        $escaped = [];
        foreach ($values as $value) {
            $escaped[] = \preg_quote($value, $regexpDelimiter);
        }

        return \implode('|', $escaped);
    }

    /**
     * @test1
     */
    public function Calculation_does_not_have_another_calculation_inside(): void
    {
        $document = $this->getHtmlDocument();
        $calculations = $document->getElementsByClassName(HtmlHelper::CALCULATION_CLASS);
        if (\count($calculations) === 0 && !$this->isSkeletonChecked()) {
            self::assertFalse(false, 'No calculations in current document');

            return;
        }
        self::assertNotEmpty($calculations);
        foreach ($calculations as $calculation) {
            foreach ($calculation->children as $child) {
                $innerCalculations = $child->getElementsByClassName(HtmlHelper::CALCULATION_CLASS);
                self::assertCount(
                    0,
                    $innerCalculations,
                    'Calculation should not has another calculation inside: ' . $calculation->outerHTML
                );
            }
        }
    }

    /**
     * @test1
     */
    public function Links_to_altar_uses_https(): void
    {
        $linksToAltar = [];
        foreach ($this->getExternalAnchors() as $link) {
            if (!\strpos($link, 'altar.cz')) {
                continue;
            }
            $linksToAltar[] = $link;
        }
        if (!$this->getTestsConfiguration()->hasLinksToAltar()) {
            self::assertCount(0, $linksToAltar, 'No link to Altar expected according to tests config');

            return;
        }
        self::assertNotEmpty($linksToAltar, 'Expected some links to Altar');
        foreach ($linksToAltar as $linkToAltar) {
            self::assertStringStartsWith('https', $linkToAltar, "Every link to Altar should be via https: '$linkToAltar'");
        }
    }

    /**
     * @test1
     * @backupGlobals enabled
     */
    public function No_links_point_to_local_hosts(): void
    {
        $urlsWithLocalHosts = [];
        /** @var Element $anchor */
        foreach ($this->getHtmlDocument(['mode' => 'prod' /* do not turn links to local */])->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
            self::assertNotEmpty($href);
            $parsedUrl = \parse_url($href);
            $hostname = $parsedUrl['host'] ?? null;
            if ($hostname === null) { // local link with anchor or query only
                continue;
            }
            if (\preg_match('~[.]loc#~', $hostname) || \gethostbyname($hostname) === '127.0.0.1') {
                $urlsWithLocalHosts[] = $anchor->outerHTML;
            }
        }
        self::assertCount(0, $urlsWithLocalHosts, "There are forgotten local URLs \n" . \implode(",\n", $urlsWithLocalHosts));
    }

    /**
     * @test1
     */
    public function Buttons_should_not_have_links_inside(): void
    {
        $buttons = $this->getHtmlDocument()->getElementsByTagName('button');
        if ($buttons->count() === 0 && !$this->isSkeletonChecked()) {
            self::assertCount(0, $buttons, 'Simply no buttons');

            return;
        }
        self::assertNotEmpty($buttons, 'Some buttons expected in a skeleton to test');
        foreach ($buttons as $button) {
            $buttonAnchors = $button->getElementsByTagName('a');
            self::assertCount(0, $buttonAnchors, 'No anchors expected in button: ' . $button->outerHTML);
        }
    }
}