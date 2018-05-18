<?php
\error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    \ini_set('display_errors', '1');
} else {
    \ini_set('display_errors', '0');
}
$documentRoot = $documentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());

$vendorRoot = $vendorRoot ?? ($documentRoot . '/vendor');
if (\file_exists($vendorRoot . '/autoload.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once $vendorRoot . '/autoload.php';
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}

$htmlHelper = \DrdPlus\FrontendSkeleton\HtmlHelper::createFromGlobals($documentRoot);
\DrdPlus\FrontendSkeleton\TracyDebugger::enable($htmlHelper->isInProduction());

$webVersions = new \DrdPlus\FrontendSkeleton\WebVersions($documentRoot);
$versionSwitchMutex = new \DrdPlus\FrontendSkeleton\WebVersionSwitchMutex();
$versionSwitcher = new \DrdPlus\FrontendSkeleton\WebVersionSwitcher($webVersions, $versionSwitchMutex);
$request = new \DrdPlus\FrontendSkeleton\Request();
try {
    $versionSwitcher->switchToVersion($_GET['version'] ?? $_COOKIE['version'] ?? $webVersions->getLastVersion());
} catch (\DrdPlus\FrontendSkeleton\Exceptions\Exception $exception) {
    \trigger_error($exception->getMessage() . '; ' . $exception->getTraceAsString(), E_USER_WARNING);
}

$partsRoot = $partsRoot ?? ($documentRoot . '/parts');
if (\file_exists($partsRoot . '/content.php')) {
    /** @noinspection PhpIncludeInspection */
    echo require $partsRoot . '/content.php';
} else {
    echo require __DIR__ . '/parts/content.php';
}
$versionSwitchMutex->unlock(); // unlock even if was not locked, just for sure
