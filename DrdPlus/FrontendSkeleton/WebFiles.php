<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Partials\CurrentMinorVersionProvider;
use Granam\Strict\Object\StrictObject;

/**
 * Gives files to serve on frontend (html, php or md)
 */
class   WebFiles extends StrictObject implements \IteratorAggregate
{
    /** @var Dirs */
    private $dirs;
    /** @var string */
    private $currentVersion;

    public function __construct(Dirs $dirs, CurrentMinorVersionProvider $currentVersionProvider)
    {
        $this->dirs = $dirs;
        $this->currentVersion = $currentVersionProvider->getCurrentMinorVersion();
    }

    /**
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->getSortedWebFileNames());
    }

    private function getSortedWebFileNames(): array
    {
        $htmlFileNames = $this->getUnsortedWebFileNames();

        $sorted = $this->sortFiles($htmlFileNames);

        return $this->extendRelativeToFullPath($sorted);
    }

    /**
     * @return array|string[]
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownWebFilesDir
     */
    private function getUnsortedWebFileNames(): array
    {
        if (!\is_dir($this->getCurrentVersionWebRoot())) {
            throw new Exceptions\UnknownWebFilesDir("Can not read dir '{$this->getCurrentVersionWebRoot()}' for web files");
        }

        return \array_filter(\scandir($this->getCurrentVersionWebRoot(), \SCANDIR_SORT_NONE), function ($file) {
            return $file !== '.' && $file !== '..' && \preg_match('~\.(html|php|md)$~', $file);
        });
    }

    protected function getCurrentVersionWebRoot(): string
    {
        return $this->dirs->getVersionWebRoot($this->currentVersion);
    }

    /**
     * @param array|string[] $fileNames
     * @return array
     */
    private function sortFiles(array $fileNames): array
    {
        \usort($fileNames, function ($firstName, $secondName) {
            $firstNameParts = $this->parseNameParts($firstName);
            $secondNameParts = $this->parseNameParts($secondName);
            if (isset($firstNameParts['page'], $secondNameParts['page'])) {
                if ($firstNameParts['page'] !== $secondNameParts['page']) {
                    return $firstNameParts['page'] < $secondNameParts['page']
                        ? -1
                        : 1;
                }
                $firstNameColumn = '';
                if (isset($firstNameParts['column'])) {
                    $firstNameColumn = $firstNameParts['column'];
                }
                $secondNameColumn = '';
                if (isset($secondNameParts['column'])) {
                    $secondNameColumn = $secondNameParts['column'];
                }
                $columnComparison = \strcmp($firstNameColumn, $secondNameColumn);
                if ($columnComparison !== 0) {
                    return $columnComparison;
                }
                $firstNameOccurrence = 0;
                if (isset($firstNameParts['occurrence'])) {
                    $firstNameOccurrence = $firstNameParts['occurrence'];
                }
                $secondNameOccurrence = 0;
                if (isset($secondNameParts['occurrence'])) {
                    $secondNameOccurrence = $secondNameParts['occurrence'];
                }

                return $secondNameOccurrence - $firstNameOccurrence;
            }

            return 0;
        });

        return $fileNames;
    }

    /**
     * @param string $name
     * @return string[]|array
     */
    private function parseNameParts(string $name): array
    {
        \preg_match('~^(?<page>\d+)(?<column>\w+)?(?<occurrence>\d+)?\s+~', $name, $matches);

        return $matches;
    }

    /**
     * @param array $relativeFileNames
     * @return array|string[]
     */
    private function extendRelativeToFullPath(array $relativeFileNames): array
    {
        return \array_map(
            function ($htmlFile) {
                return $this->getCurrentVersionWebRoot() . '/' . $htmlFile;
            },
            $relativeFileNames
        );
    }
}