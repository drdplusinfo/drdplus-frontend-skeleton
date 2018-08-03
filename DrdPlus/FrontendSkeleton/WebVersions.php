<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class WebVersions extends StrictObject
{

    public const LATEST_VERSION = 'master';

    /** @var Dirs */
    private $dirs;
    private $allVersions;
    private $lastStableVersion;
    private $lastUnstableVersion;
    private $allStableVersions;
    private $currentVersion;
    private $currentCommitHash;
    private $patchVersions;

    public function __construct(Dirs $dirs)
    {
        $this->dirs = $dirs;
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
                'cd ' . \escapeshellarg($this->dirs->getDocumentRoot()) . ' && git branch | grep -P \'v?\d+\.\d+\' --only-matching | sort --version-sort --reverse'
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
     * Intentionally are versions taken from branch only, not tags, to lower amount of versions to switch into.
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getCurrentVersion(): string
    {
        $possibleDirVersion = \basename($this->dirs->getDocumentRoot());
        if ($this->hasVersion($possibleDirVersion)) {
            return $possibleDirVersion;
        }

        if ($this->currentVersion === null) {
            $this->currentVersion = $this->executeCommand('cd ' . \escapeshellarg($this->dirs->getDocumentRoot()) . ' && git rev-parse --abbrev-ref HEAD');
        }

        return $this->currentVersion;
    }

    public function getCurrentPatchVersion(): string
    {
        return $this->getLastPatchVersionOf($this->getCurrentVersion());
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

    public function getVersionName(string $version): string
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
            $this->currentCommitHash = $this->executeCommand('git log --max-count=1 --format=%H --no-abbrev-commit');
        }

        return $this->currentCommitHash;
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
            $this->patchVersions = $this->executeArray('git tag | grep -E "([[:digit:]]+[.]){2}[[:alnum:]]+([.][[:digit:]]+)?" --only-matching | sort --version-sort --reverse');
        }

        return $this->patchVersions;
    }

    public function isCurrentVersionStable(): bool
    {
        return $this->getCurrentVersion() !== $this->getLastUnstableVersion();
    }

    public function getVersionDocumentRoot(string $forVersion): string
    {
        if ($forVersion === static::LATEST_VERSION) {
            return $this->dirs->getMasterDocumentRoot();
        }
        if ($forVersion === $this->getCurrentVersion()) {
            return $this->dirs->getDocumentRoot(); // current version to use
        }

        return $this->dirs->getDirForVersions() . '/' . $forVersion;
    }

    public function getRelativeVersionDocumentRoot(string $forVersion): string
    {
        // /foo/bar/versions/1.0
        $currentVersionDocumentRoot = $this->getVersionDocumentRoot($forVersion);

        // /versions/1.0 or empty string for master
        return \str_replace($this->dirs->getMasterDocumentRoot(), '', $currentVersionDocumentRoot);
    }
}