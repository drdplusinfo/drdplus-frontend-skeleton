<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton\Web;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\Redirect;
use DrdPlus\FrontendSkeleton\WebVersions;
use Granam\Strict\Object\StrictObject;

class Content extends StrictObject
{
    /** @var HtmlHelper */
    private $htmlHelper;
    /** @var WebVersions */
    private $webVersions;
    /** @var Head */
    private $head;
    /** @var Menu */
    private $menu;
    /** @var Body */
    private $body;
    /** @var Cache */
    private $cache;
    /** @var Redirect|null */
    private $redirect;

    public function __construct(
        HtmlHelper $htmlHelper,
        WebVersions $webVersions,
        Head $head,
        Menu $menu,
        Body $body,
        Cache $cache,
        ?Redirect $redirect
    )
    {
        $this->htmlHelper = $htmlHelper;
        $this->webVersions = $webVersions;
        $this->head = $head;
        $this->menu = $menu;
        $this->body = $body;
        $this->cache = $cache;
        $this->redirect = $redirect;
    }

    public function __toString()
    {
        return $this->getStringContent();
    }

    public function getStringContent(): string
    {
        $cachedContent = $this->getCachedContent();
        if ($cachedContent !== null) {
            return $this->injectRedirectIfAny($cachedContent); // redirect is NOT cached and has to be injected again and again
        }

        $previousMemoryLimit = \ini_set('memory_limit', '1G');

        $content = $this->composeContent();
        $this->getCache()->saveContentForDebug($content); // for debugging purpose
        $htmlDocument = $this->buildHtmlDocument($content);
        $updatedContent = $htmlDocument->saveHTML();
        $this->getCache()->cacheContent($updatedContent);
        // has to be AFTER cache as we do not want to cache it
        $updatedContent = $this->injectRedirectIfAny($updatedContent);

        if ($previousMemoryLimit !== false) {
            \ini_set('memory_limit', $previousMemoryLimit);
        }

        return $updatedContent;
    }

    protected function buildHtmlDocument(string $content): HtmlDocument
    {
        $htmlDocument = new HtmlDocument($content);
        $this->getHtmlHelper()->prepareSourceCodeLinks($htmlDocument);
        $this->getHtmlHelper()->addIdsToTablesAndHeadings($htmlDocument);
        $this->getHtmlHelper()->replaceDiacriticsFromIds($htmlDocument);
        $this->getHtmlHelper()->replaceDiacriticsFromAnchorHashes($htmlDocument);
        $this->getHtmlHelper()->addAnchorsToIds($htmlDocument);
        $this->getHtmlHelper()->resolveDisplayMode($htmlDocument);
        $this->getHtmlHelper()->markExternalLinksByClass($htmlDocument);
        $this->getHtmlHelper()->externalLinksTargetToBlank($htmlDocument);
        $this->getHtmlHelper()->injectIframesWithRemoteTables($htmlDocument);
        $this->getHtmlHelper()->addVersionHashToAssets($htmlDocument);
        if (!$this->getHtmlHelper()->isInProduction()) {
            $this->getHtmlHelper()->makeExternalDrdPlusLinksLocal($htmlDocument);
        }
        $this->injectCacheId($htmlDocument);

        return $htmlDocument;
    }

    protected function getHtmlHelper(): HtmlHelper
    {
        return $this->htmlHelper;
    }

    protected function injectCacheId(HtmlDocument $htmlDocument): void
    {
        $htmlDocument->documentElement->setAttribute('data-cache-stamp', $this->getCache()->getCacheId());
    }

    protected function composeContent(): string
    {
        $patchVersion = $this->getWebVersions()->getCurrentPatchVersion();
        $now = \date(\DATE_ATOM);
        $head = $this->getHead()->getHeadString();
        $menu = $this->getMenu()->getMenuString();
        $body = $this->getBody()->getBodyString();

        return <<<HTML
<!DOCTYPE html>
<html lang="cs" data-content-version="{$patchVersion}" data-cached-at="{$now}">
<head>
    {$head}
</head>
<body class="container">
    {$menu}
    {$body}
</body>
</html>
HTML;
    }

    protected function getHead(): Head
    {
        return $this->head;
    }

    protected function getMenu(): Menu
    {
        return $this->menu;
    }

    protected function getBody(): Body
    {
        return $this->body;
    }

    protected function getWebVersions(): WebVersions
    {
        return $this->webVersions;
    }

    protected function getCachedContent(): ?string
    {
        if ($this->getCache()->isCacheValid()) {
            return $this->getCache()->getCachedContent();
        }

        return null;
    }

    protected function getCache(): Cache
    {
        return $this->cache;
    }

    protected function injectRedirectIfAny(string $content): string
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

    protected function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }
}