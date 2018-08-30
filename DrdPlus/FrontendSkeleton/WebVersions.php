<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed;
use DrdPlus\FrontendSkeleton\Partials\CurrentMinorVersionProvider;
use Granam\Strict\Object\StrictObject;

/**
 * Reader of GIT tags defining available versions of web filesF
 */
class WebVersions extends StrictObject
{

    public const LAST_UNSTABLE_VERSION = 'master';

    /** @var Configuration */
    private $configuration;
    /** @var CurrentMinorVersionProvider */
    private $currentMinorVersionProvider;
    /** @var string[] */
    private $allVersions;
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

    public function __construct(Configuration $configuration, CurrentMinorVersionProvider $currentVersionProvider)
    {
        $this->configuration = $configuration;
        $this->currentMinorVersionProvider = $currentVersionProvider;
    }

    /**
     * Intentionally are versions taken from branches only, not tags, to lower amount of versions to switch into.
     * @return array|string[]
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getAllMinorVersions(): array
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
            $this->ensureMinorVersionExists($this->getLastUnstableVersion());
            $this->lastUnstableVersionRoot = $this->configuration->getDirs()->getVersionRoot($this->getLastUnstableVersion());
        }

        return $this->lastUnstableVersionRoot;
    }

    /**
     * @param string $command
     * @param bool $sendErrorsToStdOut = true
     * @param bool $solveMissingHomeDir = true
     * @return string[]|array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function executeArray(string $command, bool $sendErrorsToStdOut = true, bool $solveMissingHomeDir = true): array
    {
        if ($sendErrorsToStdOut) {
            $command .= ' 2>&1';
        }
        if ($solveMissingHomeDir) {
            $homeDir = \exec('echo $HOME 2>&1', $output, $returnCode);
            $this->guardCommandWithoutError($returnCode, $command, $output);
            if (!$homeDir) {
                if (\file_exists('/home/www-data')) {
                    $command = 'export HOME=/home/www-data 2>&1 && ' . $command;
                } elseif (\file_exists('/var/www')) {
                    $command = 'export HOME=/var/www 2>&1 && ' . $command;
                } // else we will hope it will somehow pass without fatal: failed to expand user dir in: '~/.gitignore'
            }
        }
        $returnCode = 0;
        $output = [];
        \exec($command, $output, $returnCode);
        $this->guardCommandWithoutError($returnCode, $command, $output);

        return $output;
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
                ),
                $returnCode
            );
        }
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

    /**
     * @return string
     */
    public function getCurrentMinorVersion(): string
    {
        return $this->currentMinorVersionProvider->getCurrentMinorVersion();
    }

    /**
     * @param string $command
     * @param bool $sendErrorsToStdOut = true
     * @param bool $solveMissingHomeDir = true
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function execute(string $command, bool $sendErrorsToStdOut = true, bool $solveMissingHomeDir = true): string
    {
        $rows = $this->executeArray($command, $sendErrorsToStdOut, $solveMissingHomeDir);

        return \end($rows);
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
            $escapedVersionRoot = \escapeshellarg($this->configuration->getDirs()->getVersionRoot($this->getCurrentMinorVersion()));
            $this->currentCommitHash = $this->execute("git -C $escapedVersionRoot log --max-count=1 --format=%H --no-abbrev-commit");
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
     */
    private function clone(string $minorVersion, string $toVersionDir): array
    {
        $toVersionDirEscaped = \escapeshellarg($toVersionDir);
        $toVersionEscaped = \escapeshellarg($minorVersion);
        $command = "git clone --branch $toVersionEscaped {$this->configuration->getWebRepositoryUrl()} $toVersionDirEscaped 2>&1";
        \exec($command, $rows, $returnCode);
        if ($returnCode !== 0) {
            if ($this->remoteBranchExists($minorVersion)) {
                throw new Exceptions\CanNotLocallyCloneWebVersionViaGit(
                    "Can not git clone required version '{$minorVersion}' by command '{$command}'"
                    . ", got return code '{$returnCode}' and output\n"
                    . \implode("\n", $rows)
                );
            }
            throw new Exceptions\UnknownWebVersion(
                "Required web minor version $minorVersion as a GIT branch does not exists:\n'{$command}' => " . \implode("\n", $rows)
            );
        }

        return $rows;
    }

    protected function remoteBranchExists(string $branchName): bool
    {
        $command = 'git branch --remotes 2>&1';
        \exec($command, $rows, $returnCode);
        if ($returnCode !== 0) {
            throw new Exceptions\CanNotFindOutRemoteBranches(
                "Can not get remote branches from git by command '{$command}'"
                . ", got return code '{$returnCode}' and output\n"
                . \implode("\n", $rows)
            );
        }
        foreach ($rows as $remoteBranch) {
            $branchFromRemote = \trim(\explode('/', $remoteBranch)[1] ?? '');
            if ($branchName === $branchFromRemote) {
                return true;
            }
        }

        return false;
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
        $toMinorVersionDirEscaped = \escapeshellarg($toMinorVersionDir);
        $commands = [];
        $commands[] = "cd $toMinorVersionDirEscaped";
        $commands[] = 'git pull --ff-only';
        $commands[] = 'git pull --tags';
        try {
            return $this->executeCommandsChainArray($commands);
        } catch (ExecutingCommandFailed $executingCommandFailed) {
            if ($this->remoteBranchExists($minorVersion)) {
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

    private function executeCommandsChainArray(array $commands): array
    {
        return $this->executeArray($this->getChainedCommands($commands), false);
    }

    private function getChainedCommands(array $commands): string
    {
        foreach ($commands as &$command) {
            $command .= ' 2>&1';
        }

        return \implode(' && ', $commands);
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
        return $this->getCurrentMinorVersion() !== $this->getLastUnstableVersion();
    }
}