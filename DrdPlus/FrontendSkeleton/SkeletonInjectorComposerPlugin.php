<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use Composer\Installer\PackageEvent;

class SkeletonInjectorComposerPlugin extends AbstractSkeletonInjectorComposerPlugin
{
    public const FRONTEND_SKELETON_PACKAGE_NAME = 'drdplus/frontend-skeleton';

    public function __construct()
    {
        parent::__construct(static::FRONTEND_SKELETON_PACKAGE_NAME);
    }

    public function plugInSkeleton(PackageEvent $event)
    {
        if ($this->alreadyInjected || !$this->isThisPackageChanged($event)) {
            return;
        }
        $documentRoot = $GLOBALS['documentRoot'] ?? getcwd();
        $this->io->write("Injecting {$this->skeletonPackageName} using document root $documentRoot");
        $this->publishSkeletonImages($documentRoot);
        $this->publishSkeletonCss($documentRoot);
        $this->publishSkeletonJs($documentRoot);
        $this->flushCache($documentRoot);
        $this->addVersionsToAssets($documentRoot);
        $this->copyGoogleVerification($documentRoot);
        $this->copyPhpUnitConfig($documentRoot);
        $this->copyProjectConfig($documentRoot);
        $this->copyFavicon($documentRoot);
        $this->alreadyInjected = true;
        $this->io->write("Injection of {$this->skeletonPackageName} finished");
    }

    protected function publishSkeletonImages(string $documentRoot): void
    {
        $this->passThrough(
            [
                'rm -f ./images/generic/skeleton/frontend*',
                'cp -r ./vendor/drdplus/frontend-skeleton/images/generic ./images/'
            ],
            $documentRoot
        );
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

    protected function copyGoogleVerification(string $documentRoot): void
    {
        $this->passThrough(['cp ./vendor/drdplus/frontend-skeleton/google8d8724e0c2818dfc.html .'], $documentRoot);
    }

    protected function copyPhpUnitConfig(string $documentRoot): void
    {
        $this->passThrough(['cp ./vendor/drdplus/frontend-skeleton/phpunit.xml.dist .'], $documentRoot);
    }

    protected function copyProjectConfig(string $documentRoot): void
    {
        $this->passThrough(['cp --no-clobber ./vendor/drdplus/frontend-skeleton/config.distribution.yml .'], $documentRoot);
    }

    protected function copyFavicon(string $documentRoot): void
    {
        $this->passThrough(['cp ./vendor/drdplus/frontend-skeleton/favicon.ico .'], $documentRoot);
    }
}