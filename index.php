<?php
\error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    \ini_set('display_errors', '1');
} else {
    \ini_set('display_errors', '0');
}
$documentRoot = $documentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());
$vendorRoot = $documentRoot . '/vendor';

/** @noinspection PhpIncludeInspection */
$autoLoader = require $vendorRoot . '/autoload.php';

$versionIndexFile = __FILE__;
if (!empty($_GET['version']) && (!\defined('VERSION_SWITCHED') || !VERSION_SWITCHED)) {
    $webVersionSwitcher = new \DrdPlus\FrontendSkeleton\WebVersionSwitcher(
        new \DrdPlus\FrontendSkeleton\WebVersions($documentRoot),
        $documentRoot,
        $documentRoot . '/versions'
    );
    $versionIndexFile = $webVersionSwitcher->getVersionIndexFile($_GET['version']);
}
if ($versionIndexFile !== __FILE__ && \realpath($versionIndexFile) !== \realpath(__FILE__)) {
    \define('VERSION_SWITCHED', true);
    $documentRoot = $webVersionSwitcher->getVersionDocumentRoot($_GET['version']);
    /** @var \Composer\Autoload\ClassLoader $autoLoader */
    $autoLoader->unregister(); // as version index will use its own
    /** @noinspection PhpIncludeInspection */
    require $versionIndexFile;
} else {
    $htmlHelper = $htmlHelper ?? \DrdPlus\FrontendSkeleton\HtmlHelper::createFromGlobals($documentRoot);
    \DrdPlus\FrontendSkeleton\TracyDebugger::enable($htmlHelper->isInProduction());

    $controller = $controller ?? new \DrdPlus\FrontendSkeleton\FrontendController(
            'UA-121206931-1',
            $htmlHelper,
            $documentRoot,
            null, // automatic web root
            $vendorRoot,
            $partsRoot ?? null,
            $genericPartsRoot ?? null
        );
    $vendorRoot = $controller->getVendorRoot();
    $partsRoot = $controller->getPartsRoot();
    $genericPartsRoot = $controller->getGenericPartsRoot();

    /** @noinspection PhpIncludeInspection */
    echo require $genericPartsRoot . '/content.php';
}