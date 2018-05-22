<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
use Gt\Dom\HTMLDocument;
use PHPUnit\Framework\TestCase;

abstract class AbstractContentTest extends TestCase
{
    private static $content = [];
    private static $htmlDocument = [];

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
        $key = $this->createKey($show, $get);
        if ((self::$content[$key] ?? null) === null) {
            if ($show !== '') {
                $_GET['show'] = $show;
            }
            if ($get) {
                $_GET = \array_merge($_GET, $get);
            }
            $this->passIn();
            \ob_start();
            /** @noinspection PhpIncludeInspection */
            include DRD_PLUS_INDEX_FILE_NAME_TO_TEST;
            self::$content[$key] = \ob_get_clean();
            self::assertNotEmpty(self::$content[$key]);
            unset($_GET['show']);
        }

        return self::$content[$key];
    }

    protected function createKey(string $show, array $get): string
    {
        return "{$this->passIn()}-$show-" . \serialize($get);
    }

    /**
     * Intended for overwrite if protected content is accessed
     */
    protected function passIn(): bool
    {
        return true;
    }

    protected function getHtmlDocument(string $show = '', array $get = []): \DrdPlus\FrontendSkeleton\HtmlDocument
    {
        $key = $this->createKey($show, $get);
        if (empty(self::$htmlDocument[$key])) {
            self::$htmlDocument[$key] = new \DrdPlus\FrontendSkeleton\HtmlDocument($this->getContent($show, $get));
        }

        return self::$htmlDocument[$key];
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