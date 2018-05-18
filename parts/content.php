<?php
// switch to version has to be BEFORE cache
$pageCache = new \DrdPlus\FrontendSkeleton\PageCache($documentRoot, $webVersions, $htmlHelper->isInProduction());

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
        foreach ($cssFiles as $cssFile) { ?>
          <link rel="stylesheet" type="text/css" href="css/<?= $cssFile ?>">
        <?php } ?>
    </head>
    <body class="container">
      <div class="background-image"></div>
        <?php
        // $contactsFixed = true; // (default is on top or bottom of the content)
        // $contactsBottom = true; // (default is top)
        // $hideHomeButton = true; // (default is to show)
        if (\file_exists($documentRoot . '/parts/menu.php')) {
            /** @noinspection PhpIncludeInspection */
            include $documentRoot . '/parts/menu.php';
        } elseif (\file_exists($vendorRoot . '/drd-plus/frontend-skeleton/parts/menu.php')) {
            /** @noinspection PhpIncludeInspection */
            include $vendorRoot . '/drd-plus/frontend-skeleton/parts/menu.php';
        } else {
            include __DIR__ . '/menu.php';
        }
        $content = \ob_get_contents();
        \ob_clean();

        if (file_exists($documentRoot . '/custom_body_content.php')) {
            /** @noinspection PhpIncludeInspection */
            include $documentRoot . '/custom_body_content.php';
            $content .= \ob_get_contents();
            \ob_clean();
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
                readfile($webFile);
                $content .= \ob_get_contents();
                \ob_clean();
            }
        } ?>
      <script type="text/javascript">
          $(document).on('click', '.lightbox', function (event) {
              event.preventDefault();
              $(this).ekkoLightbox();
          });
      </script>
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
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    $htmlHelper->makeExternalLinksLocal($htmlDocument);
}
$updated = $htmlDocument->saveHTML();
$pageCache->cacheContent($updated);

if ($previousMemoryLimit !== false) {
    \ini_set('memory_limit', $previousMemoryLimit);
}

return $updated;
