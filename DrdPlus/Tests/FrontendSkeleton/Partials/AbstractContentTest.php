<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use Gt\Dom\HTMLDocument;

abstract class AbstractContentTest extends SkeletonTestCase
{
    private static $contents = [];
    private static $htmlDocuments = [];
    protected $needPassIn = true;
    protected $needPassOut = false;

    protected function setUp(): void
    {
        if (!\defined('DRD_PLUS_INDEX_FILE_NAME_TO_TEST')) {
            self::markTestSkipped("Missing constant 'DRD_PLUS_INDEX_FILE_NAME_TO_TEST'");
        }
    }

    /**
     * @param string $show = ''
     * @param array $get = []
     * @param array $post = []
     * @return string
     */
    protected function getContent(string $show = '', array $get = [], array $post = []): string
    {
        $key = $this->createKey($show, $get, $post);
        if ((self::$contents[$key] ?? null) === null) {
            if ($show !== '') {
                $_GET['show'] = $show;
            }
            if ($get) {
                $_GET = \array_merge($_GET, $get);
            }
            if ($post) {
                $_POST = \array_merge($_POST, $post);
            }
            if ($this->needPassIn()) {
                $this->passIn();
            } elseif ($this->needPassOut()) {
                $this->passOut();
            }
            $hasCustomVersion = $this->setMasterVersionAsDefault();
            \ob_start();
            /** @noinspection PhpIncludeInspection */
            include DRD_PLUS_INDEX_FILE_NAME_TO_TEST;
            self::$contents[$key] = \ob_get_clean();
            self::assertNotEmpty(self::$contents[$key]);
            unset($_GET['show']);
            $this->unsetMasterVersionAsDefault($hasCustomVersion);
        }

        return self::$contents[$key];
    }

    protected function createKey(string $show, array $get, array $post = []): string
    {
        return "$show-" . \serialize($get) . '-' . \serialize($post) . '-' . (int)$this->needPassIn() . (int)$this->needPassOut();
    }

    /**
     * Intended for overwrite if protected content is accessed
     */
    protected function passIn(): bool
    {
        return true;
    }

    protected function needPassIn(): bool
    {
        return $this->needPassIn;
    }

    /**
     * Intended for overwrite if protected content is accessed
     */
    protected function passOut(): bool
    {
        return true;
    }

    protected function needPassOut(): bool
    {
        return $this->needPassOut;
    }

    protected function getHtmlDocument(string $show = '', array $get = [], array $post = []): \DrdPlus\FrontendSkeleton\HtmlDocument
    {
        $key = $this->createKey($show, $get, $post);
        if (empty(self::$htmlDocuments[$key])) {
            self::$htmlDocuments[$key] = new \DrdPlus\FrontendSkeleton\HtmlDocument($this->getContent($show, $get, $post));
        }

        return self::$htmlDocuments[$key];
    }

    protected function isSkeletonChecked(): bool
    {
        $documentRootRealPath = \realpath($this->getDocumentRoot());
        $frontendSkeletonRealPath = \realpath(__DIR__ . '/../../../..');

        return $documentRootRealPath === $frontendSkeletonRealPath;
    }

    protected function getCurrentPageTitle(HTMLDocument $document = null): string
    {
        $head = ($document ?? $this->getHtmlDocument())->head;
        if (!$head) {
            return '';
        }
        $titles = $head->getElementsByTagName('title');
        if ($titles->count() === 0) {
            return '';
        }
        $titles->rewind();

        return $titles->current()->nodeValue;
    }

    protected function getDocumentRoot(): string
    {
        static $documentRoot;
        if ($documentRoot === null) {
            $documentRoot = \dirname(\DRD_PLUS_INDEX_FILE_NAME_TO_TEST);
        }

        return $documentRoot;
    }

    protected function getVendorRoot(): string
    {
        return $this->getDocumentRoot() . '/vendor';
    }

    protected function getWebFilesRoot(): string
    {
        return $this->getDocumentRoot() . '/web';
    }

    protected function getDefinedPageTitle(): string
    {
        return (new FrontendController('Google Foo', $this->createHtmlHelper(), $this->getDocumentRoot()))->getPageTitle();
    }

    /**
     * @param bool|null $inProductionMode
     * @return HtmlHelper|\Mockery\MockInterface
     */
    protected function createHtmlHelper(bool $inProductionMode = null): HtmlHelper
    {
        $htmlHelper = $this->mockery(HtmlHelper::class);
        if ($inProductionMode !== null) {
            $htmlHelper->shouldReceive('isInProduction')
                ->andReturn($inProductionMode);
        }

        return $htmlHelper;
    }

    protected function fetchNonCachedContent(FrontendController $controller = null): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $controller ?? null;
        $cacheOriginalValue = $_GET[Cache::CACHE] ?? null;
        $_GET[Cache::CACHE] = Cache::DISABLE;
        $hasCustomVersion = $this->setMasterVersionAsDefault();
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getDocumentRoot() . '/index.php';
        $content = \ob_get_clean();
        $_GET[Cache::CACHE] = $cacheOriginalValue;
        $this->unsetMasterVersionAsDefault($hasCustomVersion);

        return $content;
    }

    protected function setMasterVersionAsDefault(): bool
    {
        $hasCustomVersion = ($_GET['version'] ?? null) !== null;
        $_GET['version'] = $_GET['version'] ?? 'master'; // because tests should run against latest unstable version

        return $hasCustomVersion;
    }

    protected function unsetMasterVersionAsDefault(bool $hasCustomVersion): void
    {
        if (!$hasCustomVersion) {
            unset($_GET['version']);
        }
    }

    protected function turnToLocalLink(string $link): string
    {
        return \preg_replace('~https?://((?:[[:alnum:]]+\.)*)drdplus\.info~', 'http://$1drdplus.loc', $link); // turn link into local version
    }
}