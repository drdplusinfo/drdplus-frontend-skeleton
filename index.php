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

$rulesVersions = new \DrdPlus\FrontendSkeleton\Versions($documentRoot);
$versionSwitchMutex = new \DrdPlus\FrontendSkeleton\VersionSwitchMutex();
$rulesVersionSwitcher = new \DrdPlus\FrontendSkeleton\RulesVersionSwitcher(
    $rulesVersions,
    new \DrdPlus\FrontendSkeleton\VersionSwitchMutex()
);
$request = new \DrdPlus\FrontendSkeleton\Request();
try {
    $rulesVersionSwitcher->switchToVersion($_GET['version'] ?? $_COOKIE['version'] ?? $rulesVersions->getLastVersion());
} catch (\DrdPlus\FrontendSkeleton\Exceptions\Exception $exception) {
    \trigger_error($exception->getMessage() . '; ' . $exception->getTraceAsString(), E_USER_WARNING);
}

if (array_key_exists('tables', $_GET) || array_key_exists('tabulky', $_GET)) { // we do not require licence confirmation for tables only
    /** @see vendor/drd-plus/rules-html-skeleton/get_tables.php */
    echo include __DIR__ . '/parts/get_tables.php';
    $versionSwitchMutex->unlock();

    return;
}

if (empty($visitorCanAccessContent)) { // can be defined externally by including script
    $visitorCanAccessContent = false;
    $visitorIsUsingTrial = false;
    $visitorCanAccessContent = $isVisitorBot = $request->isVisitorBot();
    if (!$isVisitorBot) {
        $usagePolicy = new \DrdPlus\FrontendSkeleton\UsagePolicy(basename($documentRoot));
        $visitorCanAccessContent = $visitorHasConfirmedOwnership = $usagePolicy->hasVisitorConfirmedOwnership();
        if (!$visitorCanAccessContent) {
            $visitorCanAccessContent = $visitorIsUsingTrial = $usagePolicy->isVisitorUsingTrial();
        }
        if (!$visitorCanAccessContent) {
            /** @see vendor/drd-plus/rules-html-skeleton/pass.php */
            include __DIR__ . '/parts/pass.php';
            $visitorCanAccessContent = $visitorHasConfirmedOwnership = $usagePolicy->hasVisitorConfirmedOwnership(); // may changed
            if (!$visitorCanAccessContent) {
                $visitorCanAccessContent = $visitorIsUsingTrial = $usagePolicy->isVisitorUsingTrial(); // may changed
            }
        }
    }
}

if (!$visitorCanAccessContent) {
    $versionSwitchMutex->unlock();

    return;
}

if ((($_SERVER['QUERY_STRING'] ?? false) === 'pdf' || !file_exists($documentRoot . '/html'))
    && file_exists($documentRoot . '/pdf') && glob($documentRoot . '/pdf/*.pdf')
) {
    /** @see vendor/drd-plus/rules-html-skeleton/get_pdf.php */
    echo include __DIR__ . '/parts/get_pdf.php';
    $versionSwitchMutex->unlock();

    return;
}

/** @see vendor/drd-plus/rules-html-skeleton/content.php */
echo require __DIR__ . '/parts/content.php';
$versionSwitchMutex->unlock();
