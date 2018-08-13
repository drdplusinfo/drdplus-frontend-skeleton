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

    public const LATEST_VERSION = 'master';

    /** @var Configuration */
    private $configuration;
    /** @var string */
    private $currentVersion;
    private $allVersions;
    private $lastStableVersion;
    private $lastUnstableVersion;
    private $allStableVersions;
    private $currentCommitHash;
    private $patchVersions;

    public function __construct(Configuration $configuration, CurrentVersionProvider $currentVersionProvider)
    {
        $this->configuration = $configuration;
        $this->currentVersion = $currentVersionProvider->getCurrentVersion();
    }

    /**
     * Intentionally are versions taken from branch only, not tags, to lower amount of versions to switch into.
     * @return array|string[]
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getAllVersions(): array
    {
        if ($this->allVersions === null) {
            $branches = $this->executeArray(
                'cd ' . \escapeshellarg($this->configuration->getDirs()->getDocumentRoot()) . ' && git branch | grep -P \'v?\d+\.\d+\' --only-matching | sort --version-sort --reverse'
            );
            \array_unshift($branches, self::LATEST_VERSION);

            $this->allVersions = $branches;
        }

        return $this->allVersions;
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
            $versions = $this->getAllVersions();
            $lastVersion = \array_pop($versions);
            // last version is not a master (strange but...) or it is the only version we got
            if ($lastVersion !== self::LATEST_VERSION || \count($versions) === 0) {
                return $lastVersion;
            }

            $this->lastStableVersion = \reset($versions); // next last version
        }

        return $this->lastStableVersion;
    }

    /**
     * @return string probably 'master'
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastUnstableVersion(): string
    {
        if ($this->lastUnstableVersion === null) {
            $versions = $this->getAllVersions();

            $this->lastUnstableVersion = \reset($versions);
        }

        return $this->lastUnstableVersion;
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

    /**
     * Intentionally are shown only minor versions.
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLatestVersion(): string
    {
        return $this->configuration->getLatestVersion();
    }

    public function getCurrentPatchVersion(): string
    {
        return $this->getLastPatchVersionOf($this->getCurrentVersion());
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
        return $version !== self::LATEST_VERSION ? "verze $version" : 'testovací!';
    }

    /**
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getCurrentCommitHash(): string
    {
        if ($this->currentCommitHash === null) {
            $this->ensureVersionExists($this->getCurrentVersion());
            $escapedWebVersionsRootDir = \escapeshellarg($this->configuration->getDirs()->getVersionDocumentRoot($this->getCurrentVersion()));
            $this->currentCommitHash = $this->executeCommand("git -C $escapedWebVersionsRootDir log --max-count=1 --format=%H --no-abbrev-commit");
        }

        return $this->currentCommitHash;
    }

    /**
     * @param string $toMinorVersion
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownVersionToSwitchInto
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    protected function ensureVersionExists(string $toMinorVersion): bool
    {
        $lastPatchVersion = $this->getLastPatchVersionOf($toMinorVersion);
        $toMinorVersionDir = $this->configuration->getDirs()->getVersionDocumentRoot($toMinorVersion);
        if (!\file_exists($toMinorVersionDir)) {
            $this->clone($lastPatchVersion, $toMinorVersion, $toMinorVersionDir);
        } else {
            $this->update($lastPatchVersion, $toMinorVersion, $toMinorVersionDir);
        }

        return true;
    }

    /**
     * @param string $patchVersion
     * @param string $minorVersion
     * @param string $toVersionDir
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     */
    private function clone(string $patchVersion, string $minorVersion, string $toVersionDir): void
    {
        $toVersionDirEscaped = \escapeshellarg($toVersionDir);
        $toVersionEscaped = \escapeshellarg($minorVersion);
        $toLastPatchVersionEscaped = \escapeshellarg($patchVersion);
        $command = "git clone --branch $toVersionEscaped . $toVersionDirEscaped 2>&1 && git -C $toVersionDirEscaped checkout $toLastPatchVersionEscaped 2>&1";
        \exec($command, $rows, $returnCode);
        if ($returnCode !== 0) {
            throw new Exceptions\CanNotLocallyCloneGitVersion(
                "Can not git clone required version '{$minorVersion}' by command '{$command}'"
                . ", got return code '{$returnCode}' and output\n"
                . \implode("\n", $rows)
            );
        }
    }

    private function update(string $patchVersion, string $minorVersion, string $toVersionDir): void
    {
        $toVersionDirEscaped = \escapeshellarg($toVersionDir);
        $toVersionEscaped = \escapeshellarg($minorVersion);
        $toLastPatchVersionEscaped = \escapeshellarg($patchVersion);
        $command = "git -C $toVersionDirEscaped checkout $toVersionEscaped 2>&1 && git -C $toVersionDirEscaped pull --ff-only 2>&1 && git -C $toVersionDirEscaped checkout $toLastPatchVersionEscaped 2>&1";
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

    protected function getLatestVersionDocumentRoot(): string
    {
        return $this->configuration->getDirs()->getVersionDocumentRoot(static::LATEST_VERSION);
    }

    /**
     * @param string $superiorVersion
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\NoPatchVersionsMatch
     */
    public function getLastPatchVersionOf(string $superiorVersion): string
    {
        if ($superiorVersion === static::LATEST_VERSION) {
            return self::LATEST_VERSION;
        }
        $patchVersions = $this->getPatchVersions();
        $matchingPatchVersions = [];
        foreach ($patchVersions as $patchVersion) {
            if (\strpos($patchVersion, $superiorVersion) === 0) {
                $matchingPatchVersions[] = $patchVersion;
            }
        }
        if (!$matchingPatchVersions) {
            throw new Exceptions\NoPatchVersionsMatch("No patch version matches to given superior version $superiorVersion");
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
            $escapedWebVersionsRootDir = \escapeshellarg($this->getLatestVersionDocumentRoot());
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