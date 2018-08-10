<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class WebVersionSwitcher extends StrictObject
{

    /** @var WebVersions */
    private $webVersions;
    /** @var Dirs */
    private $dirs;
    /** @var CookiesService */
    private $cookiesService;

    public function __construct(WebVersions $webVersions, Dirs $dirs, CookiesService $cookiesService)
    {
        $this->webVersions = $webVersions;
        $this->dirs = $dirs;
        $this->cookiesService = $cookiesService;
    }

    /**
     * @param string $toVersion
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownVersionToSwitchInto
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateGitVersion
     */
    public function getVersionIndexFile(string $toVersion): string
    {
        $this->ensureVersionExists($toVersion);

        return $this->webVersions->getVersionDocumentRoot($toVersion) . '/index.php';
    }

    /**
     * @param string $toVersion
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownVersionToSwitchInto
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    protected function ensureVersionExists(string $toVersion): bool
    {
        if ($toVersion === $this->webVersions->getCurrentVersion()) {
            return true; // we are done
        }
        if (!$this->webVersions->hasVersion($toVersion)) {
            throw new Exceptions\UnknownVersionToSwitchInto("Required version {$toVersion} does not exist");
        }
        $lastPatchVersion = $this->webVersions->getLastPatchVersionOf($toVersion);
        $toVersionDir = $this->dirs->getDirForVersions() . '/' . $toVersion;
        $toVersionDirEscaped = \escapeshellarg($toVersionDir);
        $toVersionEscaped = \escapeshellarg($toVersion);
        $toLastPatchVersionEscaped = \escapeshellarg($lastPatchVersion);
        if (!\file_exists($toVersionDir)) {
            $command = "git clone --branch $toVersionEscaped . $toVersionDirEscaped 2>&1 && git -C $toVersionDirEscaped checkout $toLastPatchVersionEscaped";
            \exec($command, $rows, $returnCode);
            if ($returnCode !== 0) {
                throw new Exceptions\CanNotLocallyCloneGitVersion(
                    "Can not git clone required version '{$toVersion}' by command '{$command}'"
                    . ", got return code '{$returnCode}' and output\n"
                    . \implode("\n", $rows)
                );
            }
            if (!$this->isVendorDirVersioned($toVersionDir)) { // MAY be versioned and already fetched by checkout
                $this->installComposerLibraries($toVersionDir);
            }
        } else {
            $command = "git -C $toVersionDirEscaped checkout $toVersionEscaped 2>&1 && git -C $toVersionDirEscaped pull --ff-only 2>&1 && git -C $toVersionDirEscaped checkout $toLastPatchVersionEscaped 2>&1";
            $rows = []; // resetting rows as they may NOT be changed on failure
            \exec($command, $rows, $returnCode);
            if ($returnCode !== 0) {
                throw new Exceptions\CanNotUpdateGitVersion(
                    "Can not update required version '{$toVersion}' by command '{$command}'"
                    . ", got return code '{$returnCode}' and output\n"
                    . \implode("\n", $rows)
                );
            }
            if (!$this->isVendorDirVersioned($toVersionDir)) {
                $this->installComposerLibraries($toVersionDir);
            }
        }

        return true;
    }

    /**
     * @param string $documentRoot
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function installComposerLibraries(string $documentRoot): void
    {
        $documentRootEscaped = \escapeshellarg($documentRoot);
        $command = "cd $documentRootEscaped 2>&1 && export COMPOSER_HOME=. && composer --working-dir=$documentRootEscaped install 2>&1";
        \exec($command, $rows, $returnCode);
        if ($returnCode !== 0) {
            throw new Exceptions\ExecutingCommandFailed(
                "Can not install libraries into '{$documentRoot}' by command '{$command}'"
                . ", got return code '{$returnCode}' and output\n"
                . \implode("\n", $rows)
            );
        }
    }

    private function isVendorDirVersioned(string $documentRoot): bool
    {
        $vendorDir = $documentRoot . '/vendor';
        if (!\file_exists($vendorDir)) { // no vendor dir, no versioning
            return false;
        }
        $documentRootEscaped = \escapeshellarg($documentRoot);
        $command = "git -C $documentRootEscaped check-ignore vendor 2>&1";
        \exec($command, $rows, $returnCode);
        if ($returnCode > 1) {
            throw new Exceptions\ExecutingCommandFailed(
                "Can not find out if is vendor dir versioned or not by command '{$command}'"
                . ", got return code '{$returnCode}' and output\n"
                . \implode("\n", $rows)
            );
        }

        return \count($rows) === 0; // GIT returns ignored dirs, so if is not ignored, gives nothing
    }

    public function persistCurrentVersion(string $versionSwitchedTo): bool
    {
        return $this->cookiesService->setCookie('version', $versionSwitchedTo, true /* not readable from JS */, new \DateTime('+ 1 year'));
    }
}