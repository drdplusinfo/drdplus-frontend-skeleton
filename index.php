<?php
\error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    \ini_set('display_errors', '1');
} else {
    \ini_set('display_errors', '0');
}
$documentRoot = $documentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());
$vendorRoot = $vendorRoot ?? ($documentRoot . '/vendor');

/** @noinspection PhpIncludeInspection */
require_once $vendorRoot . '/autoload.php';

$controller = $controller ?? new \DrdPlus\FrontendSkeleton\FrontendController(
        $documentRoot,
        null, // automatic web root
        $vendorRoot,
        $partsRoot ?? null,
        $genericPartsRoot ?? null
    );
$vendorRoot = $controller->getVendorRoot();
$partsRoot = $controller->getPartsRoot();
$genericPartsRoot = $controller->getGenericPartsRoot();

$htmlHelper = \DrdPlus\FrontendSkeleton\HtmlHelper::createFromGlobals($documentRoot);
\DrdPlus\FrontendSkeleton\TracyDebugger::enable($htmlHelper->isInProduction());

$webVersions = new \DrdPlus\FrontendSkeleton\WebVersions($documentRoot);
$cacheRoot = new \DrdPlus\FrontendSkeleton\CacheRoot($documentRoot);
$versionSwitchMutex = new \DrdPlus\FrontendSkeleton\WebVersionSwitchMutex($cacheRoot);
$versionSwitcher = new \DrdPlus\FrontendSkeleton\WebVersionSwitcher($webVersions, $versionSwitchMutex);
try {
    $versionSwitcher->switchToVersion($_GET['version'] ?? $_COOKIE['version'] ?? $webVersions->getLastVersion());
} catch (\DrdPlus\FrontendSkeleton\Exceptions\Exception $exception) {
    \trigger_error($exception->getMessage() . '; ' . $exception->getTraceAsString(), E_USER_WARNING);
}

/** @noinspection PhpIncludeInspection */
echo require $genericPartsRoot . '/content.php';

$versionSwitchMutex->unlock(); // unlock even if was not locked, just for sure
