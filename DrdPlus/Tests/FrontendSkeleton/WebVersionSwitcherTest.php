<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\FrontendSkeleton\WebVersionSwitcher;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class WebVersionSwitcherTest extends AbstractContentTest
{
    private $currentWebVersion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currentWebVersion = (new WebVersions(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST)))->getCurrentVersion();
    }

    /**
     * @test
     */
    public function I_can_switch_to_another_version(): void
    {
        $webVersions = new WebVersions(\dirname(DRD_PLUS_INDEX_FILE_NAME_TO_TEST));
        $versions = $webVersions->getAllWebVersions();
        if (!$this->getTestsConfiguration()->hasMoreVersions()) {
            self::assertCount(1, $versions, 'Only a single version expected due to a config');

            return;
        }
        self::assertGreaterThan(
            1,
            \count($versions),
            'Expected at least two versions to test, got only ' . \implode(',', $versions)
        );
        self::assertSame($this->currentWebVersion, $webVersions->getCurrentVersion());
        $rulesVersionSwitcher = new WebVersionSwitcher($webVersions, $this->getDocumentRoot(), $this->getDirForVersions());
        $currentIndexFile = $this->getDocumentRoot() . '/index.php';
        self::assertSame(
            $currentIndexFile,
            $rulesVersionSwitcher->getVersionIndexFile($this->currentWebVersion),
            "'Changing' version to the same should result into current index file"
        );
        $otherVersions = \array_diff($versions, [$this->currentWebVersion]);
        foreach ($otherVersions as $otherVersion) {
            self::assertNotSame(
                $currentIndexFile,
                $rulesVersionSwitcher->getVersionIndexFile($otherVersion),
                'Changing version should result into different index file'
            );
        }
    }
}