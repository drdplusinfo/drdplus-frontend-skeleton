<?php
/** @noinspection PhpIncludeInspection */
$autoLoader = require __DIR__ . '/safe_autoload.php';

$currentIndexFile = $documentRoot . '/index.php';
$version = $_GET['version'] ?? $_POST['version'] ?? $_COOKIE['version'] ?? $latestVersion ?? null;
if (!$version || !empty($versionSwitched)) {
    return false;
}
if (PHP_SAPI !== 'cli') {
    \DrdPlus\FrontendSkeleton\TracyDebugger::enable();
}
$dirs = $dirs ?? new \DrdPlus\FrontendSkeleton\Dirs($masterDocumentRoot, $documentRoot);
$webVersions = new \DrdPlus\FrontendSkeleton\WebVersions($dirs);
$webVersionSwitcher = new \DrdPlus\FrontendSkeleton\WebVersionSwitcher(
    $webVersions,
    $dirs,
    new \DrdPlus\FrontendSkeleton\CookiesService()
);
$webVersionSwitcher->persistCurrentVersion($version); // saves required version into cookie
$versionIndexFile = $webVersionSwitcher->getVersionIndexFile($version);
if ($versionIndexFile === $currentIndexFile || \realpath($versionIndexFile) === \realpath($currentIndexFile)) {
    return false;
}
$documentRoot = $webVersions->getVersionDocumentRoot($version);
$dirsClass = \get_class($dirs);
$dirs = new $dirsClass($masterDocumentRoot, $documentRoot); // as $dirs object will be used in included version-specific index
$versionSwitched = $version;
$webVersionSwitcher->persistCurrentVersion($version); // saves required version into cookie
/** @var \Composer\Autoload\ClassLoader $autoLoader */
$autoLoader->unregister(); // as version index will use its own
/** @noinspection PhpIncludeInspection */
require $versionIndexFile;

return true;