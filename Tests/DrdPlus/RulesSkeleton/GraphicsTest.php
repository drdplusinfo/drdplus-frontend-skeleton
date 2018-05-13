<?php
namespace Tests\DrdPlus\RulesSkeleton;

class GraphicsTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function Licence_page_has_colored_background_image(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/images/rules-full.png');
    }

    /**
     * @test
     */
    public function Rules_page_has_monochrome_background_image(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/images/rules-monochromatic.png');
    }
}