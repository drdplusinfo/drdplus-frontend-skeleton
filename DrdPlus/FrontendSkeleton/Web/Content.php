<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton\Web;

use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\PageCache;
use DrdPlus\FrontendSkeleton\Redirect;
use DrdPlus\FrontendSkeleton\ServicesContainer;
use Granam\Strict\Object\StrictObject;

class Content extends StrictObject
{
    /** @var Redirect|null */
    private $redirect;
    /** @var array */
    private $bodyClasses;
    /** @var ServicesContainer */
    private $servicesContainer;

    public function __construct(
        ServicesContainer $servicesContainer,
        ?Redirect $redirect,
        array $bodyClasses = []
    )
    {
        $this->servicesContainer = $servicesContainer;
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
        return $this->servicesContainer->getHtmlHelper();
    }

    private function injectCacheId(HtmlDocument $htmlDocument): void
    {
        $htmlDocument->documentElement->setAttribute('data-cache-stamp', $this->getPageCache()->getCacheId());
    }

    private function composeContent(): string
    {
        $patchVersion = $this->servicesContainer->getWebVersions()->getCurrentPatchVersion();
        $now = \date(\DATE_ATOM);
        $head = $this->servicesContainer->getHead()->getHeadString();
        $bodyClasses = \implode(' ', $this->bodyClasses);
        $menu = $this->servicesContainer->getMenu()->getMenuString();
        $body = $this->servicesContainer->getBody()->getBodyString();

        return <<<HTML
<!DOCTYPE html>
<html lang="cs" data-content-version="{$patchVersion}" data-cached-at="{$now}">
<head>
    {$head}
</head>
<body class="container {$bodyClasses}">
  <div class="background-image"></div>
    {$menu}
    {$body}
</body>
</html>
HTML;
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
        return $this->servicesContainer->getPageCache();
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
}