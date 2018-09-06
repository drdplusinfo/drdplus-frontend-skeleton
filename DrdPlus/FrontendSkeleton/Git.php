<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class Git extends StrictObject
{
    /**
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotGetGitStatus
     */
    public function getGitStatus(): array
    {
        // GIT status is same for any working dir, if it is a sub-dir of wanted GIT project root
        try {
            return $this->executeArray('git status');
        } catch (Exceptions\ExecutingCommandFailed $executingCommandFailed) {
            throw new Exceptions\CanNotGetGitStatus(
                "Can not get git status:\n"
                . $executingCommandFailed->getMessage(),
                $executingCommandFailed->getCode(),
                $executingCommandFailed
            );
        }
    }

    /**
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotGetGitDiff
     */
    public function getDiffAgainstOriginMaster(): array
    {
        try {
            return $this->executeArray('git diff origin/master');
        } catch (Exceptions\ExecutingCommandFailed $executingCommandFailed) {
            throw new Exceptions\CanNotGetGitDiff(
                "Can not get diff:\n"
                . $executingCommandFailed->getMessage(),
                $executingCommandFailed->getCode(),
                $executingCommandFailed
            );
        }
    }

    /**
     * @param string $dir
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getLastCommitHash(string $dir): string
    {
        $escapedDir = \escapeshellarg($dir);

        return $this->execute("git -C $escapedDir log --max-count=1 --format=%H --no-abbrev-commit");
    }

    /**
     * @param string $branch
     * @param string $destinationDir
     * @param string $repositoryUrl
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownWebVersion
     */
    public function cloneBranch(string $branch, string $repositoryUrl, string $destinationDir): array
    {
        $destinationDirEscaped = \escapeshellarg($destinationDir);
        $branchEscaped = \escapeshellarg($branch);
        try {
            return $this->executeArray("git clone --branch $branchEscaped $repositoryUrl $destinationDirEscaped");
        } catch (Exceptions\ExecutingCommandFailed $executingCommandFailed) {
            if ($this->remoteBranchExists($branch)) {
                throw new Exceptions\CanNotLocallyCloneWebVersionViaGit(
                    "Can not git clone required version '{$branch}':\n"
                    . $executingCommandFailed->getMessage(),
                    $executingCommandFailed->getCode(),
                    $executingCommandFailed
                );
            }
            throw new Exceptions\UnknownWebVersion(
                "Required web minor version $branch as a GIT branch does not exists:\n"
                . $executingCommandFailed->getMessage(),
                $executingCommandFailed->getCode(),
                $executingCommandFailed
            );
        }
    }

    /**
     * @param string $branch
     * @param string $destinationDir
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneWebVersionViaGit
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownWebVersion
     */
    public function updateBranch(string $branch, string $destinationDir): array
    {
        $branchEscaped = \escapeshellarg($branch);
        $destinationDirEscaped = \escapeshellarg($destinationDir);
        $commands = [];
        $commands[] = "cd $destinationDirEscaped";
        $commands[] = "git checkout $branchEscaped";
        $commands[] = 'git pull --ff-only';
        $commands[] = 'git pull --tags';

        return $this->executeCommandsChainArray($commands);
    }

    /**
     * @param string $branchName
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotFindOutRemoteBranches
     */
    public function remoteBranchExists(string $branchName): bool
    {
        try {
            $rows = $this->executeArray('git branch --remotes');
        } catch (Exceptions\ExecutingCommandFailed $executingCommandFailed) {
            throw new Exceptions\CanNotFindOutRemoteBranches(
                $executingCommandFailed->getMessage(),
                $executingCommandFailed->getCode(),
                $executingCommandFailed
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
     * @param string $dir
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getPatchVersions(string $dir): array
    {
        $dirEscaped = \escapeshellarg($dir);
        $commands = [
            "git -C $dirEscaped tag",
            'grep -E "([[:digit:]]+[.]){2}[[:alnum:]]+([.][[:digit:]]+)?" --only-matching',
            'sort --version-sort --reverse'
        ];

        return $this->executeArray(\implode(' | ', $commands));
    }

    /**
     * @param string $dir
     * @return array
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\ExecutingCommandFailed
     */
    public function getAllVersionedBranches(string $dir): array
    {
        $dirEscaped = \escapeshellarg($dir);
        $commands = [
            "git -C $dirEscaped branch -r ",
            'cut -d "/" -f2',
            'grep HEAD --invert-match',
            'grep -P "v?\d+\.\d+" --only-matching',
            'sort --version-sort --reverse'
        ];

        return $this->executeArray(\implode(' | ', $commands));
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

}