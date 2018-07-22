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
  <html lang="cs" data-version="<?= $controller->getCurrentPatchVersion() ?>">
    <head>
      <title><?= $controller->getPageTitle() ?></title>
      <link rel="shortcut icon" href="/favicon.ico">
      <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
      <script id="googleAnalyticsId" data-google-analytics-id=<?= \json_encode($controller->getGoogleAnalyticsId()) ?>
      async src="https://www.googletagmanager.com/gtag/js?id=<?= $controller->getGoogleAnalyticsId() ?>"></script>
        <?php
        foreach ($controller->getJsFiles() as $jsFile) { ?>
          <script type="text/javascript" src="/js/<?= $jsFile ?>"></script>
        <?php }
        foreach ($controller->getCssFiles() as $cssFile) {
            if (\strpos($cssFile, 'no-script.css') !== false) { ?>
              <noscript>
                <link rel="stylesheet" type="text/css" href="/css/<?= $cssFile ?>">
              </noscript>
            <?php } else { ?>
              <link rel="stylesheet" type="text/css" href="/css/<?= $cssFile ?>">
            <?php }
        } ?>
    </head>
    <body class="container <?= \implode(' ', $controller->getBodyClasses()) ?>">
      <div class="background-image"></div>
        <?php
        // $contactsFixed = true; // (default is on top or bottom of the content)
        // $contactsBottom = true; // (default is top)
        // $hideHomeButton = true; // (default is to show)
        $content = \ob_get_contents();
        \ob_clean();
        $content .= $controller->getContacts();
        $content .= $controller->getCustomBodyContent();
        $content .= $controller->getWebContent(); ?>
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
$htmlHelper->updateAssetLinks($htmlDocument, $controller->getWebVersions());
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