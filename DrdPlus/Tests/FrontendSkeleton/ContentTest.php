<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Granam\String\StringTools;
use Gt\Dom\Element;
use Gt\Dom\Node;

class ContentTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function Page_has_title(): void
    {
        $definedPageTitle = $this->getDefinedPageTitle();
        $currentPageTitle = $this->getCurrentPageTitle();
        self::assertNotEmpty($definedPageTitle, 'Page title is not defined');
        self::assertNotEmpty($currentPageTitle, 'Page title is missing on page');
        self::assertSame($definedPageTitle, $currentPageTitle, 'Defined and current page titles should be the same');
    }

    /**
     * @test
     */
    public function Every_plus_after_2d6_is_upper_indexed(): void
    {
        self::assertSame(
            0,
            \preg_match(
                '~.{0,10}2k6\s*(?!<span class="upper-index">\+</span>).{0,20}\+~',
                $this->getContentWithoutIds(),
                $matches
            ),
            \var_export($matches, true)
        );
    }

    private function getContentWithoutIds(): string
    {
        $document = clone $this->getHtmlDocument();
        /** @var Element $body */
        $body = $document->getElementsByTagName('body')[0];
        $this->removeIds($body);

        return $document->saveHTML();
    }

    private function removeIds(Element $element): void
    {
        if ($element->hasAttribute('id')) {
            $element->removeAttribute('id');
        }
        foreach ($element->children as $child) {
            $this->removeIds($child);
        }
    }

    /**
     * @test
     */
    public function Every_registered_trademark_and_trademark_symbols_are_upper_indexed(): void
    {
        self::assertSame(
            0,
            \preg_match(
                '~.{0,10}(?:(?<!<span class="upper-index">)\s*[®™]|[®™]\s*(?!</span>).{0,10})~u',
                $this->getContent(),
                $matches
            ),
            \var_export($matches, true)
        );
    }

    /**
     * @test
     */
    public function I_can_navigate_to_every_heading_by_expected_anchor(): void
    {
        $htmlDocument = $this->getHtmlDocument();
        $totalHeadingsCount = 0;
        for ($tagLevel = 1; $tagLevel <= 6; $tagLevel++) {
            $headings = $htmlDocument->getElementsByTagName('h' . $tagLevel);
            $totalHeadingsCount += \count($headings);
            foreach ($headings as $heading) {
                $id = $heading->id;
                self::assertNotEmpty($id, 'Expected some ID for ' . $heading->outerHTML);
                $anchors = $heading->getElementsByTagName('a');
                self::assertCount(1, $anchors, 'Expected single anchor in ' . $heading->outerHTML);
                $anchor = $anchors->current();
                $href = $anchor->getAttribute('href');
                self::assertNotEmpty($href, 'Expected some href of anchor in ' . $heading->outerHTML);
                self::assertSame('#' . $id, $href, 'Expected anchor pointing to the heading ID');
                $headingText = '';
                foreach ($anchor->childNodes as $childNode) {
                    /** @var Node $childNode */
                    if ($childNode->nodeType === \XML_TEXT_NODE) {
                        $headingText = $childNode->textContent;
                        break;
                    }
                }
                self::assertNotEmpty($headingText, 'Expected some human name for heading ' . $heading->outerHTML);
                $idFromText = HtmlHelper::toId($headingText);
                self::assertSame($id, $idFromText, "Expected different ID as created from '$headingText' heading");
            }
        }
        self::assertGreaterThan(0, $totalHeadingsCount, 'Expected some headings');
    }
}