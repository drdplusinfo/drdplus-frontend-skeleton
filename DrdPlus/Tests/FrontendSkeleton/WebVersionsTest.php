<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class WebVersionsTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_get_current_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest(WebVersions::LAST_UNSTABLE_VERSION));
        self::assertSame(WebVersions::LAST_UNSTABLE_VERSION, $webVersions->getCurrentMinorVersion());
    }

    /**
     * @test
     */
    public function I_can_get_current_patch_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        if ($webVersions->getCurrentMinorVersion() === $this->getTestsConfiguration()->getExpectedLastUnstableVersion()) {
            self::assertSame(
                $this->getTestsConfiguration()->getExpectedLastUnstableVersion(),
                $webVersions->getCurrentPatchVersion()
            );
        } else {
            self::assertRegExp(
                '~^' . \preg_quote($webVersions->getCurrentMinorVersion(), '~') . '[.]\d+$~',
                $webVersions->getCurrentPatchVersion()
            );
        }
    }

    /**
     * @test
     */
    public function I_can_ask_it_if_code_has_specific_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        self::assertTrue($webVersions->hasMinorVersion($this->getTestsConfiguration()->getExpectedLastUnstableVersion()));
        if ($this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertTrue($webVersions->hasMinorVersion('1.0'));
        }
        self::assertFalse($webVersions->hasMinorVersion('-1'));
    }

    /**
     * @test
     */
    public function I_can_get_last_stable_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $lastStableVersion = $webVersions->getLastStableMinorVersion();
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertSame($this->getTestsConfiguration()->getExpectedLastUnstableVersion(), $webVersions->getLastStableMinorVersion());
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
    public function I_will_get_unstable_version_if_there_are_no_last_stable_version(): void
    {
        $webVersions = $this->getWebVersionsWithEmptyStableMinorVersions();
        self::assertSame($webVersions->getLastUnstableVersion(), $webVersions->getLastStableMinorVersion());
    }

    private function getWebVersionsWithEmptyStableMinorVersions(): WebVersions
    {
        $configuration = $this->getConfiguration();
        $request = $this->createRequest();

        return new class($configuration, $request) extends WebVersions
        {
            public function getAllStableMinorVersions(): array
            {
                return [];
            }

        };
    }

    /**
     * @test
     */
    public function I_can_get_last_unstable_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        self::assertSame($this->getTestsConfiguration()->getExpectedLastUnstableVersion(), $webVersions->getLastUnstableVersion());
        $versions = $webVersions->getAllMinorVersions();
        $lastVersion = \reset($versions);
        self::assertSame($lastVersion, $webVersions->getLastUnstableVersion());
    }

    /**
     * @test
     */
    public function I_can_get_all_stable_versions(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $allVersions = $webVersions->getAllMinorVersions();
        $expectedStableVersions = [];
        foreach ($allVersions as $version) {
            if ($version !== $this->getTestsConfiguration()->getExpectedLastUnstableVersion()) {
                $expectedStableVersions[] = $version;
            }
        }
        self::assertSame($expectedStableVersions, $webVersions->getAllStableMinorVersions());
    }

    /**
     * @test
     */
    public function I_can_get_czech_version_name(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        self::assertSame('testovací!', $webVersions->getVersionHumanName($this->getTestsConfiguration()->getExpectedLastUnstableVersion()));
        self::assertSame('verze 1.2.3', $webVersions->getVersionHumanName('1.2.3'));
    }

    /**
     * @test
     */
    public function I_can_get_current_commit_hash(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $currentCommitHash = $webVersions->getCurrentCommitHash(); // called before reading .git/HEAD to ensure it exists
        self::assertSame(
            $this->getLastCommitHashFromHeadFile(
                $this->createDirs()->getVersionRoot($this->getTestsConfiguration()->getExpectedLastVersion())
            ),
            $currentCommitHash
        );
    }

    /**
     * @param string $dir
     * @return string
     * @throws \DrdPlus\Tests\FrontendSkeleton\Exceptions\CanNotReadGitHead
     */
    private function getLastCommitHashFromHeadFile(string $dir): string
    {
        $head = \file_get_contents($dir . '/.git/HEAD');
        if (\preg_match('~^[[:alnum:]]{40,}$~', $head)) {
            return $head; // the HEAD file contained the has itself
        }
        $gitHeadFile = \trim(\preg_replace('~ref:\s*~', '', \file_get_contents($dir . '/.git/HEAD')));
        $gitHeadFilePath = $dir . '/.git/' . $gitHeadFile;
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
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $allWebVersions = $webVersions->getAllMinorVersions();
        self::assertNotEmpty($allWebVersions, 'At least single web version (from GIT) expected');
        if (!$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertSame([$this->getTestsConfiguration()->getExpectedLastUnstableVersion()], $allWebVersions);
        } else {
            self::assertSame(
                $this->getVersionsRange($this->getTestsConfiguration()->getExpectedLastVersion()),
                $allWebVersions
            );
        }
    }

    protected function getVersionsRange(string $lastVersion): array
    {
        $stableVersions = \range(1.0, (float)$lastVersion);

        $stringStableVersions = \array_map(function (float $version) {
            $stringVersion = (string)$version;
            if (\strpos($stringVersion, '.') === false) {
                $stringVersion .= '.0';
            }

            return $stringVersion;
        }, $stableVersions);
        \array_unshift($stringStableVersions, WebVersions::LAST_UNSTABLE_VERSION);

        return $stringStableVersions;
    }

    /**
     * @test
     */
    public function I_can_get_patch_versions(): void
    {
        $tags = $this->runCommand(
            'git -C ' . \escapeshellarg($this->getConfiguration()->getDirs()->getVersionRoot($this->getTestsConfiguration()->getExpectedLastUnstableVersion())) . ' tag'
        );
        $expectedVersionTags = [];
        foreach ($tags as $tag) {
            if (\preg_match('~^(\d+[.]){2}[[:alnum:]]+([.]\d+)?$~', $tag)) {
                $expectedVersionTags[] = $tag;
            }
        }
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertCount(0, $expectedVersionTags, 'No version tags expected as there are no versions');

            return;
        }
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        self::assertNotEmpty(
            $expectedVersionTags,
            'Some version tags expected as we have versions ' . \implode(',', $webVersions->getAllStableMinorVersions())
        );
        $sortedExpectedVersionTags = $this->sortVersionsFromLatest($expectedVersionTags);
        self::assertSame($sortedExpectedVersionTags, $webVersions->getPatchVersions());
        $this->I_can_get_last_patch_version_for_every_stable_version($sortedExpectedVersionTags, $webVersions);
    }

    private function sortVersionsFromLatest(array $versions): array
    {
        \usort($versions, 'version_compare');

        return \array_reverse($versions);
    }

    private function I_can_get_last_patch_version_for_every_stable_version(array $expectedVersionTags, WebVersions $webVersions): void
    {
        foreach ($webVersions->getAllStableMinorVersions() as $stableVersion) {
            $matchingPatchVersionTags = [];
            foreach ($expectedVersionTags as $expectedVersionTag) {
                if (\strpos($expectedVersionTag, $stableVersion) === 0) {
                    $matchingPatchVersionTags[] = $expectedVersionTag;
                }
            }
            self::assertNotEmpty($matchingPatchVersionTags, "Missing patch version tags for version $stableVersion");
            $sortedMatchingVersionTags = $this->sortVersionsFromLatest($matchingPatchVersionTags);
            self::assertSame(
                \reset($sortedMatchingVersionTags),
                $webVersions->getLastPatchVersionOf($stableVersion),
                "Expected different patch version tag for $stableVersion"
            );
        }
    }

    /**
     * @test
     */
    public function I_will_get_last_unstable_version_as_patch_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        self::assertSame($webVersions->getLastUnstableVersion(), $webVersions->getLastPatchVersionOf($webVersions->getLastUnstableVersion()));
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\NoPatchVersionsMatch
     */
    public function I_can_not_get_last_patch_version_for_non_existing_version(): void
    {
        $nonExistingVersion = '-999.999';
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        try {
            self::assertNotContains($nonExistingVersion, $webVersions->getAllMinorVersions(), 'This version really exists?');
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getMessage());
        }
        $webVersions->getLastPatchVersionOf($nonExistingVersion);
    }

    /**
     * @test
     */
    public function I_can_get_index_of_another_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $versions = $webVersions->getAllMinorVersions();
        if (!$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertCount(1, $versions, 'Only a single version expected due to a config');

            return;
        }
        self::assertGreaterThan(
            1,
            \count($versions),
            'Expected at least two versions to test, got only ' . \implode(',', $versions)
        );
    }

    /**
     * @test
     */
    public function I_can_update_already_fetched_web_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        foreach ($webVersions->getAllMinorVersions() as $version) {
            $result = $webVersions->update($version);
            self::assertNotEmpty($result);
        }
    }

    /**
     * @test
     */
    public function I_can_update_web_version_even_if_not_yet_fetched_locally(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $dirs = $this->createDirs();
        foreach ($webVersions->getAllMinorVersions() as $version) {
            $versionRoot = $dirs->getVersionRoot($version);
            if (\file_exists($versionRoot)) {
                $versionRootEscaped = \escapeshellarg($versionRoot);
                \exec("rm -fr $versionRootEscaped 2>&1", $output, $returnCode);
                self::assertSame(0, $returnCode, "Can not remove $versionRoot, got " . implode("\n", $output));
            }
            $result = $webVersions->update($version);
            self::assertNotEmpty($result);
        }
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\UnknownWebVersion
     * @expectedExceptionMessageRegExp ~999[.]999~
     */
    public function I_can_not_update_non_existing_web_version(): void
    {
        $webVersions = new WebVersions($this->getConfiguration(), $this->createRequest());
        $webVersions->update('999.999');
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_get_current_minor_version(): void
    {
        $webVersionsClass = static::getSutClass();
        /** @var WebVersions $webVersions */
        $webVersions = new $webVersionsClass($this->getConfiguration(), $this->createRequest());
        $webVersionsReflection = new \ReflectionClass($webVersionsClass);

        self::assertTrue($webVersionsReflection->hasProperty('configuration'), $webVersionsClass . ' no more has "configuration" property');
        $configurationProperty = $webVersionsReflection->getProperty('configuration');
        $configurationProperty->setAccessible(true);

        self::assertTrue($webVersionsReflection->hasProperty('request'), $webVersionsClass . ' no more has "request" property');
        $requestProperty = $webVersionsReflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($webVersions, $this->createRequest(null /* no version */));
        $configuration = $this->mockery(Configuration::class);
        $configuration->expects('getWebLastStableMinorVersion')
            ->andReturn('foo.bar.baz');
        $configurationProperty->setValue($webVersions, $configuration);

        self::assertSame('foo.bar.baz', $webVersions->getCurrentMinorVersion());
    }
}