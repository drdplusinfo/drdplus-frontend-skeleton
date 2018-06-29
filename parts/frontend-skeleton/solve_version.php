<?php
/** @noinspection PhpIncludeInspection */
$autoLoader = require $vendorRoot . '/autoload.php';

$versionIndexFile = $documentRoot . '/index.php';
$masterVersionIndexFile = $versionIndexFile;
$version = $_GET['version'] ?? $_POST['version'] ?? $_COOKIE['version'] ?? null;
if (!$version || (\defined('VERSION_SWITCHED') && VERSION_SWITCHED)) {
    return false;
}
$webVersionSwitcher = new \DrdPlus\FrontendSkeleton\WebVersionSwitcher(
    new \DrdPlus\FrontendSkeleton\WebVersions($documentRoot),
    $documentRoot,
    $documentRoot . '/versions'
);
$versionIndexFile = $webVersionSwitcher->getVersionIndexFile($version);
if ($versionIndexFile === $currentIndexFile || \realpath($versionIndexFile) === \realpath($currentIndexFile)) {
    return false;
}
\define('VERSION_SWITCHED', true);
/** @var \Composer\Autoload\ClassLoader $autoLoader */
$autoLoader->unregister(); // as version index will use its own
/** @noinspection PhpIncludeInspection */
require $versionIndexFile;

return true;