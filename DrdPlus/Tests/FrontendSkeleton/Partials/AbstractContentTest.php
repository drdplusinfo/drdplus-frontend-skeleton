<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\FrontendSkeleton\Dirs;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\Partials\CurrentMinorVersionProvider;
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;
use Mockery\MockInterface;

abstract class AbstractContentTest extends SkeletonTestCase
{
    use DirsForTestsTrait;

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
     * @param array $get = []
     * @param array $post = []
     * @param array $cookies = []
     * @return string
     */
    protected function getContent(array $get = [], array $post = [], array $cookies = []): string
    {
        $key = $this->createKey($get, $post, $cookies);
        if ((self::$contents[$key] ?? null) === null) {
            $originalGet = $_GET;
            $originalPost = $_POST;
            $originalCookies = $_COOKIE;
            if ($get) {
                $_GET = \array_merge($_GET, $get);
            }
            if ($post) {
                $_POST = \array_merge($_POST, $post);
            }
            if ($cookies) {
                $_COOKIE = \array_merge($_COOKIE, $cookies);
            }
            if ($this->needPassIn()) {
                $this->passIn();
            } elseif ($this->needPassOut()) {
                $this->passOut();
            }
            /** @noinspection PhpUnusedLocalVariableInspection */
            $latestVersion = $this->getTestsConfiguration()->getExpectedLastUnstableVersion();
            \ob_start();
            /** @noinspection PhpIncludeInspection */
            include DRD_PLUS_INDEX_FILE_NAME_TO_TEST;
            self::$contents[$key] = \ob_get_clean();
            $_POST = $originalPost;
            $_GET = $originalGet;
            $_COOKIE = $originalCookies;
            self::assertNotEmpty(
                self::$contents[$key],
                'Nothing has been fetched with GET ' . \var_export($get, true) . ', POST ' . \var_export($post, true)
                . ' and COOKIE ' . \var_export($cookies, true)
                . ' from ' . DRD_PLUS_INDEX_FILE_NAME_TO_TEST
            );
        }

        return self::$contents[$key];
    }

    protected function createKey(array $get, array $post, array $cookies): string
    {
        return \json_encode($get) . '-' . \json_encode($post) . '-' . \json_encode($cookies) . '-' . (int)$this->needPassIn() . (int)$this->needPassOut();
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

    /**
     * @param array $get
     * @param array $post
     * @param array $cookies
     * @return \DrdPlus\FrontendSkeleton\HtmlDocument
     */
    protected function getHtmlDocument(array $get = [], array $post = [], array $cookies = []): \DrdPlus\FrontendSkeleton\HtmlDocument
    {
        $key = $this->createKey($get, $post, $cookies);
        if (empty(self::$htmlDocuments[$key])) {
            self::$htmlDocuments[$key] = new \DrdPlus\FrontendSkeleton\HtmlDocument($this->getContent($get, $post, $cookies));
        }

        return self::$htmlDocuments[$key];
    }

    protected function isSkeletonChecked(): bool
    {
        return $this->isFrontendSkeletonChecked();
    }

    protected function isFrontendSkeletonChecked(): bool
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

    protected function getDefinedPageTitle(): string
    {
        $dirs = $this->createDirs();

        return (new FrontendController($this->createConfiguration($dirs), $this->createHtmlHelper($dirs)))->getPageTitle();
    }

    /**
     * @param Dirs $dirs
     * @param bool $inDevMode
     * @param bool $inForcedProductionMode
     * @param bool $shouldHideCovered
     * @param bool $showIntroductionOnly
     * @return HtmlHelper|\Mockery\MockInterface
     */
    protected function createHtmlHelper(
        Dirs $dirs = null,
        bool $inForcedProductionMode = false,
        bool $inDevMode = false,
        bool $shouldHideCovered = false,
        bool $showIntroductionOnly = false
    ): HtmlHelper
    {
        return new HtmlHelper($dirs ?? $this->createDirs(), $inDevMode, $inForcedProductionMode, $shouldHideCovered, $showIntroductionOnly);
    }

    protected function fetchNonCachedContent(FrontendController $controller = null, bool $backupGlobals = true): string
    {
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalCookies = $_COOKIE;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $controller ?? null;
        $_GET[Cache::CACHE] = Cache::DISABLE;
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getDocumentRoot() . '/index.php';
        $content = \ob_get_clean();
        if ($backupGlobals) {
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_COOKIE = $originalCookies;
        }

        return $content;
    }

    protected function fetchContentFromLink(string $link, bool $withBody, array $post = [], array $cookies = [], array $headers = []): array
    {
        $curl = \curl_init($link);
        \curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($curl, \CURLOPT_CONNECTTIMEOUT, 7);
        if (!$withBody) {
            // to get headers only
            \curl_setopt($curl, \CURLOPT_HEADER, 1);
            \curl_setopt($curl, \CURLOPT_NOBODY, 1);
        }
        \curl_setopt($curl, \CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:58.0) Gecko/20100101 Firefox/58.0'); // to get headers only
        if ($post) {
            \curl_setopt($curl, \CURLOPT_POSTFIELDS, $post);
        }
        if ($cookies) {
            $cookieData = [];
            foreach ($cookies as $name => $value) {
                $cookieData[] = "$name=$value";
            }
            \curl_setopt($curl, \CURLOPT_COOKIE, \implode('; ', $cookieData));
        }
        foreach ($headers as $headerName => $headerValue) {
            \curl_setopt($curl, \CURLOPT_HEADER, "$headerName=$headerValue");
        }
        $content = \curl_exec($curl);
        $responseHttpCode = \curl_getinfo($curl, \CURLINFO_HTTP_CODE);
        $redirectUrl = \curl_getinfo($curl, \CURLINFO_REDIRECT_URL);
        $curlError = \curl_error($curl);
        \curl_close($curl);

        return [
            'responseHttpCode' => $responseHttpCode,
            'redirectUrl' => $redirectUrl,
            'content' => $content,
            'error' => $curlError,
        ];
    }

    protected function runCommand(string $command): array
    {
        \exec("$command 2>&1", $output, $returnCode);
        self::assertSame(0, $returnCode, "Failed command '$command', got output " . \var_export($output, true));

        return $output;
    }

    protected function executeCommand(string $command): string
    {
        $output = $this->runCommand($command);

        return \end($output) ?: '';
    }

    /**
     * @param HtmlDocument $document
     * @return array|Element[]
     */
    protected function getMetaRefreshes(HtmlDocument $document): array
    {
        $metaElements = $document->head->getElementsByTagName('meta');
        $metaRefreshes = [];
        foreach ($metaElements as $metaElement) {
            if ($metaElement->getAttribute('http-equiv') === 'Refresh') {
                $metaRefreshes[] = $metaElement;
            }
        }

        return $metaRefreshes;
    }

    protected function getGitFolderIgnoring(string $dirToCheck): array
    {
        $documentRootEscaped = \escapeshellarg($this->getDocumentRoot());
        $dirToCheckEscaped = \escapeshellarg($dirToCheck);
        $command = "git -C $documentRootEscaped check-ignore $dirToCheckEscaped 2>&1";
        \exec($command, $output, $result);
        if ($result > 1) { // both 0 and 1 are valid success return codes
            throw new \RuntimeException(
                "Can not find out if is vendor dir versioned or not by command '{$command}'"
                . ", got return code '{$result}' and output\n"
                . \implode("\n", $output)
            );
        }

        return ['output' => $output, 'result' => $result];
    }

    protected function createConfiguration(Dirs $dirs = null): Configuration
    {
        return Configuration::createFromYml($dirs ?? $this->createDirs());
    }

    protected function createCurrentVersionProvider(string $currentVersion = null): CurrentMinorVersionProvider
    {
        $currentVersionProvider = $this->mockery(CurrentMinorVersionProvider::class);
        $currentVersionProvider->allows('getCurrentMinorVersion')
            ->andReturn($currentVersion ?? $this->getTestsConfiguration()->getExpectedLastVersion());

        /** @var CurrentMinorVersionProvider $currentVersionProvider */
        return $currentVersionProvider;
    }

    /**
     * @param array $customSettings
     * @return Configuration|MockInterface
     */
    protected function createCustomConfiguration(array $customSettings): Configuration
    {
        $originalConfiguration = $this->createConfiguration();
        $configurationClass = \get_class($originalConfiguration);
        $customConfiguration = new $configurationClass(
            $originalConfiguration->getDirs(),
            \array_replace_recursive($originalConfiguration->getSettings(), $customSettings)
        );

        return $customConfiguration;
    }
}