<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class TablesTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_get_tables_only(): void
    {
        $htmlDocument = $this->getHtmlDocument([Request::TABLES => '' /* all of them */]);
        $tables = $htmlDocument->body->getElementsByTagName('table');
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasTables()) {
            self::assertCount(0, $tables, 'No tables expected due to tests configuration');
        } else {
            self::assertGreaterThan(0, \count($tables), 'Expected some tables');
        }
        $this->There_is_no_other_content_than_tables($htmlDocument);
    }

    protected function There_is_no_other_content_than_tables(HtmlDocument $htmlDocument): void
    {
        $menu = $htmlDocument->getElementById(HtmlHelper::MENU_ID);
        $menu->remove();
        foreach ($htmlDocument->getElementsByClassName(HtmlHelper::INVISIBLE_ID_CLASS) as $invisible) {
            $invisible->remove();
        }
        foreach ($htmlDocument->getElementsByClassName(HtmlHelper::INVISIBLE_CLASS) as $invisible) {
            $invisible->remove();
        }
        foreach ($htmlDocument->body->children as $child) {
            self::assertSame(
                'table',
                $child->tagName,
                'Expected only tables, seems tables content filter does not work at all'
            );
        }
    }

    /**
     * @test
     */
    public function I_can_get_wanted_tables_from_content(): void
    {
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasTables()) {
            self::assertFalse(false, 'Disabled by tests configuration');

            return;
        }
        $implodedTables = \implode(',', $this->getTestsConfiguration()->getSomeExpectedTableIds());
        $htmlDocument = $this->getHtmlDocument([Request::TABLES => $implodedTables]);
        $tables = $htmlDocument->body->getElementsByTagName('table');
        self::assertNotEmpty($tables, 'No tables have been fetched, when required IDs ' . $implodedTables);
        foreach ($this->getTestsConfiguration()->getSomeExpectedTableIds() as $tableId) {
            self::assertNotNull(
                $htmlDocument->getElementById(HtmlHelper::toId($tableId)), 'Missing table of ID ' . $tableId
            );
        }
        $this->There_is_no_other_content_than_tables($htmlDocument);
    }
}