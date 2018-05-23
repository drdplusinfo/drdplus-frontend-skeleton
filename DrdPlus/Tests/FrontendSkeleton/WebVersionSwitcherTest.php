<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\FrontendSkeleton\WebVersionSwitcher;
use DrdPlus\FrontendSkeleton\WebVersionSwitchMutex;

class WebVersionSwitcherTest extends SkeletonTestCase
{
    private $currentWebVersion;

    protected function setUp()
    {
        parent::setUp();
        if ($this->areThereUncommittedChanges()) {
            self::markTestSkipped('There are uncommitted changes, so can not test version switching');
        }
        $this->currentWebVersion = (new WebVersions(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST)))->getCurrentVersion();
    }

    protected function areThereUncommittedChanges(): bool
    {
        \exec('git diff', $changedRows, $output);
        self::assertSame(0, $output, 'Failed executing `git diff`');

        return \count($changedRows) > 0;
    }

    protected function tearDown()
    {
        $webVersionSwitchMutex = new WebVersionSwitchMutex();
        $webVersionSwitcher = new WebVersionSwitcher(
            new WebVersions(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST)),
            $webVersionSwitchMutex
        );
        $webVersionSwitcher->switchToVersion($this->currentWebVersion);
        $webVersionSwitchMutex->unlock(); // we need to unlock it as it is NOT unlocked by itself (intentionally)
        parent::tearDown();
    }

    /**
     * @test
     */
    public function I_can_switch_to_another_version(): void
    {
        $webVersions = new WebVersions(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST));
        $versions = $webVersions->getAllWebVersions();
        if ($this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertCount(1, $versions, 'Only a single version expected due to a config');
        }
        self::assertGreaterThan(
            1,
            \count($versions),
            'Expected at least two versions to test, got only ' . \implode($versions)
        );
        $versionSwitchMutex = new WebVersionSwitchMutex();
        $rulesVersionSwitcher = new WebVersionSwitcher($webVersions, $versionSwitchMutex);
        self::assertFalse(
            $rulesVersionSwitcher->switchToVersion($this->currentWebVersion),
            'Changing version to the same should result into false as nothing changed'
        );
        $versionSwitchMutex->unlock(); // we need to unlock it as it is NOT unlocked by itself (intentionally)
        $otherVersions = \array_diff($versions, [$this->currentWebVersion]);
        foreach ($otherVersions as $otherVersion) {
            self::assertTrue(
                $rulesVersionSwitcher->switchToVersion($otherVersion),
                'Changing version should result into true as changed'
            );
            /** @noinspection DisconnectedForeachInstructionInspection */
            $versionSwitchMutex->unlock(); // we need to unlock it as it is NOT unlocked by itself (intentionally)
        }
    }
}
