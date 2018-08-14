<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Partials\CurrentVersionProvider;
use Granam\Strict\Object\StrictObject;

/**
 * Reader of GIT tags defining available versions of web filesF
 */
class WebVersions extends StrictObject
{

    public const LAST_UNSTABLE_VERSION = 'master';

    /** @var Configuration */
    private $configuration;
    /** @var string */
    private $currentVersion;
    /** @var string[] */
    private $allVersions;
    /** @var string */
    private $lastStableVersion;
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

    public function __construct(Configuration $configuration, CurrentVersionProvider $currentVersionProvider)
    {
        $this->configuration = $configuration;
        $this->currentVersion = $currentVersionProvider->getCurrentVersion();
    }

    /**
     * Intentionally are versions taken from branches only, not tags, to lower amount of versions to switch into.
     * @return array|string[]
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getAllVersions(): array
    {
        if ($this->allVersions === null) {
            $escapedLatestVersionWebRoot = \escapeshellarg($this->getLastUnstableVersionWebRoot());
            $command = "git -C $escapedLatestVersionWebRoot branch -r | cut -d '/' -f2 | grep HEAD --invert-match | grep -P 'v?\d+\.\d+' --only-matching | sort --version-sort --reverse";
            $branches = $this->executeArray($command);
            \array_unshift($branches, $this->getLastUnstableVersion());

            $this->allVersions = $branches;
        }

        return $this->allVersions;
    }

    protected function getLastUnstableVersionWebRoot(): string
    {
        if ($this->lastUnstableVersionRoot === null) {
            $this->lastUnstableVersionRoot = $this->configuration->getDirs()->getVersionRoot(static::LAST_UNSTABLE_VERSION);
        }

        return $this->lastUnstableVersionRoot;
    }

    /**
     * @param string $command
     * @return string[]|array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function executeArray(string $command): array
    {
        $returnCode = 0;
        $output = [];
        \exec($command, $output, $returnCode);
        $this->guardCommandWithoutError($returnCode, $command, $output);

        return $output;
    }

    /**
     * Gives last STABLE version, if any, or master if not
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastStableVersion(): string
    {
        if ($this->lastStableVersion === null) {
            $stableVersions = $this->getAllStableVersions();
            $this->lastStableVersion = \reset($stableVersions);
        }

        return $this->lastStableVersion;
    }

    /**
     * @return string probably 'master'
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastUnstableVersion(): string
    {
        return static::LAST_UNSTABLE_VERSION;
    }

    public function getAllStableVersions(): array
    {
        if ($this->allStableVersions === null) {
            $this->allStableVersions = \array_values( // reset indexes
                \array_diff($this->getAllVersions(), [$this->getLastUnstableVersion()])
            );
        }

        return $this->allStableVersions;
    }

    /**
     * @param int $returnCode
     * @param string $command
     * @param array $output
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function guardCommandWithoutError(int $returnCode, string $command, ?array $output): void
    {
        if ($returnCode !== 0) {
            throw new Exceptions\ExecutingCommandFailed(
                "Error while executing '$command', expected return '0', got '$returnCode'"
                . ($output !== null ?
                    ("with output: '" . \implode("\n", $output) . "'")
                    : ''
                )
            );
        }
    }

    /**
     * @param string $version
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function hasVersion(string $version): bool
    {
        return \in_array($version, $this->getAllVersions(), true);
    }

    public function getCurrentPatchVersion(): string
    {
        if ($this->currentPatchVersion === null) {
            $this->currentPatchVersion = $this->getLastPatchVersionOf($this->getCurrentVersion());
        }

        return $this->currentPatchVersion;
    }

    /**
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    /**
     * @param string $command
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function executeCommand(string $command): string
    {
        $returnCode = 0;
        $output = [];
        $lastRow = \exec($command, $output, $returnCode);
        $this->guardCommandWithoutError($returnCode, $command, $output);

        return $lastRow;
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
            $this->ensureMinorVersionExists($this->getCurrentVersion());
            $escapedVersionRoot = \escapeshellarg($this->configuration->getDirs()->getVersionRoot($this->getCurrentVersion()));
            $this->currentCommitHash = $this->executeCommand("git -C $escapedVersionRoot log --max-count=1 --format=%H --no-abbrev-commit");
        }

        return $this->currentCommitHash;
    }

    /**
     * @param string $minorVersion
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownVersionToSwitchInto
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    protected function ensureMinorVersionExists(string $minorVersion): bool
    {
        if (($this->existingMinorVersions[$minorVersion] ?? null) === null) {
            $toMinorVersionDir = $this->configuration->getDirs()->getVersionRoot($minorVersion);
            if (!\file_exists($toMinorVersionDir)) {
                $this->clone($minorVersion, $toMinorVersionDir);
            } else {
                $this->update($minorVersion, $toMinorVersionDir);
            }
        }

        return true;
    }

    /**
     * @param string $minorVersion
     * @param string $toVersionDir
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     */
    private function clone(string $minorVersion, string $toVersionDir): void
    {
        $toVersionDirEscaped = \escapeshellarg($toVersionDir);
        $toVersionEscaped = \escapeshellarg($minorVersion);
        $command = "git clone --branch $toVersionEscaped {$this->configuration->getWebRepositoryUrl()} $toVersionDirEscaped 2>&1";
        \exec($command, $rows, $returnCode);
        if ($returnCode !== 0) {
            throw new Exceptions\CanNotLocallyCloneGitVersion(
                "Can not git clone required version '{$minorVersion}' by command '{$command}'"
                . ", got return code '{$returnCode}' and output\n"
                . \implode("\n", $rows)
            );
        }
    }

    private function update(string $minorVersion, string $toVersionDir): void
    {
        $toVersionDirEscaped = \escapeshellarg($toVersionDir);
        $command = "git -C $toVersionDirEscaped pull --ff-only 2>&1 && git -C $toVersionDirEscaped pull --tags";
        $rows = []; // resetting rows as they may NOT be changed on failure
        \exec($command, $rows, $returnCode);
        if ($returnCode !== 0) {
            throw new Exceptions\CanNotUpdateGitVersion(
                "Can not update required version '{$minorVersion}' by command '{$command}'"
                . ", got return code '{$returnCode}' and output\n"
                . \implode("\n", $rows)
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
                "No patch version matches given superior version $superiorVersion, available are only " . ($patchVersions ? \implode(',', $patchVersions) : "'nothing'"));
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
            $this->ensureMinorVersionExists($this->getLastUnstableVersion());
            $escapedWebVersionsRootDir = \escapeshellarg($this->getLastUnstableVersionWebRoot());
            $this->patchVersions = $this->executeArray(<<<CMD
git -C $escapedWebVersionsRootDir tag | grep -E "([[:digit:]]+[.]){2}[[:alnum:]]+([.][[:digit:]]+)?" --only-matching | sort --version-sort --reverse
CMD
            );
        }

        return $this->patchVersions;
    }

    public function isCurrentVersionStable(): bool
    {
        return $this->getCurrentVersion() !== $this->getLastUnstableVersion();
    }
}