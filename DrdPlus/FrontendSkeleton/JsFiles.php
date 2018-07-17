<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

class JsFiles extends AbstractPublicFiles
{
    /**
     * @var string
     */
    private $dirWithJs;

    public function __construct(string $dirWithJs)
    {
        $this->dirWithJs = \rtrim($dirWithJs, '\/');
    }

    /**
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->scanForJsFiles($this->dirWithJs));
    }

    /**
     * @param string $directory
     * @param string $jsRelativeRoot
     * @return array|string[]
     */
    private function scanForJsFiles(string $directory, string $jsRelativeRoot = ''): array
    {
        if (!\is_dir($directory)) {
            return [];
        }
        $genericJsFiles = [];
        $vendorJsFiles = [];
        $jsFiles = [];
        $jsRelativeRoot = \rtrim($jsRelativeRoot, '\/');
        foreach (\scandir($directory, \SCANDIR_SORT_NONE) as $folder) {
            $folderPath = $directory . '/' . $folder;
            if (\is_dir($folderPath)) {
                if ($folder === '.' || $folder === '..' || $folder === '.gitignore') {
                    continue;
                }
                $jsFilesFromDir = $this->scanForJsFiles(
                    $folderPath,
                    ($jsRelativeRoot !== '' ? ($jsRelativeRoot . '/') : '') . $folder
                );
                if ($folder === 'generic') {
                    foreach ($jsFilesFromDir as $jsFileFromDir) {
                        $genericJsFiles[] = $jsFileFromDir;
                    }
                } elseif ($folder === 'vendor') {
                    foreach ($jsFilesFromDir as $jsFileFromDir) {
                        $vendorJsFiles[] = $jsFileFromDir;
                    }
                } else {
                    foreach ($jsFilesFromDir as $jsFileFromDir) {
                        $jsFiles[] = $jsFileFromDir;
                    }
                }
            } elseif (\is_file($folderPath) && \strpos($folder, '.js') !== false) {
                $jsFiles[] = ($jsRelativeRoot !== '' ? ($jsRelativeRoot . '/') : '') . $folder; // intentionally relative path
            }
        }

        \usort($vendorJsFiles, function (string $someJs, string $anotherJs) {
            if (\strpos($someJs, 'jquery') !== false) {
                return -1;
            }
            if (\strpos($anotherJs, 'jquery') !== false) {
                return 1;
            }
            if (\strpos($someJs, 'bootstrap') !== false) {
                return -1;
            }
            if (\strpos($anotherJs, 'bootstrap') !== false) {
                return 1;
            }

            return $someJs <=> $anotherJs;
        });

        return \array_merge($vendorJsFiles, $genericJsFiles, $jsFiles); // vendor first, generic second, custom last
    }
}