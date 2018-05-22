<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
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
        if (\defined('JUST_TEXT_TESTING') && JUST_TEXT_TESTING) {
            self::assertCount(0, $allTables);

            return;
        }
        self::assertGreaterThan(0, \count($allTables));
        self::assertEmpty($htmlHelper->findTablesWithIds($this->getHtmlDocument(), ['nonExistingTableId']));
        foreach ($this->getSomeExpectedTableIds() as $someExpectedTableId) {
            $lowerExpectedTableId = \strtolower($someExpectedTableId);
            self::assertArrayHasKey($lowerExpectedTableId, $allTables);
            $expectedTable = $allTables[$lowerExpectedTableId];
            self::assertInstanceOf(Element::class, $expectedTable);
            self::assertNotEmpty($expectedTable->innerHTML, "Table of ID $someExpectedTableId is empty");
            $singleTable = $htmlHelper->findTablesWithIds($this->getHtmlDocument(), [$someExpectedTableId]);
            self::assertCount(1, $singleTable);
            self::assertArrayHasKey($lowerExpectedTableId, $allTables, 'ID is expected to be lower-cased');
        }
    }

    protected function getSomeExpectedTableIds(): array
    {
        return \defined('SOME_EXPECTED_TABLE_IDS') ? (array)\SOME_EXPECTED_TABLE_IDS : ['IAmSoAlone'];
    }

    /**
     * @test
     */
    public function Same_table_ids_are_filtered_on_tables_only_mode(): void
    {
        $htmlHelper = HtmlHelper::createFromGlobals($this->getDocumentRoot());
        $tableIds = $this->getSomeExpectedTableIds();
        if (\count($tableIds) === 0) {
            self::assertFalse(false, 'No tables expected');
        }
        $tableId = \current($tableIds);
        $tables = $htmlHelper->findTablesWithIds($this->getHtmlDocument(), [$tableId, $tableId]);
        self::assertCount(1, $tables);
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\DuplicatedRequiredTableId
     * @expectedExceptionMessageRegExp ~IAmSoAlone~
     */
    public function I_can_not_request_tables_with_same_ids_if_lower_cased(): void
    {
        $htmlHelper = HtmlHelper::createFromGlobals($this->getDocumentRoot());
        $htmlHelper->findTablesWithIds($this->getHtmlDocument(), ['IAmSoAlone', 'iamsoalone']);
    }
}