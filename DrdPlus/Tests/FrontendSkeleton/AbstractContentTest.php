<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
use Gt\Dom\HTMLDocument;

abstract class AbstractContentTest extends SkeletonTestCase
{
    private static $contents = [];
    private static $htmlDocuments = [];

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
        if ((self::$contents[$key] ?? null) === null) {
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
            self::$contents[$key] = \ob_get_clean();
            self::assertNotEmpty(self::$contents[$key]);
            unset($_GET['show']);
        }

        return self::$contents[$key];
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
        if (empty(self::$htmlDocuments[$key])) {
            self::$htmlDocuments[$key] = new \DrdPlus\FrontendSkeleton\HtmlDocument($this->getContent($show, $get));
        }

        return self::$htmlDocuments[$key];
    }

    protected function isSkeletonChecked(HTMLDocument $document): bool
    {

        $head = $document->head;
        self::assertNotEmpty($head, 'Document lacks of head');
        $titles = $head->getElementsByTagName('title');
        self::assertGreaterThan(0, \count($titles), 'Head lacks of title');
        $titles->rewind();
        $title = $titles->current();

        return \strpos($title->nodeValue, 'skeleton') !== false;
    }

    protected function getDocumentRoot(): string
    {
        return \dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST);
    }

    protected function getPageTitle(): string
    {
        return (new HtmlHelper($this->getDocumentRoot(), false, false, false, false))->getPageTitle();
    }

}