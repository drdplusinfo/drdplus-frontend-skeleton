<?php
\error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    \ini_set('display_errors', '1');
} else {
    \ini_set('display_errors', '0');
}
$masterDocumentRoot = $masterDocumentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());
$documentRoot = $documentRoot ?? $masterDocumentRoot;
$latestVersion = $latestVersion ?? '1.0';

if (!require __DIR__ . '/parts/frontend-skeleton/solve_version.php') { // returns true if version has been switched and solved
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . '/parts/frontend-skeleton/safe_autoload.php';
    $dirs = $dirs ?? new \DrdPlus\FrontendSkeleton\Dirs($masterDocumentRoot, $documentRoot);
    $htmlHelper = $htmlHelper ?? \DrdPlus\FrontendSkeleton\HtmlHelper::createFromGlobals($dirs);
    if (PHP_SAPI !== 'cli') {
        \DrdPlus\FrontendSkeleton\TracyDebugger::enable($htmlHelper->isInProduction());
    }

    $controller = $controller
        ?? new \DrdPlus\FrontendSkeleton\FrontendController('UA-121206931-1', $htmlHelper, $dirs);

    /** @noinspection PhpIncludeInspection */
    echo require $dirs->getGenericPartsRoot() . '/content.php';
}