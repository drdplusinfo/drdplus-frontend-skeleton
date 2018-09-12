<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Granam\String\StringTools;

class TablesTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_get_tables_only(): void
    {
        $withTables = $this->getHtmlDocument([Request::TABLES => '' /* all of them */]);
        $body = $withTables->getElementsByTagName('body')[0];
        $tables = $body->getElementsByTagName('table');
        if (!$this->isSkeletonChecked() && !$this->getTestsConfiguration()->hasTables()) {
            self::assertCount(0, $tables, 'No tables expected due to tests configuration');
        } else {
            self::assertGreaterThan(0, \count($tables), 'Expected some tables');
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
            $tableId = StringTools::toConstantLikeValue(StringTools::camelCaseToSnakeCase($tableId));
            self::assertNotNull(
                $htmlDocument->getElementById(StringTools::toConstantLikeValue($tableId)), 'Missing table of ID ' . $tableId
            );
        }
    }
}