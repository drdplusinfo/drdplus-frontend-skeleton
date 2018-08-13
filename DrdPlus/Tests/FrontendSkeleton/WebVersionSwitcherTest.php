<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\FrontendSkeleton\WebVersionSwitcher;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class WebVersionSwitcherTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_get_index_of_another_version(): void
    {
        $webVersions = new WebVersions($this->createConfiguration());
        $versions = $webVersions->getAllVersions();
        if (!$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertCount(1, $versions, 'Only a single version expected due to a config');

            return;
        }
        self::assertGreaterThan(
            1,
            \count($versions),
            'Expected at least two versions to test, got only ' . \implode(',', $versions)
        );
        $currentWebVersion = $webVersions->getCurrentVersion();
        $rulesVersionSwitcher = new WebVersionSwitcher($webVersions, $this->createDirs());
        $currentIndexFile = $this->getDocumentRoot() . '/index.php';
        self::assertSame(
            $currentIndexFile,
            $rulesVersionSwitcher->getVersionDir($currentWebVersion),
            "'Changing' version to the same should result into current index file"
        );
        $otherVersions = \array_diff($versions, [$currentWebVersion]);
        foreach ($otherVersions as $otherVersion) {
            self::assertNotSame(
                $currentIndexFile,
                $rulesVersionSwitcher->getVersionDir($otherVersion),
                'Changing version should result into different index file'
            );
        }
    }
}