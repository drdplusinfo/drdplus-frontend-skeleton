<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\SkeletonInjectorComposerPlugin;
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
    public function Assets_have_injected_versions(): void
    {
        if (!$this->isFrontendSkeletonChecked()) {
            self::assertFalse(false, 'Assets versions are injected by ' . SkeletonInjectorComposerPlugin::class);

            return;
        }
        $postInstallScripts = static::$composerConfig['scripts']['post-install-cmd'] ?? [];
        self::assertNotEmpty(
            $postInstallScripts,
            'Missing post-install-cmd scripts, expected at least "php bin/assets --css --dir=css"'
        );
        $postUpdateScripts = static::$composerConfig['scripts']['post-update-cmd'] ?? [];
        self::assertNotEmpty(
            $postUpdateScripts,
            'Missing post-update-cmd scripts, expected at least "php bin/assets --css --dir=css"'
        );
        foreach ([$postInstallScripts, $postUpdateScripts] as $postChangeScripts) {
            self::assertContains(
                'php bin/assets --css --dir=css',
                $postChangeScripts,
                'Missing script to compile assets, there are only scripts '
                . \preg_replace('~^Array\n\((.+)\)~', '$1', \var_export($postChangeScripts, true))
            );
        }
    }

    /**
     * @test
     */
    public function Package_is_injected(): void
    {
        if (!$this->isSkeletonChecked()) {
            self::assertFalse(false, 'Intended for skeleton only');

            return;
        }
        self::assertSame('composer-plugin', static::$composerConfig['type']);
        self::assertSame(SkeletonInjectorComposerPlugin::class, static::$composerConfig['extra']['class']);
    }
}