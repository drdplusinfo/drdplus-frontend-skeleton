<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use Granam\Strict\Object\StrictObject;

class FrontendController extends StrictObject
{
    /** @var string */
    private $googleAnalyticsId;
    /** @var HtmlHelper */
    private $htmlHelper;
    /** @var string */
    private $documentRoot;
    /** @var string */
    private $webRoot;
    /** @var string */
    private $vendorRoot;
    /** @var string */
    private $partsRoot;
    /** @var string */
    private $genericPartsRoot;
    /** @var string */
    private $jsRoot;
    /** @var string */
    private $cssRoot;
    /** @var string */
    private $webName;
    /** @var string */
    private $pageTitle;
    /** @var WebFiles */
    private $webFiles;
    /** @var WebVersions */
    private $webVersions;
    /** @var Request */
    private $request;
    /** @var array */
    private $bodyClasses;
    /** @var bool */
    private $contactsFixed = false;
    /** @var bool */
    private $showHomeButton = true;
    /** @var CacheRoot */
    private $cacheRoot;
    /** @var PageCache */
    private $pageCache;
    /** @var Redirect|null */
    private $redirect;

    public function __construct(
        string $googleAnalyticsId,
        HtmlHelper $htmlHelper,
        string $documentRoot,
        string $webRoot = null,
        string $vendorRoot = null,
        string $partsRoot = null,
        string $genericPartsRoot = null,
        array $bodyClasses = []
    )
    {
        $this->googleAnalyticsId = $googleAnalyticsId;
        $this->documentRoot = $documentRoot;
        $this->webRoot = $webRoot ?? ($documentRoot . '/web');
        $this->vendorRoot = $vendorRoot ?? ($documentRoot . '/vendor');
        $this->partsRoot = $partsRoot ?? ($documentRoot . '/parts');
        $this->genericPartsRoot = $genericPartsRoot ?? (__DIR__ . '/../../parts/frontend-skeleton');
        $this->cssRoot = $documentRoot . '/css';
        $this->jsRoot = $documentRoot . '/js';
        $this->bodyClasses = $bodyClasses;
        $this->htmlHelper = $htmlHelper;
    }

    /**
     * @param Redirect $redirect
     */
    public function setRedirect(Redirect $redirect): void
    {
        $this->redirect = $redirect;
    }

    /**
     * @return Redirect|null
     */
    public function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }

    /**
     * @return string
     */
    public function getGoogleAnalyticsId(): string
    {
        return $this->googleAnalyticsId;
    }

    /**
     * @return string
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    /**
     * @return string
     */
    public function getDirForVersions(): string
    {
        return $this->getDocumentRoot() . '/versions';
    }

    /**
     * @return string
     */
    public function getWebRoot(): string
    {
        return $this->webRoot;
    }

    /**
     * @param string $webRoot
     */
    public function setWebRoot(string $webRoot): void
    {
        $this->webRoot = $webRoot;
    }

    /**
     * @return string
     */
    public function getVendorRoot(): string
    {
        return $this->vendorRoot;
    }

    /**
     * @return string
     */
    public function getPartsRoot(): string
    {
        return $this->partsRoot;
    }

    /**
     * @return string
     */
    public function getGenericPartsRoot(): string
    {
        return $this->genericPartsRoot;
    }

    /**
     * @return string
     */
    public function getJsRoot(): string
    {
        return $this->jsRoot;
    }

    /**
     * @return string
     */
    public function getCssRoot(): string
    {
        return $this->cssRoot;
    }

    public function getCssFiles(): CssFiles
    {
        return new CssFiles($this->getCssRoot());
    }

    public function getJsFiles(): JsFiles
    {
        return new JsFiles($this->getJsRoot());
    }

    public function getWebName(): string
    {
        if ($this->webName === null) {
            if (!\file_exists($this->getDocumentRoot() . '/name.txt')) {
                throw new Exceptions\MissingFileWithPageName("Can not find file '{$this->getDocumentRoot()}/name.txt'");
            }
            $webName = \trim((string)\file_get_contents($this->getDocumentRoot() . '/name.txt'));
            if ($webName === '') {
                throw new Exceptions\FileWithPageNameIsEmpty("File '{$this->getDocumentRoot()}/name.txt' is empty");
            }
            $this->webName = $webName;
        }

        return $this->webName;
    }

    public function getPageTitle(): string
    {
        if ($this->pageTitle === null) {
            $name = $this->getWebName();
            $smiley = \file_exists($this->getDocumentRoot() . '/title_smiley.txt')
                ? \trim(\file_get_contents($this->getDocumentRoot() . '/title_smiley.txt'))
                : '';

            $this->pageTitle = ($smiley !== '')
                ? ($smiley . ' ' . $name)
                : $name;
        }

        return $this->pageTitle;
    }

    public function getContacts(): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getGenericPartsRoot() . '/contacts.php';

        return \ob_get_clean();
    }

    public function getCustomBodyContent(): string
    {
        if (!\file_exists($this->getPartsRoot() . '/custom_body_content.php')) {
            return '';
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        $content = '<div id="customBodyContent">';
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getPartsRoot() . '/custom_body_content.php';
        $content .= \ob_get_clean();
        $content .= '</div>';

        return $content;
    }

    public function getWebContent(): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        $content = '';
        foreach ($this->getWebFiles() as $webFile) {
            if (\preg_match('~\.php$~', $webFile)) {
                \ob_start();
                /** @noinspection PhpIncludeInspection */
                include $webFile;
                $content .= \ob_get_clean();
            } else {
                $content .= \file_get_contents($webFile);
            }
        }

        return $content;
    }

    public function getWebFiles(): WebFiles
    {
        if ($this->webFiles === null) {
            $this->webFiles = new WebFiles($this->getWebRoot());
        }

        return $this->webFiles;
    }

    public function getWebVersions(): WebVersions
    {
        if ($this->webVersions === null) {
            $this->webVersions = new WebVersions($this->getDocumentRoot());
        }

        return $this->webVersions;
    }

    public function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = new Request(new Bot());
        }

        return $this->request;
    }

    public function getBodyClasses(): array
    {
        return $this->bodyClasses;
    }

    public function addBodyClass(string $class): void
    {
        $this->bodyClasses[] = $class;
    }

    public function setContactsFixed(): FrontendController
    {
        $this->contactsFixed = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isContactsFixed(): bool
    {
        return $this->contactsFixed;
    }

    public function hideHomeButton(): FrontendController
    {
        $this->showHomeButton = false;

        return $this;
    }

    public function isShownHomeButton(): bool
    {
        return $this->showHomeButton;
    }

    public function getCacheRoot(): CacheRoot
    {
        if ($this->cacheRoot === null) {
            $this->cacheRoot = new CacheRoot($this->getDocumentRoot());
        }

        return $this->cacheRoot;
    }

    public function getPageCache(): PageCache
    {
        if ($this->pageCache === null) {
            $this->pageCache = new PageCache(
                $this->getCacheRoot(),
                $this->getWebVersions(),
                $this->htmlHelper->isInProduction(),
                $this->getWebRoot()
            );
        }

        return $this->pageCache;
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getWantedVersion(): string
    {
        return $_GET['version'] ?? $_COOKIE['version'] ?? $this->getWebVersions()->getLastUnstableVersion();
    }

    public function getCachedContent(): string
    {
        if ($this->getPageCache()->isCacheValid()) {
            return $this->getPageCache()->getCachedContent();
        }

        return '';
    }
}