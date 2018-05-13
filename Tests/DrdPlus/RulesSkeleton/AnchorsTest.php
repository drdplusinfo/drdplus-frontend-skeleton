<?php
namespace Tests\DrdPlus\RulesSkeleton;

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
        $invalidAnchors = $this->parseInvalidAnchors($this->getOwnershipConfirmationContent());
        self::assertCount(
            0,
            $invalidAnchors,
            'Some anchors from ownership confirmation points to invalid links ' . implode(',', $invalidAnchors)
        );
        $invalidAnchors = $this->parseInvalidAnchors($this->getRulesContent());
        self::assertCount(
            0,
            $invalidAnchors,
            'Some anchors from rules points to invalid links ' . implode(',', $invalidAnchors)
        );
    }

    /**
     * @param string $content
     * @return array
     */
    private function parseInvalidAnchors(string $content): array
    {
        \preg_match_all('~(?<invalidAnchors><a[^>]+href="(?:(?![#?]|https?|[.]?/|mailto).)+[^>]+>)~', $content, $matches);

        return $matches['invalidAnchors'];
    }

    /**
     * @test
     */
    public function Local_anchors_with_hashes_point_to_existing_ids(): void
    {
        $html = $this->getRulesHtmlDocument();
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
        $html = $this->getRulesHtmlDocument();
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
        foreach ($this->getExternalAnchors() as $anchor) {
            $link = $anchor->getAttribute('href');
            if (\in_array($link, self::$checkedExternalAnchors, true)) {
                continue;
            }
            $curl = curl_init($link);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            curl_setopt($curl, CURLOPT_NOBODY, 1); // to get headers only
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:58.0) Gecko/20100101 Firefox/58.0'); // to get headers only
            curl_exec($curl);
            $responseHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $redirectUrl = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
            curl_close($curl);
            self::assertTrue(
                $responseHttpCode >= 200 && $responseHttpCode < 300,
                "Could not reach $link, got response code $responseHttpCode and redirect URL '$redirectUrl'"
            );
            $checkedExternalAnchors[] = $link;
        }
    }

    /**
     * @return array|Element[]
     */
    private function getExternalAnchors(): array
    {
        $html = $this->getRulesHtmlDocument();
        $externalAnchors = [];
        /** @var Element $anchor */
        foreach ($html->getElementsByTagName('a') as $anchor) {
            if (\preg_match('~^(http|//)~', $anchor->getAttribute('href'))) {
                $externalAnchors[] = $anchor;
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
        if (\defined('NO_EXTERNAL_ANCHORS_WITH_HASH_EXPECTED') && NO_EXTERNAL_ANCHORS_WITH_HASH_EXPECTED) {
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

    private function turnToLocalLink(string $link): string
    {
        if (\strpos($link, 'drdplus.info') === false) {
            return $link;
        }

        return \str_replace(['drdplus.info', 'https'], ['drdplus.loc', 'http'], $link); // turn link into local version
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
            $curl = curl_init($link);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            $cookieName = null;
            if (\strpos($link, 'drdplus.loc') !== false || \strpos($link, 'drdplus.info') !== false) {
                self::assertNotEmpty(
                    \preg_match('~//(?<subDomain>[^.]+([.][^.]+)*)\.drdplus\.~', $link, $matches),
                    "Expected some sub-domain in link $link"
                );
                $cookieName = $this->getCookieNameForOwnershipConfirmation($matches['subDomain']);
                curl_setopt($curl, CURLOPT_COOKIE, $cookieName . '=1');
            }
            $content = curl_exec($curl);
            curl_close($curl);
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
        $document = $this->getRulesHtmlDocument();
        $noAnchorsForMe = $document->getElementById(StringTools::toConstant('no-anchor-for-me'));
        if (!$noAnchorsForMe && !$this->checkingSkeleton($document)
        ) {
            self::assertFalse(false, 'Nothing to test here');

            return;
        }
        self::assertNotEmpty($noAnchorsForMe);
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
        $document = $this->getRulesHtmlDocument();
        $originalIds = $document->getElementsByClassName(HtmlHelper::INVISIBLE_ID_CLASS);
        self::assertNotEmpty($originalIds);
        foreach ($originalIds as $originalId) {
            self::assertSame('', $originalId->innerHTML);
        }
    }

    /**
     * @test
     */
    public function Only_allowed_elements_are_moved_into_injected_link(): void
    {
        $document = $this->getRulesHtmlDocument();
        $withAllowedElementsOnly = $document->getElementById(self::ID_WITH_ALLOWED_ELEMENTS_ONLY);
        if (!$withAllowedElementsOnly && !$this->checkingSkeleton($document)) {
            self::assertFalse(false, 'Nothing to test here');

            return;
        }
        self::assertNotEmpty($withAllowedElementsOnly);
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
        $document = $this->getRulesHtmlDocument();
        $calculations = $document->getElementsByClassName(HtmlHelper::CALCULATION_CLASS);
        if (!$this->checkingSkeleton($document) && \count($calculations) === 0) {
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
        foreach ($this->getExternalAnchors() as $anchor) {
            $link = $anchor->getAttribute('href');
            if (!\strpos($link, 'altar.cz')) {
                continue;
            }
            self::assertStringStartsWith('https', $link, "Every link to Altar should be via https: '$link'");
        }
    }

    /**
     * @test
     */
    public function Links_to_vukogvazd_uses_https(): void
    {
        $linksToVukogvazd = [];
        foreach ($this->getExternalAnchors() as $anchor) {
            $link = $anchor->getAttribute('href');
            if (\strpos($link, 'vukogvazd.cz')) {
                $linksToVukogvazd[] = $link;
            }
        }
        if (\count($linksToVukogvazd) === 0) {
            self::assertFalse(false, 'No links to Vukogvazd have been found');
        } else {
            foreach ($linksToVukogvazd as $linkToVukogvazd) {
                self::assertStringStartsWith('https', $linkToVukogvazd, "Every link to vukogvazd should be via https: '$linkToVukogvazd'");
            }
        }
    }

    /**
     * @test
     */
    public function Character_sheet_comes_from_drdplus_info(): void
    {
        $linksToCharacterSheet = [];
        foreach ($this->getExternalAnchors() as $anchor) {
            $link = $anchor->getAttribute('href');
            $link = $this->turnToLocalLink($link);
            if (\strpos($link, 'charakternik.pdf')) {
                $linksToCharacterSheet[] = $link;
            }
        }
        if (((\defined('JUST_TEXT_TESTING') && JUST_TEXT_TESTING)
                || (\defined('NOT_FOR_PLAYERS') && NOT_FOR_PLAYERS)
            )
            && \count($linksToCharacterSheet) === 0
        ) {
            self::assertFalse(false, 'No links to PDF character sheet have been found');

            return;
        }
        self::assertGreaterThan(0, \count($linksToCharacterSheet), 'PDF character sheet is missing');
        $expectedOriginalLink = 'https://www.drdplus.info/pdf/charakternik.pdf';
        $expectedLink = $this->turnToLocalLink($expectedOriginalLink);
        foreach ($linksToCharacterSheet as $linkToCharacterSheet) {
            self::assertSame(
                $expectedLink,
                $linkToCharacterSheet,
                "Every link to PDF character sheet should lead to $expectedOriginalLink"
            );
        }
    }

    /**
     * @test
     */
    public function Journal_comes_from_drdplus_info(): void
    {
        $linksToJournal = [];
        foreach ($this->getExternalAnchors() as $anchor) {
            $link = $anchor->getAttribute('href');
            $link = $this->turnToLocalLink($link);
            if (\preg_match('~/denik_\w+\.pdf$~', $link)) {
                $linksToJournal[] = $link;
            }
        }
        if (((\defined('JUST_TEXT_TESTING') && JUST_TEXT_TESTING)
                || (\defined('NOT_FOR_PLAYERS') && NOT_FOR_PLAYERS)
            )
            || \count($linksToJournal) === 0
        ) {
            self::assertFalse(false, 'No links to PDF journal have been found');

            return;
        }
        self::assertGreaterThan(0, \count($linksToJournal), 'PDF journals are missing');
        if (\defined('WITHOUT_SPECIFIC_JOURNAL') && WITHOUT_SPECIFIC_JOURNAL) {
            foreach ($linksToJournal as $linkToJournal) {
                self::assertRegExp(
                    '~^http://www.drdplus[.]loc/pdf/deniky/denik_\w+[.]pdf$~',
                    $linkToJournal,
                    'Every link to PDF journal should lead to https://www.drdplus.info/pdf/deniky/denik_foo.pdf'
                );
            }

            return;
        }
        $expectedOriginalLink = $this->getExpectedLinkToJournal();
        $expectedLink = $this->turnToLocalLink($expectedOriginalLink);
        foreach ($linksToJournal as $linkToJournal) {
            self::assertSame(
                $expectedLink,
                $linkToJournal,
                "Every link to PDF journal should lead to $expectedOriginalLink"
            );
        }
    }

    private function getExpectedLinkToJournal(): string
    {
        return 'https://www.drdplus.info/pdf/deniky/denik_' . StringTools::toConstant($this->getProfessionName()) . '.pdf';
    }

    private function getProfessionName(): string
    {
        $pageTitle = $this->getPageTitle();
        self::assertSame(
            1,
            \preg_match('~\s(?<lastWord>\w+)$~u', $pageTitle, $matches),
            "No last word found in '$pageTitle'"
        );
        $lastWord = $matches['lastWord'];

        return \rtrim($lastWord, 'aeiouy');
    }
}