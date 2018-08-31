<?php
\error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    \ini_set('display_errors', '1');
} else {
    \ini_set('display_errors', '0');
}
$documentRoot = $documentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());

/** @noinspection PhpIncludeInspection */
require_once $documentRoot . '/vendor/autoload.php';
$dirs = $dirs ?? new \DrdPlus\FrontendSkeleton\Dirs($documentRoot);
$htmlHelper = $htmlHelper ?? \DrdPlus\FrontendSkeleton\HtmlHelper::createFromGlobals($dirs);
if (PHP_SAPI !== 'cli') {
    \DrdPlus\FrontendSkeleton\TracyDebugger::enable($htmlHelper->isInProduction());
}

$configuration = $configuration ?? \DrdPlus\FrontendSkeleton\Configuration::createFromYml($dirs);
$servicesContainer = $servicesContainer ?? new \DrdPlus\FrontendSkeleton\ServicesContainer($configuration, $htmlHelper);
$controller = $controller ?? new \DrdPlus\FrontendSkeleton\FrontendController($servicesContainer);
if ($controller->isRequestedWebVersionUpdate()) {
    $controller->updateWebVersion();
    echo 'OK';

    return;
}
$controller->persistCurrentVersion();

echo $controller->getContent()->getStringContent();