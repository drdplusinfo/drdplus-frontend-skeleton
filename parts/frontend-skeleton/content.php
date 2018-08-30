<?php
/** @var \DrdPlus\FrontendSkeleton\FrontendController $controller */

$cachedContent = $controller->getCachedContent();
if ($cachedContent !== '') {
    return $controller->injectRedirectIfAny($cachedContent); // redirect is NOT cached and has to be injected again and again
}

$previousMemoryLimit = \ini_set('memory_limit', '1G');
\ob_start();
?>
  <!DOCTYPE html>
  <html lang="cs" data-content-version="<?= $controller->getCurrentPatchVersion() ?>" data-cached-at="<?= \date(\DATE_ATOM); ?>">
    <head>
        <?php
        $content = \ob_get_contents();
        \ob_clean();
        $content .= $controller->getHead(); ?>
    </head>
    <body class="container <?= \implode(' ', $controller->getBodyClasses()) ?>">
      <div class="background-image"></div>
        <?php
        $content .= \ob_get_contents();
        \ob_clean();
        $content .= $controller->getMenu();
        $content .= $controller->fetchWebContent(); ?>
    </body>
  </html>
<?php
$content .= \ob_get_clean();
$controller->getPageCache()->saveContentForDebug($content); // for debugging purpose
$htmlDocument = new \DrdPlus\FrontendSkeleton\HtmlDocument($content);
$htmlHelper = $controller->getHtmlHelper();
$htmlHelper->prepareSourceCodeLinks($htmlDocument);
$htmlHelper->addIdsToTablesAndHeadings($htmlDocument);
$htmlHelper->replaceDiacriticsFromIds($htmlDocument);
$htmlHelper->replaceDiacriticsFromAnchorHashes($htmlDocument);
$htmlHelper->addAnchorsToIds($htmlDocument);
$htmlHelper->resolveDisplayMode($htmlDocument);
$htmlHelper->markExternalLinksByClass($htmlDocument);
$htmlHelper->externalLinksTargetToBlank($htmlDocument);
$htmlHelper->injectIframesWithRemoteTables($htmlDocument);
$htmlHelper->addVersionHashToAssets($htmlDocument);
if (!$htmlHelper->isInProduction()) {
    $htmlHelper->makeExternalDrdPlusLinksLocal($htmlDocument);
}
$controller->injectCacheId($htmlDocument);
$updatedContent = $htmlDocument->saveHTML();
$controller->getPageCache()->cacheContent($updatedContent);

// has to be AFTER cache as we do not want to cache it
$updatedContent = $controller->injectRedirectIfAny($updatedContent);

if ($previousMemoryLimit !== false) {
    \ini_set('memory_limit', $previousMemoryLimit);
}

return $updatedContent;