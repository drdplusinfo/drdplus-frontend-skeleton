<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class SkeletonInjectorComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    private $composer;
    /** @var IOInterface */
    private $io;

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'plugInSkeleton',
            PackageEvents::POST_PACKAGE_UPDATE => 'plugInSkeleton',
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function plugInSkeleton()
    {
        $documentRoot = $GLOBALS['documentRoot'] ?? getcwd();
        $this->io->write('Injecting drd-plus/frontend-skeleton using document root ' . $documentRoot);
        $this->publishSkeletonImages($documentRoot);
        $this->publishSkeletonCss($documentRoot);
        $this->publishSkeletonJs($documentRoot);
        $this->flushCache($documentRoot);
        $this->addVersionsToAssets($documentRoot);
        $this->copyGoogleVerification($documentRoot);
        $this->copyPhpUnitConfig($documentRoot);
        $this->io->write('Injection of drd-plus/frontend-skeleton finished');
    }

    private function addVersionsToAssets(string $documentRoot)
    {
        $assetsVersion = new AssetsVersion(true, false);
        $changedFiles = $assetsVersion->addVersionsToAssetLinks($documentRoot, ['css'], [], [], false);
        if ($changedFiles) {
            $this->io->write('Those assets got versions to asset links: ' . \implode(', ', $changedFiles));
        }
    }

    private function publishSkeletonImages(string $documentRoot)
    {
        $this->passThrough(
            [
                'rm -f ./images/generic/skeleton/frontend*',
                'cp -r ./vendor/drd-plus/frontend-skeleton/images/generic ./images/'
            ],
            $documentRoot
        );
    }

    private function passThrough(array $commands, string $workingDir = null): void
    {
        if ($workingDir !== null) {
            $escapedWorkingDir = \escapeshellarg($workingDir);
            \array_unshift($commands, 'cd ' . $escapedWorkingDir);
        }
        foreach ($commands as &$command) {
            $command .= ' 2>&1';
        }
        unset($command);
        $chain = \implode(' && ', $commands);
        \exec($chain, $output, $returnCode);
        if ($returnCode !== 0) {
            $this->io->writeError(
                "Failed injecting skeleton by command $chain\nGot return code $returnCode and output " . \implode("\n", $output)
            );

            return;
        }
        $this->io->write($chain);
        if ($output) {
            $this->io->write(' ' . \implode("\n", $output));
        }
    }

    private function publishSkeletonCss(string $documentRoot): void
    {
        $this->passThrough(
            [
                'rm -f ./css/generic/skeleton/frontend*',
                'rm -fr ./css/generic/skeleton/vendor/frontend',
                'cp -r ./vendor/drd-plus/frontend-skeleton/css/generic ./css/',
                'chmod -R g+w ./css/generic/skeleton/vendor/frontend'
            ],
            $documentRoot
        );
    }

    private function publishSkeletonJs(string $documentRoot): void
    {
        $this->passThrough(
            [
                'rm -f ./js/generic/skeleton/frontend*',
                'rm -fr ./js/generic/skeleton/vendor/frontend',
                'cp -r ./vendor/drd-plus/frontend-skeleton/js/generic ./js/',
                'chmod -R g+w ./js/generic/skeleton/vendor/frontend'
            ],
            $documentRoot
        );
    }

    private function flushCache(string $documentRoot): void
    {
        $this->passThrough(['find ./cache -mindepth 2 -type f -exec rm {} +'], $documentRoot);
    }

    private function copyGoogleVerification(string $documentRoot)
    {
        $this->passThrough(['cp ./vendor/drd-plus/frontend-skeleton/google8d8724e0c2818dfc.html .'], $documentRoot);
    }

    private function copyPhpUnitConfig(string $documentRoot)
    {
        $this->passThrough(['cp ./vendor/drd-plus/frontend-skeleton/phpunit.xml.dist .'], $documentRoot);
    }
}