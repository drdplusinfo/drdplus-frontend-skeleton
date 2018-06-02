<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
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
     * @test
     */
    public function Local_anchors_with_hashes_point_to_existing_ids(): void
    {
        $html = $this->getHtmlDocument();
        $localAnchors = $this->getLocalAnchors();
        if (!$this->getTestsConfiguration()->hasIds()) { // no IDs no local anchors
            self::assertCount(0, $localAnchors, 'No local anchors expected as there are no IDs to make anchors from');

            return;
        }
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
     * @test
     */
    public function All_external_anchors_can_be_reached(): void
    {
        $skippedExternalUrls = [];
        foreach ($this->getExternalAnchors() as $anchor) {
            $originalLink = $anchor->getAttribute('href');
            $link = $this->turnToLocalLink($originalLink);
            if (\in_array($link, self::$checkedExternalAnchors, true)) {
                continue;
            }
            $weAreOffline = false;
            if ($originalLink === $link) { // nothing changed so it is not an drdplus.info link and is still external
                $host = \parse_url($link, \PHP_URL_HOST);
                $weAreOffline = $host !== false
                    && !\filter_var($host, \FILTER_VALIDATE_IP)
                    && \gethostbyname($host) === $host; // instead of IP address we got again the site name
            }
            if ($weAreOffline) {
                $skippedExternalUrls[] = $link;
            } else {
                $curl = \curl_init($link);
                \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 7);
                \curl_setopt($curl, CURLOPT_HEADER, 1);
                \curl_setopt($curl, CURLOPT_NOBODY, 1); // to get headers only
                \curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:58.0) Gecko/20100101 Firefox/58.0'); // to get headers only
                \curl_exec($curl);
                $responseHttpCode = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $redirectUrl = \curl_getinfo($curl, CURLINFO_REDIRECT_URL);
                \curl_close($curl);
                self::assertTrue(
                    $responseHttpCode >= 200 && $responseHttpCode < 300,
                    "Could not reach $link, got response code $responseHttpCode and redirect URL '$redirectUrl'"
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

    /**
     * @return array|Element[]
     */
    protected function getExternalAnchors(): array
    {
        static $externalAnchors = [];
        if (!$externalAnchors) {
            $html = $this->getHtmlDocument();
            /** @var Element $anchor */
            foreach ($html->getElementsByTagName('a') as $anchor) {
                if (\preg_match('~^(http|//)~', $anchor->getAttribute('href'))) {
                    $externalAnchors[] = $anchor;
                }
            }
        }

        return $externalAnchors;
    }

    /**
     * @test
     */
    public function External_anchors_with_hashes_point_to_existing_ids(): void
    {
        $externalAnchorsWithHash = $this->getExternalAnchorsWithHash();
        if (!$this->getTestsConfiguration()->hasExternalAnchorsWithHashes()) {
            self::assertCount(0, $externalAnchorsWithHash);

            return;
        }
        foreach ($externalAnchorsWithHash as $anchor) {
            $link = $anchor->getAttribute('href');
            $link = $this->turnToLocalLink($link);
            $html = $this->getExternalHtmlDocument($link);
            $expectedId = \substr($link, \strpos($link, '#') + 1); // just remove leading #
            /** @var Element $target */
            $target = $html->getElementById($expectedId);
            self::assertNotEmpty(
                $target,
                'No element found by ID ' . $expectedId . ' in a document with URL ' . $link
                . ($link !== $anchor->getAttribute('href') ? ' (originally ' . $anchor->getAttribute('href') . ')' : '')
            );
            self::assertNotRegExp('~(display:\s*none|visibility:\s*hidden)~', (string)$target->getAttribute('style'));
        }
    }

    protected function turnToLocalLink(string $link): string
    {
        return \preg_replace('~https?://((?:[[:alnum:]]+\.)*)drdplus\.info~', 'http://$1drdplus.loc', $link); // turn link into local version
    }

    /**
     * @return array|Element[]
     */
    private function getExternalAnchorsWithHash(): array
    {
        $externalAnchorsWithHash = [];
        foreach ($this->getExternalAnchors() as $anchor) {
            if (\strpos($anchor->getAttribute('href'), '#') > 0) {
                $externalAnchorsWithHash[] = $anchor;
            }
        }

        return $externalAnchorsWithHash;
    }

    private function getExternalHtmlDocument(string $href): HTMLDocument
    {
        $link = \substr($href, 0, \strpos($href, '#') ?: null);
        if ((self::$externalHtmlDocuments[$link] ?? null) === null) {
            $curl = \curl_init($link);
            \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            $cookieName = null;
            if (\strpos($link, 'drdplus.loc') !== false || \strpos($link, 'drdplus.info') !== false) {
                self::assertNotEmpty(
                    \preg_match('~//(?<subDomain>[^.]+([.][^.]+)*)\.drdplus\.~', $link, $matches),
                    "Expected some sub-domain in link $link"
                );
                \curl_setopt($curl, CURLOPT_POSTFIELDS, ['trial' => '1']);
            }
            $content = \curl_exec($curl);
            \curl_close($curl);
            self::assertNotEmpty($content, 'Nothing has been fetched from URL ' . $link);
            self::$externalHtmlDocuments[$link] = @new HTMLDocument($content);
            if (\strpos($link, 'drdplus.loc') !== false || \strpos($link, 'drdplus.info') !== false) {
                self::assertCount(
                    0,
                    self::$externalHtmlDocuments[$link]->getElementsByTagName('form'),
                    'Seems we have not passed ownership check for ' . $href . ' by using cookie of name '
                    . \var_export($cookieName, true)
                );
            }
        }

        return self::$externalHtmlDocuments[$link];
    }

    /**
     * @test
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
     * @test
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
     * @test
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
     * @test
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
        foreach ($calculations as $calculation) {
            self::assertNotEmpty($calculation->id, 'Missing ID for calculation: ' . \trim($calculation->innerHTML));
            self::assertRegExp('~^(Hod na|Hod proti|Výpočet) ~u', $calculation->getAttribute('data-original-id'));
            self::assertRegExp('~^(hod_na|hod_proti|vypocet)_~u', $calculation->id);
        }
    }

    /**
     * @test
     */
    public function Links_to_altar_uses_https(): void
    {
        $linksToAltar = [];
        foreach ($this->getExternalAnchors() as $anchor) {
            $link = $anchor->getAttribute('href');
            if (!\strpos($link, 'altar.cz')) {
                continue;
            }
            $linksToAltar[] = $link;
        }
        if (!$this->getTestsConfiguration()->hasLinksToAltar()) {
            self::assertCount(0, $linksToAltar, 'No link to Altar expected according to tests config');
        }
        foreach ($linksToAltar as $linkToAltar) {
            self::assertStringStartsWith('https', $linkToAltar, "Every link to Altar should be via https: '$linkToAltar'");
        }
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function No_links_point_to_local_hosts(): void
    {
        $urlsWithLocalHosts = [];
        /** @var Element $anchor */
        foreach ($this->getHtmlDocument('', ['mode' => 'prod' /* do not turn links to local */])->getElementsByTagName('a') as $anchor) {
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
}