<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
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
            'post-autoload-dump' => 'addVersionsToAssets',
        ];
    }

    public function addVersionsToAssets()
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }
}