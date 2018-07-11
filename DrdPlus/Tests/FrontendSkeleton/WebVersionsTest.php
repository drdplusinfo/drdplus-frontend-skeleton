<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class WebVersionsTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_get_current_version(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        self::assertSame(\exec('git rev-parse --abbrev-ref HEAD'), $webVersions->getCurrentVersion());
    }

    /**
     * @test
     */
    public function I_can_ask_it_if_code_has_specific_version(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        self::assertTrue($webVersions->hasVersion($this->getTestsConfiguration()->getExpectedLastUnstableVersion()));
        if ($this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertTrue($webVersions->hasVersion('1.0'));
        }
        self::assertFalse($webVersions->hasVersion('-1'));
    }

    /**
     * @test
     */
    public function I_can_get_last_stable_version(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        $lastStableVersion = $webVersions->getLastStableVersion();
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertSame($this->getTestsConfiguration()->getExpectedLastUnstableVersion(), $webVersions->getLastStableVersion());
        } else {
            self::assertNotSame($this->getTestsConfiguration()->getExpectedLastUnstableVersion(), $lastStableVersion);
            self::assertGreaterThanOrEqual(0, \version_compare($lastStableVersion, '1.0'));
        }
        self::assertSame(
            $this->getTestsConfiguration()->getExpectedLastVersion(),
            $lastStableVersion,
            'Tests configuration requires different version'
        );
    }

    /**
     * @test
     */
    public function I_can_get_last_unstable_version(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        self::assertSame($this->getTestsConfiguration()->getExpectedLastUnstableVersion(), $webVersions->getLastUnstableVersion());
    }

    /**
     * @test
     */
    public function I_can_get_czech_version_name(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        self::assertSame('testovací!', $webVersions->getVersionName($this->getTestsConfiguration()->getExpectedLastUnstableVersion()));
        self::assertSame('verze 1.2.3', $webVersions->getVersionName('1.2.3'));
    }

    /**
     * @test
     */
    public function I_can_get_current_commit_hash(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        self::assertSame($this->getLastCommitHashFromHeadFile(), $webVersions->getCurrentCommitHash());
    }

    /**
     * @return string
     * @throws \DrdPlus\Tests\FrontendSkeleton\Exceptions\CanNotReadGitHead
     */
    private function getLastCommitHashFromHeadFile(): string
    {
        $head = \file_get_contents($this->getDocumentRoot() . '/.git/HEAD');
        if (\preg_match('~^[[:alnum:]]{40,}$~', $head)) {
            return $head; // the HEAD file contained the has itself
        }
        $gitHeadFile = \trim(\preg_replace('~ref:\s*~', '', \file_get_contents($this->getDocumentRoot() . '/.git/HEAD')));
        $gitHeadFilePath = $this->getDocumentRoot() . '/.git/' . $gitHeadFile;
        if (!\is_readable($gitHeadFilePath)) {
            throw new Exceptions\CanNotReadGitHead(
                "Could not read $gitHeadFilePath, in that dir are files "
                . \implode(',', \scandir(\dirname($gitHeadFilePath), SCANDIR_SORT_NONE))
            );
        }

        return \trim(\file_get_contents($gitHeadFilePath));
    }

    /**
     * @test
     */
    public function I_can_get_all_web_versions(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        $allWebVersions = $webVersions->getAllWebVersions();
        self::assertNotEmpty($allWebVersions, 'At least single web version (from GIT) expected');
        if (!$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertSame([$this->getTestsConfiguration()->getExpectedLastUnstableVersion()], $allWebVersions);
        } else {
            self::assertSame($this->getBranchesFromFileSystem(), $allWebVersions);
        }
    }

    private function getBranchesFromFileSystem(): array
    {
        $command = 'ls -1 .git/logs/refs/heads/ | sort --version-sort --reverse'; // from latest to oldest
        \exec($command, $output, $returnCode);
        self::assertSame(0, $returnCode, "Failed command $command");

        return $output;
    }
}