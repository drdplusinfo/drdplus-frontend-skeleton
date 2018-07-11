<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class WebVersions extends StrictObject
{

    public const LATEST_VERSION = 'master';

    /**
     * @var string
     */
    private $documentRoot;

    public function __construct(string $documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * Intentionally are versions taken from branch only, not tags, to lower amount of versions to switch into.
     * @return array|string[]
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getAllWebVersions(): array
    {
        $branches = $this->executeArray(
            'cd ' . \escapeshellarg($this->documentRoot) . ' && git branch | grep -P \'v?\d+\.\d+\' --only-matching | sort --version-sort --reverse'
        );
        \array_unshift($branches, self::LATEST_VERSION);

        return $branches;
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
     * Gives last STABLE version, if any, or master of not
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastStableVersion(): string
    {
        $versions = $this->getAllWebVersions();
        $lastVersion = \array_pop($versions);
        // last version is not a master (strange but...) or it is the only version we got
        if ($lastVersion !== 'master' || \count($versions) === 0) {
            return $lastVersion;
        }

        return \reset($versions); // next last version
    }

    /**
     * @return string probably 'master' 
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastUnstableVersion(): string
    {
        $versions = $this->getAllWebVersions();

        return \reset($versions);
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
        return \in_array($version, $this->getAllWebVersions(), true);
    }

    /**
     * Intentionally are versions taken from branch only, not tags, to lower amount of versions to switch into.
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getCurrentVersion(): string
    {
        $branch = $this->execute('cd ' . \escapeshellarg($this->documentRoot) . ' && git branch | grep -P \'^[*]\' | head -n 1');

        return \ltrim($branch, '* ');
    }

    /**
     * @param string $command
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    private function execute(string $command): string
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
        return $this->execute('git log --max-count=1 --format=%H --no-abbrev-commit');
    }
}