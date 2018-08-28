<?php
/** @var \DrdPlus\FrontendSkeleton\FrontendController $controller */
?>
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
