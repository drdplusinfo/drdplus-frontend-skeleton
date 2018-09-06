<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed;
use DrdPlus\FrontendSkeleton\Partials\CurrentMinorVersionProvider;
use DrdPlus\FrontendSkeleton\Partials\CurrentPatchVersionProvider;
use Granam\Strict\Object\StrictObject;

/**
 * Reader of GIT tags defining available versions of web filesF
 */
class WebVersions extends StrictObject implements CurrentMinorVersionProvider, CurrentPatchVersionProvider
{

    public const LAST_UNSTABLE_VERSION = 'master';

    /** @var Configuration */
    private $configuration;
    /** @var string[] */
    private $allMinorVersions;
    /** @var string */
    private $lastStableMinorVersion;
    /** @var string */
    private $lastStablePatchVersion;
    /** @var string[] */
    private $allStableVersions;
    /** @var string */
    private $currentCommitHash;
    /** @var string[] */
    private $patchVersions;
    /** @var string */
    private $currentPatchVersion;
    /** @var string[] */
    private $existingMinorVersions = [];
    /** @var string[] */
    private $lastPatchVersionsOf = [];
    /** @var string */
    private $lastUnstableVersionRoot;
    /** @var Request */
    private $request;
    /** @var Git */
    private $git;

    public function __construct(Configuration $configuration, Request $request, Git $git)
    {
        $this->configuration = $configuration;
        $this->request = $request;
        $this->git = $git;
    }

    /**
     * Intentionally are versions taken from branches only, not tags, to lower amount of versions to switch into.
     * @return array|string[]
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getAllMinorVersions(): array
    {
        if ($this->allMinorVersions === null) {
            $allBranches = $this->git->getAllVersionedBranches($this->getLastUnstableVersionWebRoot());
            \array_unshift($allBranches, $this->getLastUnstableVersion());

            $this->allMinorVersions = $allBranches;
        }

        return $this->allMinorVersions;
    }

    protected function getLastUnstableVersionWebRoot(): string
    {
        if ($this->lastUnstableVersionRoot === null) {
            $this->ensureMinorVersionExists($this->getLastUnstableVersion());
            $this->lastUnstableVersionRoot = $this->configuration->getDirs()->getVersionRoot($this->getLastUnstableVersion());
        }

        return $this->lastUnstableVersionRoot;
    }

    /**
     * Gives last STABLE version, if any, or 'master' if not
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastStableMinorVersion(): string
    {
        if ($this->lastStableMinorVersion === null) {
            $stableMinorVersions = $this->getAllStableMinorVersions();
            $this->lastStableMinorVersion = \reset($stableMinorVersions);
            if ($this->lastStableMinorVersion === false) {
                $this->lastStableMinorVersion = $this->getLastUnstableVersion();
            }
        }

        return $this->lastStableMinorVersion;
    }

    /**
     * Gives last STABLE patch version, if any, or 'master' if not
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastStablePatchVersion(): string
    {
        if ($this->lastStablePatchVersion === null) {
            $this->lastStablePatchVersion = $this->getLastPatchVersionOf($this->getLastStableMinorVersion());
        }

        return $this->lastStablePatchVersion;
    }

    /**
     * @return string probably 'master'
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastUnstableVersion(): string
    {
        return static::LAST_UNSTABLE_VERSION;
    }

    public function getAllStableMinorVersions(): array
    {
        if ($this->allStableVersions === null) {
            $this->allStableVersions = \array_values( // reset indexes
                \array_diff($this->getAllMinorVersions(), [$this->getLastUnstableVersion()])
            );
        }

        return $this->allStableVersions;
    }

    /**
     * @param string $version
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function hasMinorVersion(string $version): bool
    {
        return \in_array($version, $this->getAllMinorVersions(), true);
    }

    public function getCurrentPatchVersion(): string
    {
        if ($this->currentPatchVersion === null) {
            $this->currentPatchVersion = $this->getLastPatchVersionOf($this->getCurrentMinorVersion());
        }

        return $this->currentPatchVersion;
    }

    public function getCurrentMinorVersion(): string
    {
        $minorVersion = $this->request->getValue(Request::VERSION);
        if ($minorVersion && $this->hasMinorVersion($minorVersion)) {
            return $minorVersion;
        }

        return $this->configuration->getWebLastStableMinorVersion();
    }

    public function getVersionHumanName(string $version): string
    {
        return $version !== $this->getLastUnstableVersion() ? "verze $version" : 'testovací!';
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getCurrentCommitHash(): string
    {
        if ($this->currentCommitHash === null) {
            $this->ensureMinorVersionExists($this->getCurrentMinorVersion());
            $this->currentCommitHash = $this->git->getLastCommitHash(
                $this->configuration->getDirs()->getVersionRoot($this->getCurrentMinorVersion())
            );
        }

        return $this->currentCommitHash;
    }

    /**
     * @param string $minorVersion
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    protected function ensureMinorVersionExists(string $minorVersion): bool
    {
        if (($this->existingMinorVersions[$minorVersion] ?? null) === null) {
            $toMinorVersionDir = $this->configuration->getDirs()->getVersionRoot($minorVersion);
            if (!\file_exists($toMinorVersionDir)) {
                $this->clone($minorVersion, $toMinorVersionDir);
            }
            $this->existingMinorVersions[$minorVersion] = true;
        }

        return true;
    }

    /**
     * @param string $minorVersion
     * @param string $toVersionDir
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownWebVersion
     */
    private function clone(string $minorVersion, string $toVersionDir): array
    {
        return $this->git->cloneBranch($minorVersion, $this->configuration->getWebRepositoryUrl(), $toVersionDir);
    }

    /**
     * @param string $minorVersion
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownWebVersion
     */
    public function update(string $minorVersion): array
    {
        $toMinorVersionDir = $this->configuration->getDirs()->getVersionRoot($minorVersion);
        if (!\file_exists($toMinorVersionDir)) {
            return $this->clone($minorVersion, $toMinorVersionDir);
        }
        try {
            return $this->git->updateBranch($minorVersion, $toMinorVersionDir);
        } catch (ExecutingCommandFailed $executingCommandFailed) {
            if ($this->git->remoteBranchExists($minorVersion)) {
                throw new Exceptions\CanNotUpdateWebVersionViaGit(
                    "Can not update required version '{$minorVersion}': " . $executingCommandFailed->getMessage(),
                    $executingCommandFailed->getCode(),
                    $executingCommandFailed
                );
            }
            throw new Exceptions\UnknownWebVersion(
                "Required web minor version $minorVersion as a GIT branch does not exists:\n{$executingCommandFailed->getMessage()}"
            );
        }
    }

    /**
     * @param string $superiorVersion
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\NoPatchVersionsMatch
     */
    public function getLastPatchVersionOf(string $superiorVersion): string
    {
        if (($this->lastPatchVersionsOf[$superiorVersion] ?? null) === null) {
            $this->lastPatchVersionsOf[$superiorVersion] = $this->determineLastPatchVersionOf($superiorVersion);
        }

        return $this->lastPatchVersionsOf[$superiorVersion];
    }

    private function determineLastPatchVersionOf(string $superiorVersion): string
    {
        if ($superiorVersion === $this->getLastUnstableVersion()) {
            return $this->getLastUnstableVersion();
        }
        $patchVersions = $this->getPatchVersions();
        $matchingPatchVersions = [];
        foreach ($patchVersions as $patchVersion) {
            if (\strpos($patchVersion, $superiorVersion) === 0) {
                $matchingPatchVersions[] = $patchVersion;
            }
        }
        if (!$matchingPatchVersions) {
            throw new Exceptions\NoPatchVersionsMatch(
                "No patch version matches given superior version $superiorVersion, available are only "
                . ($patchVersions ? \implode(',', $patchVersions) : "'nothing'"));
        }
        \usort($matchingPatchVersions, 'version_compare');

        return \end($matchingPatchVersions);
    }

    /**
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getPatchVersions(): array
    {
        if ($this->patchVersions === null) {
            $this->patchVersions = $this->git->getPatchVersions($this->getLastUnstableVersionWebRoot());
        }

        return $this->patchVersions;
    }

    public function isCurrentVersionStable(): bool
    {
        return $this->getCurrentMinorVersion() !== $this->getLastUnstableVersion();
    }
}