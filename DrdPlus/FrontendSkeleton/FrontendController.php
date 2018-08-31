<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use DrdPlus\FrontendSkeleton\Partials\CurrentMinorVersionProvider;
use DrdPlus\FrontendSkeleton\Web\Body;
use DrdPlus\FrontendSkeleton\Web\Content;
use DrdPlus\FrontendSkeleton\Web\Head;
use DrdPlus\FrontendSkeleton\Web\Menu;
use DrdPlus\FrontendSkeleton\Web\WebFiles;
use Granam\Strict\Object\StrictObject;

class FrontendController extends StrictObject implements CurrentMinorVersionProvider
{
    /** @var Configuration */
    private $configuration;
    /** @var HtmlHelper */
    private $htmlHelper;
    /** @var WebFiles */
    private $webFiles;
    /** @var WebVersions */
    private $webVersions;
    /** @var Request */
    private $request;
    /** @var array */
    private $bodyClasses;
    /** @var PageCache */
    protected $pageCache;
    /** @var Redirect|null */
    private $redirect;
    /** @var CookiesService */
    private $cookiesService;
    /** @var Content */
    private $content;
    /** @var Menu */
    private $menu;
    /** @var Head */
    private $head;
    /** @var Body */
    private $body;
    /** @var CssFiles */
    private $cssFiles;

    public function __construct(Configuration $configuration, HtmlHelper $htmlHelper, array $bodyClasses = [])
    {
        $this->configuration = $configuration;
        $this->htmlHelper = $htmlHelper;
        $this->bodyClasses = $bodyClasses;
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

    public function isMenuPositionFixed(): bool
    {
        return $this->getConfiguration()->isMenuPositionFixed();
    }

    public function isShownHomeButton(): bool
    {
        return $this->getConfiguration()->isShowHomeButton();
    }

    public function getCookiesService(): CookiesService
    {
        if ($this->cookiesService === null) {
            $this->cookiesService = new CookiesService();
        }

        return $this->cookiesService;
    }

    public function getCurrentMinorVersion(): string
    {
        $minorVersion = $this->getRequest()->getValue(Request::VERSION);
        if ($minorVersion && $this->getWebVersions()->hasMinorVersion($minorVersion)) {
            return $minorVersion;
        }

        return $this->getConfiguration()->getWebLastStableMinorVersion();
    }

    protected function reloadWebVersions()
    {
        $this->webVersions = null;
        $this->pageCache = null; // as uses web version
    }

    public function isRequestedWebVersionUpdate(): bool
    {
        return $this->getRequest()->getValue(Request::UPDATE) === 'web';
    }

    public function updateWebVersion(): int
    {
        $updatedVersions = 0;
        // sadly we do not know which version has been updated, so we will update all of them
        foreach ($this->getWebVersions()->getAllMinorVersions() as $version) {
            $this->getWebVersions()->update($version);
            $updatedVersions++;
        }

        return $updatedVersions;
    }

    public function persistCurrentVersion(): bool
    {
        return $this->getCookiesService()->setMinorVersionCookie($this->getCurrentMinorVersion());
    }

    public function getContent(): Content
    {
        if ($this->content === null) {
            $this->content = new Content(
                $this->getMenu(),
                $this->getHead(),
                $this->getBody(),
                $this->getPageCache(),
                $this->getHtmlHelper(),
                $this->getWebVersions(),
                $this->getRedirect(),
                $this->getBodyClasses()
            );
        }

        return $this->content;
    }

    protected function getMenu(): Menu
    {
        if ($this->menu === null) {
            $this->menu = new Menu($this->getConfiguration(), $this->getWebVersions(), $this->getRequest());
        }

        return $this->menu;
    }

    protected function getHead(): Head
    {
        if ($this->head === null) {
            $this->head = new Head($this->getConfiguration(), $this->getHtmlHelper(), $this->getCssFiles());
        }

        return $this->head;
    }

    protected function getBody(): Body
    {
        if ($this->body === null) {
            $this->body = new Body($this->getWebFiles());
        }

        return $this->body;
    }

    protected function getCssFiles(): CssFiles
    {
        if ($this->cssFiles === null) {
            $this->cssFiles = new CssFiles($this->getHtmlHelper()->isInProduction(), $this->getConfiguration()->getDirs());
        }

        return $this->cssFiles;
    }

    public function getDirs(): Dirs
    {
        return $this->getConfiguration()->getDirs();
    }

    protected function getPageCache(): PageCache
    {
        if ($this->pageCache === null) {
            $this->pageCache = new PageCache(
                $this->getWebVersions(),
                $this->getDirs(),
                $this->getHtmlHelper()->isInProduction()
            );
        }

        return $this->pageCache;
    }

    protected function getWebFiles(): WebFiles
    {
        if ($this->webFiles === null) {
            $this->webFiles = new WebFiles($this->getDirs(), $this);
        }

        return $this->webFiles;
    }
}