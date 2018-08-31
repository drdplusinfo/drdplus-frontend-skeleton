<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton\Web;

use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\PageCache;
use DrdPlus\FrontendSkeleton\Partials\CurrentPatchVersionProvider;
use DrdPlus\FrontendSkeleton\Redirect;
use Granam\Strict\Object\StrictObject;

class Content extends StrictObject
{
    /** @var Menu */
    private $menu;
    /** @var PageCache */
    private $pageCache;
    /** @var HtmlHelper */
    private $htmlHelper;
    /** @var CurrentPatchVersionProvider */
    private $currentPatchVersionProvider;
    /** @var Redirect|null */
    private $redirect;
    /** @var array */
    private $bodyClasses;
    /** @var Head */
    private $head;
    /**
     * @var Body
     */
    private $body;

    public function __construct(
        Menu $menu,
        Head $head,
        Body $body,
        PageCache $pageCache,
        HtmlHelper $htmlHelper,
        CurrentPatchVersionProvider $currentPatchVersionProvider,
        ?Redirect $redirect,
        array $bodyClasses = []
    )
    {
        $this->menu = $menu;
        $this->head = $head;
        $this->body = $body;
        $this->pageCache = $pageCache;
        $this->htmlHelper = $htmlHelper;
        $this->currentPatchVersionProvider = $currentPatchVersionProvider;
        $this->redirect = $redirect;
        $this->bodyClasses = $bodyClasses;
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
        $this->getPageCache()->saveContentForDebug($content); // for debugging purpose
        $htmlDocument = $this->buildHtmlDocument($content);
        $updatedContent = $htmlDocument->saveHTML();
        $this->getPageCache()->cacheContent($updatedContent);
        // has to be AFTER cache as we do not want to cache it
        $updatedContent = $this->injectRedirectIfAny($updatedContent);

        if ($previousMemoryLimit !== false) {
            \ini_set('memory_limit', $previousMemoryLimit);
        }

        return $updatedContent;
    }

    private function buildHtmlDocument(string $content): HtmlDocument
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

    private function getHtmlHelper(): HtmlHelper
    {
        return $this->htmlHelper;
    }

    private function injectCacheId(HtmlDocument $htmlDocument): void
    {
        $htmlDocument->documentElement->setAttribute('data-cache-stamp', $this->getPageCache()->getCacheId());
    }

    private function composeContent(): string
    {
        $bodyClasses = \implode(' ', $this->bodyClasses);
        $now = \date(\DATE_ATOM);

        return <<<HTML
<!DOCTYPE html>
<html lang="cs" data-content-version="{$this->currentPatchVersionProvider->getCurrentPatchVersion()}" data-cached-at="{$now}">
<head>
    {$this->getHead()->getHeadString()}
</head>
<body class="container {$bodyClasses}">
  <div class="background-image"></div>
    {$this->getMenu()->getMenuString()}
    {$this->getBody()->getBodyString()}
</body>
</html>
HTML;
    }

    public function getMenu(): Menu
    {
        return $this->menu;
    }

    private function getCachedContent(): ?string
    {
        if ($this->getPageCache()->isCacheValid()) {
            return $this->getPageCache()->getCachedContent();
        }

        return null;
    }

    private function getPageCache(): PageCache
    {
        return $this->pageCache;
    }

    private function injectRedirectIfAny(string $content): string
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

    private function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }

    private function getHead(): Head
    {
        return $this->head;
    }

    private function getBody(): Body
    {
        return $this->body;
    }

}