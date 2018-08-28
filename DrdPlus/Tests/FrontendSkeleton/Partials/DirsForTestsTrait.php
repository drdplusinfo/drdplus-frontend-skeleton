<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use DrdPlus\FrontendSkeleton\Dirs;

trait DirsForTestsTrait
{
    protected function createDirs(string $documentRoot = null): Dirs
    {
        return new Dirs($documentRoot ?? $this->getDocumentRoot());
    }

    protected function getDocumentRoot(): string
    {
        static $masterDocumentRoot;
        if ($masterDocumentRoot === null) {
            $masterDocumentRoot = \dirname(\DRD_PLUS_INDEX_FILE_NAME_TO_TEST);
        }

        return $masterDocumentRoot;
    }

    protected function getDirForVersions(): string
    {
        return $this->getDocumentRoot() . '/versions';
    }

    protected function getVendorRoot(): string
    {
        return $this->getDocumentRoot() . '/vendor';
    }

    protected function getPartsRoot(): string
    {
        return $this->getDocumentRoot() . '/parts';
    }

    protected function getGenericPartsRoot(): string
    {
        return __DIR__ . '/../../../../parts/frontend-skeleton';
    }

    protected function unifyPath(string $path): string
    {
        $path = \str_replace('\\', '/', $path);
        $path = \preg_replace('~/\.(?:/|$)~', '/', $path);

        return $this->squashTwoDots($path);
    }

    private function squashTwoDots(string $path): string
    {
        $originalPath = $path;
        $path = \preg_replace('~/[^/.]+/\.\.~', '', $path);
        if ($originalPath === $path) {
            return $originalPath; // nothing has been squashed
        }

        return $this->squashTwoDots($path);
    }
}