<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

/**
 * @method TestsConfiguration getTestsConfiguration
 */
class ComposerConfigTest extends AbstractContentTest
{
    protected static $composerConfig;

    protected function setUp(): void
    {
        parent::setUp();
        if (static::$composerConfig === null) {
            $composerFilePath = $this->getDocumentRoot() . '/composer.json';
            self::assertFileExists($composerFilePath, 'composer.json has not been found in document root');
            $content = \file_get_contents($composerFilePath);
            self::assertNotEmpty($content, "Nothing has been fetched from $composerFilePath, is readable?");
            static::$composerConfig = \json_decode($content, true /*as array */);
            self::assertNotEmpty(static::$composerConfig, 'Can not decode composer.json content');
        }
    }

    /**
     * @test
     */
    public function Project_is_using_php_of_version_with_nullable_type_hints(): void
    {
        $requiredPhpVersion = static::$composerConfig['require']['php'];
        self::assertGreaterThan(0, \preg_match('~(?<version>\d.+)$~', $requiredPhpVersion, $matches));
        $minimalPhpVersion = $matches['version'];
        self::assertGreaterThanOrEqual(
            0,
            \version_compare($minimalPhpVersion, '7.1'), "Required PHP version should be equal or greater to 7.1, get $requiredPhpVersion"
        );
    }

    /**
     * @test
     */
    public function Assets_have_checked_versions(): void
    {
        $postInstallScripts = static::$composerConfig['scripts']['post-install-cmd'] ?? [];
        self::assertNotEmpty(
            $postInstallScripts,
            'Missing post-install-cmd scripts, expected at least "php ./vendor/bin/assets --css --dir=css"'
        );
        $postUpdateScripts = static::$composerConfig['scripts']['post-update-cmd'] ?? [];
        self::assertNotEmpty(
            $postUpdateScripts,
            'Missing post-update-cmd scripts, expected at least "php ./vendor/bin/assets --css --dir=css"'
        );
        foreach ([$postInstallScripts, $postUpdateScripts] as $postChangeScripts) {
            self::assertContains(
                'php ./vendor/bin/assets --css --dir=css',
                $postChangeScripts,
                'Missing script to compile assets, there are only scripts '
                . \preg_replace('~^Array\n\((.+)\)~', '$1', \var_export($postChangeScripts, true))
            );
        }
    }

    /**
     * @test
     */
    public function Generic_assets_are_hard_copied_from_libraries(): void
    {
        $preAutoloadDump = static::$composerConfig['scripts']['pre-autoload-dump'] ?? [];
        self::assertNotEmpty($preAutoloadDump, 'Missing pre-autoload-dump scripts');
        if ($this->isSkeletonChecked()) {
            self::assertFalse(false, 'Skeleton does not have assets hard copied as it is their creator');

            return;
        }
        foreach (['css', 'js', 'images'] as $assets) {
            self::assertContains(
                "rm -fr ./$assets/generic && cp -r ./vendor/drd-plus/frontend-skeleton/$assets/generic ./$assets/",
                $preAutoloadDump,
                "Missing script to copy $assets assets, there are only scripts "
                . \preg_replace('~^Array\n\((.+)\)~', '$1', \var_export($preAutoloadDump, true))
            );
        }
    }
}