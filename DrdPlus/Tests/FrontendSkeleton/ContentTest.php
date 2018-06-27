<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Granam\String\StringTools;
use Gt\Dom\Element;

class ContentTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function Page_has_title(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/name.txt', 'Expected file name.txt with page title is missing');
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
    public function I_get_pages_with_custom_body_content(): void
    {
        $customBodyContentFile = $this->getPartsRoot() . '/custom_body_content.php';
        if (!$this->getTestsConfiguration()->hasCustomBodyContent()) {
            self::assertFileNotExists($customBodyContentFile, "Does not expected {$customBodyContentFile} to exists according to tests config");

            return;
        }
        $customBodyContentId = StringTools::camelCaseToSnakeCase('customBodyContent');
        self::assertFileExists($customBodyContentFile, "Expected {$customBodyContentFile} to exists according to tests config");
        $customBodyContent = $this->getHtmlDocument()->getElementById($customBodyContentId);
        self::assertNotEmpty($customBodyContent, "Custom body content element has not been found by ID '$customBodyContentId'");
        self::assertInstanceOf(Element::class, $customBodyContent);
        /** @var Element $customBodyContent */
        self::assertNotEmpty($customBodyContent->innerHTML, "Content of '$customBodyContentId' is empty");
    }

    protected function getPartsRoot(): string
    {
        return $this->getDocumentRoot() . '/parts';
    }
}