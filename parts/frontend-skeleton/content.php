<?php
// switch to version has to be BEFORE cache usage
$pageCache = new \DrdPlus\FrontendSkeleton\PageCache($cacheRoot, $webVersions, $htmlHelper->isInProduction());

if ($pageCache->isCacheValid()) {
    return $pageCache->getCachedContent();
}
$previousMemoryLimit = \ini_set('memory_limit', '1G');
\ob_start();
?>
  <!DOCTYPE html>
  <html lang="cs">
    <head>
      <title><?= $htmlHelper->getPageTitle() ?></title>
      <link rel="shortcut icon" href="/favicon.ico">
      <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
        <?php
        /** @var array|string[] $cssFiles */
        $jsRoot = $documentRoot . '/js';
        $jsFiles = new \DrdPlus\FrontendSkeleton\JsFiles($jsRoot);
        foreach ($jsFiles as $jsFile) { ?>
          <script type="text/javascript" src="js/<?= $jsFile ?>"></script>
        <?php }
        /** @var array|string[] $cssFiles */
        $cssRoot = $documentRoot . '/css';
        $cssFiles = new \DrdPlus\FrontendSkeleton\CssFiles($cssRoot);
        foreach ($cssFiles as $cssFile) {
            if (\strpos($cssFile, 'no-script.css') !== false) { ?>
              <noscript>
                <link rel="stylesheet" type="text/css" href="css/<?= $cssFile ?>">
              </noscript>
            <?php } else { ?>
              <link rel="stylesheet" type="text/css" href="css/<?= $cssFile ?>">
            <?php }
        } ?>
    </head>
    <body class="container">
      <div class="background-image"></div>
        <?php
        // $contactsFixed = true; // (default is on top or bottom of the content)
        // $contactsBottom = true; // (default is top)
        // $hideHomeButton = true; // (default is to show)
        include $genericPartsRoot . '/menu.php';
        $content = \ob_get_contents();
        \ob_clean();

        if (\file_exists($partsRoot . '/custom_body_content.php')) {
            $content .= '<div id="customBodyContent">';
            /** @noinspection PhpIncludeInspection */
            include $partsRoot . '/custom_body_content.php';
            $content .= \ob_get_contents();
            \ob_clean();
            $content .= '</div>';
        }

        /** @var array|string[] $sortedWebFiles */
        $sortedWebFiles = new \DrdPlus\FrontendSkeleton\WebFiles($documentRoot . '/web');
        foreach ($sortedWebFiles as $webFile) {
            if (\preg_match('~\.php$~', $webFile)) {
                /** @noinspection PhpIncludeInspection */
                include $webFile;
                $content .= \ob_get_contents();
                \ob_clean();
            } else {
                \readfile($webFile);
                $content .= \ob_get_contents();
                \ob_clean();
            }
        } ?>
    </body>
  </html>
<?php
$content .= \ob_get_clean();
$pageCache->saveContentForDebug($content); // for debugging purpose
$htmlDocument = new \DrdPlus\FrontendSkeleton\HtmlDocument($content);
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
    $htmlHelper->makeExternalLinksLocal($htmlDocument);
}
$updatedContent = $htmlDocument->saveHTML();
$pageCache->cacheContent($updatedContent);

if ($previousMemoryLimit !== false) {
    \ini_set('memory_limit', $previousMemoryLimit);
}

return $updatedContent;
