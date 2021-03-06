<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\AssetsVersion;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class AssetsVersionTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function All_css_files_have_versioned_assets(): void
    {
        $assetsVersionClass = static::getSutClass();
        /** @var AssetsVersion $assetsVersion */
        $assetsVersion = new $assetsVersionClass(true /* scan for CSS */);
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
            . "\ntranspile them:\nphp ./vendor/bin/assets --css --dir=css"
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
        $binAssetsEscaped = \escapeshellarg($this->getBinAssetsFile());
        $output = $this->runCommand("php $binAssetsEscaped");
        self::assertNotEmpty($output);
        self::assertStringStartsWith('Options are', $output[0]);
    }

    /**
     * @test
     */
    public function I_can_run_script_for_cli_assets_control(): void
    {
        $filePermissions = \fileperms($this->getBinAssetsFile());
        $inOctal = \decoct($filePermissions & 0777);
        self::assertSame(
            '775',
            $inOctal,
            "Expected {$this->getBinAssetsFile()} to has executable permissions 0775 as Composer will do that anyway later on this library installation"
        );
    }
}