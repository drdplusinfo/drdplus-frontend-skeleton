<?php
namespace Tests\DrdPlus\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
use Gt\Dom\HTMLDocument;
use PHPUnit\Framework\TestCase;

abstract class AbstractContentTest extends TestCase
{
    protected function setUp()
    {
        if (!\defined('DRD_PLUS_INDEX_FILE_NAME_TO_TEST')) {
            self::markTestSkipped('Missing constant \'DRD_PLUS_INDEX_FILE_NAME_TO_TEST\'');
        }
    }

    /**
     * @param string $show = ''
     * @param array $get = []
     * @return string
     */
    protected function getContent(string $show = '', array $get = []): string
    {
        static $content = [];
        $key = "$show-" . \serialize($get);
        if (($content[$key] ?? null) === null) {
            if ($show !== '') {
                $_GET['show'] = $show;
            }
            if ($get) {
                $_GET = \array_merge($_GET, $get);
            }
            \ob_start();
            /** @noinspection PhpIncludeInspection */
            include DRD_PLUS_INDEX_FILE_NAME_TO_TEST;
            $content[$key] = \ob_get_clean();
            self::assertNotEmpty($content[$key]);
            unset($_GET['show']);
        }

        return $content[$key];
    }

    protected function getHtmlDocument(string $show = '', array $get = []): HTMLDocument
    {
        static $htmlDocument = [];
        $key = "$show-" . \serialize($get);
        if (empty($htmlDocument[$key])) {
            $htmlDocument[$key] = new HTMLDocument($this->getContent($show, $get));
        }

        return $htmlDocument[$key];
    }

    protected function isSkeletonChecked(HTMLDocument $document): bool
    {
        return \strpos($document->head->getElementsByTagName('title')->item(0)->nodeValue, 'skeleton') !== false;
    }

    protected function getDocumentRoot(): string
    {
        return \dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST);
    }

    protected function getPageTitle(): string
    {
        return (new HtmlHelper($this->getDocumentRoot(), false, false, false))->getPageTitle();
    }

}