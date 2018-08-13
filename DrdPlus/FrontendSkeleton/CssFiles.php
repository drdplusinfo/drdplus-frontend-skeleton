<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Partials\AbstractPublicFiles;

class CssFiles extends AbstractPublicFiles
{
    /**
     * @var string
     */
    private $cssRoot;

    public function __construct(bool $preferMinified, Dirs $dirs)
    {
        parent::__construct($preferMinified);
        $this->cssRoot = rtrim($dirs->getCssRoot(), '\/');
    }

    /**
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->scanForCssFiles($this->cssRoot));
    }

    /**
     * @param string $directory
     * @param string $cssRelativeRoot
     * @param int $level
     * @return array|string[]|string[][]
     */
    private function scanForCssFiles(string $directory, string $cssRelativeRoot = '', int $level = 1): array
    {
        $cssRelativeRoot = \rtrim($cssRelativeRoot, '\/');
        /** @var array|string[][] $cssFiles */
        $cssFiles = [];
        foreach (\scandir($directory, SCANDIR_SORT_NONE) as $folder) {
            if ($folder === '.' || $folder === '..' || $folder === '.gitignore') {
                continue;
            }
            $folderPath = $directory . '/' . $folder;
            if (\is_dir($folderPath)) {
                if ($folder === 'ignore') {
                    continue;
                }
                $anotherCssFiles = $this->scanForCssFiles(
                    $folderPath,
                    ($cssRelativeRoot !== '' ? ($cssRelativeRoot . '/') : '') . $folder,
                    $level + 1
                );
                foreach ($anotherCssFiles as $iteratedLevel => $sameLevelAnotherCssFiles) {
                    /** @var array $sameLevelAnotherCssFiles */
                    foreach ($sameLevelAnotherCssFiles as $sameLevelAnotherCssFile) {
                        $cssFiles[$iteratedLevel][] = $sameLevelAnotherCssFile;
                    }
                }
            } elseif (\is_file($folderPath) && \preg_match('~[.]css$~', $folder)) {
                $cssFiles[$level][] = ($cssRelativeRoot !== '' ? ($cssRelativeRoot . '/') : '') . $folder; // intentionally relative path
            }
        }
        if ($level > 1) {
            return $cssFiles;
        }
        \krsort($cssFiles); // deeper means more generic and goes first
        $flattenedCss = [];
        foreach ($cssFiles as $sameLevelCssFiles) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $flattenedCss = \array_merge($flattenedCss, $sameLevelCssFiles); // deeper files can be overloaded by shallow ones
        }
        $flattenedCss = $this->removeMapFiles($flattenedCss);
        $flattenedCss = $this->filterUniqueFiles($flattenedCss);

        return $flattenedCss;
    }
}