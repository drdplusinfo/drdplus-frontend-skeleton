<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use DrdPlus\FrontendSkeleton\Partials\CurrentVersionProvider;
use Granam\Strict\Object\StrictObject;

class FrontendController extends StrictObject implements CurrentVersionProvider
{
    /** @var Configuration */
    private $configuration;
    /** @var HtmlHelper */
    private $htmlHelper;
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
    private $menuPositionFixed;
    /** @var bool */
    private $showHomeButton;
    /** @var PageCache */
    protected $pageCache;
    /** @var Redirect|null */
    private $redirect;
    /** @var CookiesService */
    private $cookiesService;

    public function __construct(Configuration $configuration, HtmlHelper $htmlHelper, array $bodyClasses = [])
    {
        $this->configuration = $configuration;
        $this->htmlHelper = $htmlHelper;
        $this->bodyClasses = $bodyClasses;
        $this->menuPositionFixed = $configuration->isMenuPositionFixed();
        $this->showHomeButton = $configuration->isShowHomeButton();
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
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return HtmlHelper
     */
    public function getHtmlHelper(): HtmlHelper
    {
        return $this->htmlHelper;
    }

    /**
     * @return string
     */
    public function getGoogleAnalyticsId(): string
    {
        return $this->configuration->getGoogleAnalyticsId();
    }

    public function getCssFiles(): CssFiles
    {
        return new CssFiles($this->getHtmlHelper()->isInProduction(), $this->getConfiguration()->getDirs());
    }

    public function getJsFiles(): JsFiles
    {
        return new JsFiles($this->getHtmlHelper()->isInProduction(), $this->getConfiguration()->getDirs());
    }

    public function getWebName(): string
    {
        return $this->getConfiguration()->getWebName();
    }

    public function getPageTitle(): string
    {
        if ($this->pageTitle === null) {
            $name = $this->getWebName();
            $smiley = $this->getConfiguration()->getTitleSmiley();
            $this->pageTitle = ($smiley !== '')
                ? ($smiley . ' ' . $name)
                : $name;
        }

        return $this->pageTitle;
    }

    public function getMenu(): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getConfiguration()->getDirs()->getGenericPartsRoot() . '/menu.php';

        return \ob_get_clean();
    }

    public function getWebContent(): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        $content = '';
        foreach ($this->getWebFiles() as $webFile) {
            if (\preg_match('~[.]php$~', $webFile)) {
                \ob_start();
                /** @noinspection PhpIncludeInspection */
                include $webFile;
                $content .= \ob_get_clean();
            } elseif (\preg_match('~[.]md$~', $webFile)) {
                $content .= \Parsedown::instance()->parse(\file_get_contents($webFile));
            } else {
                $content .= \file_get_contents($webFile);
            }
        }

        return $content;
    }

    public function getWebFiles(): WebFiles
    {
        if ($this->webFiles === null) {
            $this->webFiles = new WebFiles($this->getConfiguration()->getDirs(), $this);
        }

        return $this->webFiles;
    }

    public function getWebVersions(): WebVersions
    {
        if ($this->webVersions === null) {
            $this->webVersions = new WebVersions($this->getConfiguration(), $this);
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

    /**
     * @return bool
     */
    public function isMenuPositionFixed(): bool
    {
        return $this->menuPositionFixed;
    }

    public function isShownHomeButton(): bool
    {
        return $this->showHomeButton;
    }

    public function getPageCache(): PageCache
    {
        if ($this->pageCache === null) {
            $this->pageCache = new PageCache($this->getWebVersions(), $this->getConfiguration()->getDirs(), $this->htmlHelper->isInProduction());
        }

        return $this->pageCache;
    }

    public function getCachedContent(): string
    {
        if ($this->getPageCache()->isCacheValid()) {
            return $this->getPageCache()->getCachedContent();
        }

        return '';
    }

    public function getCurrentPatchVersion(): string
    {
        return $this->getWebVersions()->getCurrentPatchVersion();
    }

    public function injectCacheId(HtmlDocument $htmlDocument): void
    {
        $htmlDocument->documentElement->setAttribute('data-cache-stamp', $this->getPageCache()->getCacheId());
    }

    public function injectRedirectIfAny(string $content): string
    {
        if (!$this->getRedirect()) {
            return $content;
        }
        $cachedDocument = new HtmlDocument($content);
        $meta = $cachedDocument->createElement('meta');
        $meta->setAttribute('http-equiv', 'Refresh');
        $meta->setAttribute('content', $this->getRedirect()->getAfterSeconds() . '; url=' . $this->getRedirect()->getTarget());
        $meta->setAttribute('id', 'meta_redirect');
        $cachedDocument->head->appendChild($meta);

        return $cachedDocument->saveHTML();
    }

    public function getCookiesService(): CookiesService
    {
        if ($this->cookiesService === null) {
            $this->cookiesService = new CookiesService();
        }

        return $this->cookiesService;
    }

    public function getCurrentVersion(): string
    {
        return $this->getRequest()->getValue('version') ?? $this->getConfiguration()->getWebLastStableVersion();
    }
}