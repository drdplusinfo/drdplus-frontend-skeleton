<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
use Granam\String\StringTools;
use Gt\Dom\Element;

class HtmlHelperTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_find_out_if_I_am_in_production(): void
    {
        self::assertFalse(HtmlHelper::createFromGlobals($this->getDocumentRoot())->isInProduction());
        // there is no way how to change PHP_SAPI constant value
    }

    /**
     * @test
     */
    public function I_can_get_filtered_tables_from_content(): void
    {
        $htmlHelper = HtmlHelper::createFromGlobals($this->getDocumentRoot());

        $allTables = $htmlHelper->findTablesWithIds($this->getHtmlDocument());
        if (!$this->getTestsConfiguration()->hasTables()) {
            self::assertCount(0, $allTables);

            return;
        }
        self::assertGreaterThan(0, \count($allTables));
        self::assertEmpty($htmlHelper->findTablesWithIds($this->getHtmlDocument(), ['nonExistingTableId']));
        $someExpectedTableIds = $this->getTestsConfiguration()->getSomeExpectedTableIds();
        if (!$this->getTestsConfiguration()->hasTables()) {
            self::assertCount(0, $someExpectedTableIds, 'No tables expected');

            return;
        }
        self::assertGreaterThan(0, \count($someExpectedTableIds), 'Some tables expected');
        foreach ($someExpectedTableIds as $someExpectedTableId) {
            $lowerExpectedTableId = StringTools::toConstantLikeValue(StringTools::camelCaseToSnakeCase($someExpectedTableId));
            self::assertArrayHasKey($lowerExpectedTableId, $allTables);
            $expectedTable = $allTables[$lowerExpectedTableId];
            self::assertInstanceOf(Element::class, $expectedTable);
            self::assertNotEmpty($expectedTable->innerHTML, "Table of ID $someExpectedTableId is empty");
            $singleTable = $htmlHelper->findTablesWithIds($this->getHtmlDocument(), [$someExpectedTableId]);
            self::assertCount(1, $singleTable);
            self::assertArrayHasKey($lowerExpectedTableId, $allTables, 'ID is expected to be lower-cased');
        }
    }

    /**
     * @test
     */
    public function Same_table_ids_are_filtered_on_tables_only_mode(): void
    {
        if (!$this->getTestsConfiguration()->hasTables()) {
            self::assertCount(
                0,
                $this->getHtmlDocument()->getElementsByTagName('table'),
                'No tables with IDs expected according to tests config'
            );

            return;
        }
        $htmlHelper = HtmlHelper::createFromGlobals($this->getDocumentRoot());
        $someExpectedTableIds = $this->getTestsConfiguration()->getSomeExpectedTableIds();
        self::assertGreaterThan(0, \count($someExpectedTableIds), 'Some tables expected according to tests config');
        $tableId = \current($someExpectedTableIds);
        $tables = $htmlHelper->findTablesWithIds($this->getHtmlDocument(), [$tableId, $tableId]);
        self::assertCount(1, $tables);
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\DuplicatedRequiredTableId
     * @expectedExceptionMessageRegExp ~IAmSoAlone~
     */
    public function I_can_not_request_tables_with_ids_with_same_id_like_representation(): void
    {
        $htmlHelper = HtmlHelper::createFromGlobals($this->getDocumentRoot());
        $htmlHelper->findTablesWithIds($this->getHtmlDocument(), ['IAmSoAlone', 'iAmSóAlóne']);
    }
}