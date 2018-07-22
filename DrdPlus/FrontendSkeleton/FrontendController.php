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
    /** @var Dirs */
    private $dirs;
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
    /** @var PageCache */
    protected $pageCache;
    /** @var Redirect|null */
    private $redirect;

    public function __construct(string $googleAnalyticsId, HtmlHelper $htmlHelper, Dirs $dirs, array $bodyClasses = [])
    {
        $this->googleAnalyticsId = $googleAnalyticsId;
        $this->dirs = $dirs;
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
     * @return Dirs
     */
    public function getDirs(): Dirs
    {
        return $this->dirs;
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
        return $this->googleAnalyticsId;
    }

    public function getCssFiles(): CssFiles
    {
        return new CssFiles($this->dirs->getCssRoot());
    }

    public function getJsFiles(): JsFiles
    {
        return new JsFiles($this->dirs->getJsRoot());
    }

    public function getWebName(): string
    {
        if ($this->webName === null) {
            if (!\file_exists($this->dirs->getDocumentRoot() . '/name.txt')) {
                throw new Exceptions\MissingFileWithPageName("Can not find file '{$this->dirs->getDocumentRoot()}/name.txt'");
            }
            $webName = \trim((string)\file_get_contents($this->dirs->getDocumentRoot() . '/name.txt'));
            if ($webName === '') {
                throw new Exceptions\FileWithPageNameIsEmpty("File '{$this->dirs->getDocumentRoot()}/name.txt' is empty");
            }
            $this->webName = $webName;
        }

        return $this->webName;
    }

    public function getPageTitle(): string
    {
        if ($this->pageTitle === null) {
            $name = $this->getWebName();
            $smiley = \file_exists($this->dirs->getDocumentRoot() . '/title_smiley.txt')
                ? \trim(\file_get_contents($this->dirs->getDocumentRoot() . '/title_smiley.txt'))
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
        include $this->dirs->getGenericPartsRoot() . '/contacts.php';

        return \ob_get_clean();
    }

    public function getCustomBodyContent(): string
    {
        if (!\file_exists($this->dirs->getPartsRoot() . '/custom_body_content.php')) {
            return '';
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        $content = '<div id="customBodyContent">';
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->dirs->getPartsRoot() . '/custom_body_content.php';
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
            $this->webFiles = new WebFiles($this->dirs->getWebRoot());
        }

        return $this->webFiles;
    }

    public function getWebVersions(): WebVersions
    {
        if ($this->webVersions === null) {
            $this->webVersions = new WebVersions($this->dirs);
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

    public function getPageCache(): PageCache
    {
        if ($this->pageCache === null) {
            $this->pageCache = new PageCache($this->getWebVersions(), $this->dirs, $this->htmlHelper->isInProduction());
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

    public function getCurrentVersion(): string
    {
        return $this->getWebVersions()->getCurrentVersion();
    }

    public function getCurrentPatchVersion(): string
    {
        return $this->getWebVersions()->getCurrentPatchVersion();
    }

    public function injectCacheId(HtmlDocument $htmlDocument)
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
}