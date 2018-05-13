<?php
namespace Tests\DrdPlus\RulesSkeleton;

class SourceCodeLinksTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_follow_linked_source_code(): void
    {
        $sourceUrls = $this->getSourceUrls();
        if (\count($sourceUrls) === 0) {
            self::assertFalse(false, 'Nothing to test here');

            return;
        }
        foreach ($sourceUrls as $sourceUrl) {
            $localFile = $this->toLocalPath($sourceUrl);
            $toLocalFile = '';
            foreach (\explode('/', $localFile) as $filePart) {
                if ($filePart === '') {
                    continue;
                }
                if (!\file_exists($toLocalFile . '/' . $filePart)) {
                    self::fail(
                        "Dir or file '$filePart' does not exists in dir '$toLocalFile' (was looking for $localFile linked by $sourceUrl)"
                    );
                }
                $toLocalFile .= '/' . $filePart;
            }
            self::assertFileExists($localFile, \preg_replace('~^.+\.\./~', '', $localFile));
        }
    }

    /**
     * @return array|string[]
     */
    private function getSourceUrls(): array
    {
        $sourceUrls = [];
        foreach ($this->parseSourceUrls($this->getRulesContentForDev()) as $sourceUrl) {
            $sourceUrls[] = $sourceUrl;
        }

        return $sourceUrls;
    }

    /**
     * @param string $html
     * @return array|string[]
     */
    private function parseSourceUrls(string $html): array
    {
        \preg_match_all('~data-source-code="(?<links>[^"]+)"~', $html, $matches);

        return $matches['links'];
    }

    /**
     * @param string $link like
     *     https://github.com/jaroslavtyc/drd-plus-professions/blob/master/DrdPlus/Professions/Priest.php
     * @return string
     */
    private function toLocalPath(string $link): string
    {
        $withoutWebRoot = \str_replace('https://github.com/jaroslavtyc/', '', $link);
        $withoutGithubSpecifics = \preg_replace('~(?<type>blob|tree)/master/~', '', $withoutWebRoot);
        $withLocalSubDirs = \preg_replace_callback(
            '~^drd-(?:plus-)?(?<projectName>[^/]+)~',
            function (array $matches) {
                return 'drd-plus/' . $matches['projectName'];
            },
            $withoutGithubSpecifics
        );
        $localProjectsRootDir = '/home/jaroslav/Projects';

        $localPath = $localProjectsRootDir . '/' . $withLocalSubDirs;
        if (\file_exists($localPath) && \preg_match('~(?<type>blob|tree)/master/~', $withoutWebRoot, $matches)) {
            if (\is_file($localPath)) {
                self::assertSame('blob', $matches['type'], "File $localPath should be linked as blob, not " . $matches['type']);
            } else if (\is_dir($localPath)) {
                self::assertSame('tree', $matches['type'], "Dir $localPath should be linked as tree, not " . $matches['type']);
            }
        }

        return $localPath;
    }
}