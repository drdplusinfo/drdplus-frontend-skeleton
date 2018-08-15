<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ComposerInjectorPlugin implements PluginInterface, EventSubscriberInterface
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

    public function plugInSkeleton()
    {
        $documentRoot = $GLOBALS['documentRoot'] ?? getcwd();
        $this->addVersionsToAssets($documentRoot);
    }

    private function addVersionsToAssets(string $documentRoot)
    {
        $assetsVersion = new AssetsVersion(true, false);
        $changedFiles = $assetsVersion->addVersionsToAssetLinks($documentRoot, ['css'], [], [], false);
        if ($changedFiles) {
            $this->io->write('Those assets got versions to asset links: ' . \implode(', ', $changedFiles));
        }
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }
}