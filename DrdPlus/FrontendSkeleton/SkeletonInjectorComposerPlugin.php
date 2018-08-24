<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class SkeletonInjectorComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public const FRONTEND_SKELETON_PACKAGE_NAME = 'drdplus/frontend-skeleton';
    /** @var Composer */
    protected $composer;
    /** @var IOInterface */
    protected $io;
    /** @var bool */
    protected $alreadyInjected = false;

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

    public function plugInSkeleton(PackageEvent $event)
    {
        if ($this->alreadyInjected || !$this->isThisPackageChanged($event)) {
            return;
        }
        $skeletonPackageName = $this->composer->getPackage()->getName();
        $documentRoot = $GLOBALS['documentRoot'] ?? getcwd();
        $this->io->write("Injecting $skeletonPackageName using document root $documentRoot");
        $this->publishSkeletonImages($documentRoot);
        $this->publishSkeletonCss($documentRoot);
        $this->publishSkeletonJs($documentRoot);
        $this->flushCache($documentRoot);
        $this->addVersionsToAssets($documentRoot);
        $this->copyGoogleVerification($documentRoot);
        $this->copyPhpUnitConfig($documentRoot);
        $this->copyProjectConfig($documentRoot);
        $this->alreadyInjected = true;
        $this->io->write("Injection of $skeletonPackageName finished");
    }

    protected function isThisPackageChanged(PackageEvent $event): bool
    {
        /** @var InstallOperation|UpdateOperation $operation */
        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $changedPackageName = $operation->getPackage()->getName();
        } elseif ($operation instanceof UpdateOperation) {
            $changedPackageName = $operation->getInitialPackage()->getName();
        } else {
            return false;
        }

        return $this->isChangedPackageThisOne($changedPackageName);
    }

    protected function isChangedPackageThisOne(string $changedPackageName): bool
    {
        return $changedPackageName === static::FRONTEND_SKELETON_PACKAGE_NAME;
    }

    protected function shouldSkipFile(string $fileName): bool
    {
        return \in_array($fileName, $this->composer->getConfig()->get('frontend-skeleton')['skip-injecting'] ?? [], true);
    }

    protected function addVersionsToAssets(string $documentRoot)
    {
        $assetsVersion = new AssetsVersion(true, false);
        $changedFiles = $assetsVersion->addVersionsToAssetLinks($documentRoot, ['css'], [], [], false);
        if ($changedFiles) {
            $this->io->write('Those assets got versions to asset links: ' . \implode(', ', $changedFiles));
        }
    }

    protected function publishSkeletonImages(string $documentRoot)
    {
        $this->passThrough(
            [
                'rm -f ./images/generic/skeleton/frontend*',
                'cp -r ./vendor/drdplus/frontend-skeleton/images/generic ./images/'
            ],
            $documentRoot
        );
    }

    protected function passThrough(array $commands, string $workingDir = null): void
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

    protected function publishSkeletonCss(string $documentRoot): void
    {
        $this->passThrough(
            [
                'rm -f ./css/generic/skeleton/frontend*',
                'rm -fr ./css/generic/skeleton/vendor/frontend',
                'cp -r ./vendor/drdplus/frontend-skeleton/css/generic ./css/',
                'chmod -R g+w ./css/generic/skeleton/vendor/frontend'
            ],
            $documentRoot
        );
    }

    protected function publishSkeletonJs(string $documentRoot): void
    {
        $this->passThrough(
            [
                'rm -f ./js/generic/skeleton/frontend*',
                'rm -fr ./js/generic/skeleton/vendor/frontend',
                'cp -r ./vendor/drdplus/frontend-skeleton/js/generic ./js/',
                'chmod -R g+w ./js/generic/skeleton/vendor/frontend'
            ],
            $documentRoot
        );
    }

    protected function flushCache(string $documentRoot): void
    {
        $this->passThrough(['find ./cache -mindepth 2 -type f -exec rm {} +'], $documentRoot);
    }

    protected function copyGoogleVerification(string $documentRoot)
    {
        $this->passThrough(['cp ./vendor/drdplus/frontend-skeleton/google8d8724e0c2818dfc.html .'], $documentRoot);
    }

    protected function copyPhpUnitConfig(string $documentRoot)
    {
        if ($this->shouldSkipFile('phpunit.xml.dist')) {
            $this->io->write('Skipping phpunit.xml.dist');
        } else {
            $this->passThrough(['cp ./vendor/drdplus/frontend-skeleton/phpunit.xml.dist .'], $documentRoot);
        }
    }

    protected function copyProjectConfig(string $documentRoot)
    {
        $this->passThrough(['cp --no-clobber ./vendor/drdplus/frontend-skeleton/config.distribution.yml .'], $documentRoot);
    }
}