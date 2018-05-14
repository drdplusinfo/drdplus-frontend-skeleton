<?php
error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}
$documentRoot = PHP_SAPI !== 'cli' ? rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '\/') : getcwd();

/** @noinspection PhpIncludeInspection */
require_once $documentRoot . '/vendor/autoload.php';

\DrdPlus\FrontendSkeleton\TracyDebugger::enable();

$versions = new \DrdPlus\FrontendSkeleton\Versions($documentRoot);
$versionSwitchMutex = new \DrdPlus\FrontendSkeleton\VersionSwitchMutex();
$versionSwitcher = new \DrdPlus\FrontendSkeleton\VersionSwitcher($versions, $versionSwitchMutex);
$request = new \DrdPlus\FrontendSkeleton\Request();
try {
    $versionSwitcher->switchToVersion($_GET['version'] ?? $_COOKIE['version'] ?? $versions->getLastVersion());
} catch (\DrdPlus\FrontendSkeleton\Exceptions\Exception $exception) {
    \trigger_error($exception->getMessage() . '; ' . $exception->getTraceAsString(), E_USER_WARNING);
}

/** @see vendor/drd-plus/rules-html-skeleton/content.php */
echo require __DIR__ . '/parts/content.php';
$versionSwitchMutex->unlock(); // unlock even if was not locked, just for sure
