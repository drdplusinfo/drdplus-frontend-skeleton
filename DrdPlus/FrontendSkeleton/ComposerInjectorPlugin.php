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
            PackageEvents::POST_PACKAGE_INSTALL => 'addVersionsToAssets',
            PackageEvents::POST_PACKAGE_UPDATE => 'addVersionsToAssets',
        ];
    }

    public function addVersionsToAssets()
    {
        $documentRoot = $GLOBALS['documentRoot'] ?? getcwd();
        $assetsVersion = new AssetsVersion(true, false);
        $assetsVersion->addVersionsToAssetLinks($documentRoot, ['css'], [], [], false);
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }
}