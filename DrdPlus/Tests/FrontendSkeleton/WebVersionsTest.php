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
        self::assertSame($this->executeCommand('git rev-parse --abbrev-ref HEAD'), $webVersions->getCurrentVersion());
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
    public function I_can_get_all_stable_versions(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        $allVersions = $webVersions->getAllVersions();
        $expectedStableVersions = [];
        foreach ($allVersions as $version) {
            if ($version !== $this->getTestsConfiguration()->getExpectedLastUnstableVersion()) {
                $expectedStableVersions[] = $version;
            }
        }
        self::assertSame($expectedStableVersions, $webVersions->getAllStableVersions());
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
        $allWebVersions = $webVersions->getAllVersions();
        self::assertNotEmpty($allWebVersions, 'At least single web version (from GIT) expected');
        if (!$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertSame([$this->getTestsConfiguration()->getExpectedLastUnstableVersion()], $allWebVersions);
        } else {
            self::assertSame($this->getBranchesFromFileSystem(), $allWebVersions);
        }
    }

    private function getBranchesFromFileSystem(): array
    {
        return $this->runCommand('ls -1 .git/logs/refs/heads/ | sort --version-sort --reverse'); // from latest to oldest
    }

    /**
     * @test
     */
    public function I_can_get_minor_versions(): void
    {
        $tags = $this->runCommand('ls ' . \escapeshellarg($this->getDocumentRoot()) . '/.git/refs/tags');
        $expectedVersionTags = [];
        foreach ($tags as $tag) {
            if (\preg_match('~^(\d+[.]){2}\d+$~', $tag)) {
                $expectedVersionTags[] = $tag;
            }
        }
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertCount(0, $expectedVersionTags, 'No version tags expected as there are no versions');

            return;
        }
        $webVersions = new WebVersions($this->getDocumentRoot());
        self::assertNotEmpty(
            $expectedVersionTags,
            'Some version tags expected as we have versions ' . \implode(',', $webVersions->getAllStableVersions())
        );
        $sortedExpectedVersionTags = $this->sortVersionsFromLatest($expectedVersionTags);
        self::assertSame($sortedExpectedVersionTags, $webVersions->getMinorVersions());
        $this->I_can_get_last_minor_version_for_every_stable_version($sortedExpectedVersionTags, $webVersions);
    }

    private function sortVersionsFromLatest(array $versions): array
    {
        \usort($versions, 'version_compare');

        return \array_reverse($versions);
    }

    private function I_can_get_last_minor_version_for_every_stable_version(array $expectedVersionTags, WebVersions $webVersions): void
    {
        foreach ($webVersions->getAllStableVersions() as $stableVersion) {
            $matchingMinorVersionTags = [];
            foreach ($expectedVersionTags as $expectedVersionTag) {
                if (\strpos($expectedVersionTag, $stableVersion) === 0) {
                    $matchingMinorVersionTags[] = $expectedVersionTag;
                }
            }
            self::assertNotEmpty($matchingMinorVersionTags, "Missing minor version tags for version $stableVersion");
            $sortedMatchingVersionTags = $this->sortVersionsFromLatest($matchingMinorVersionTags);
            self::assertSame(
                \end($sortedMatchingVersionTags),
                $webVersions->getLastMinorVersionOf($stableVersion),
                "Expected different minor version tag for $stableVersion"
            );
        }
    }

    /**
     * @test
     */
    public function I_will_get_last_unstable_version_as_minor_version(): void
    {
        $webVersions = new WebVersions($this->getDocumentRoot());
        self::assertSame($webVersions->getLastUnstableVersion(), $webVersions->getLastMinorVersionOf($webVersions->getLastUnstableVersion()));
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\NoMinorVersionsMatch
     */
    public function I_can_not_get_last_minor_version_for_non_existing_version(): void
    {
        $nonExistingVersion = '-999.999';
        $webVersions = new WebVersions($this->getDocumentRoot());
        try {
            self::assertNotContains($nonExistingVersion, $webVersions->getAllVersions(), 'This version really exists?');
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getMessage());
        }
        $webVersions->getLastMinorVersionOf($nonExistingVersion);
    }
}