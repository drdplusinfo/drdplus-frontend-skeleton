<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\AssetsVersion;

class AssetsVersionTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function All_css_files_have_versioned_assets(): void
    {
        $assetsVersion = new AssetsVersion(true /* scan for CSS */);
        $changedFiles = $assetsVersion->addVersionsToAssetLinks(
            $this->getDocumentRoot(),
            [$this->getDocumentRoot() . '/css'],
            [],
            [],
            true // dry run
        );
        self::assertCount(
            0,
            $changedFiles,
            "Expected all CSS files already transpiled to have versioned links to assets, but those are not: \n"
            . \implode("\n", $changedFiles)
            . "\ntranspile them:\nphp {$this->getBinAssetsFile()} --css --dir=css"
        );
    }

    protected function getBinAssetsFile(): string
    {
        $assetsFile = $this->getVendorRoot() . '/bin/assets';
        if (!\file_exists($assetsFile)) {
            $assetsFile = $this->getDocumentRoot() . '/bin/assets';
        }
        if (!\file_exists($assetsFile)) {
            throw new \LogicException('Can not find bin/assets file');
        }

        return $assetsFile;
    }

    /**
     * @test
     */
    public function I_can_use_helper_script(): void
    {
        $binAssets = $this->getBinAssetsFile();
        \exec("php $binAssets", $output, $result);
        self::assertSame(0, $result, 'Can not call ' . $binAssets);
        self::assertNotEmpty($output);
        self::assertStringStartsWith('Options are', $output[0]);
    }
}