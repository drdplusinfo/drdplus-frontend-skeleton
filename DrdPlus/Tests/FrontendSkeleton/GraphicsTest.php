<?php
namespace DrdPlus\Tests\FrontendSkeleton;

class GraphicsTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function Main_page_has_monochrome_background_image(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/images/main-background.png');
    }

    /**
     * @test
     */
    public function Licence_page_has_monochrome_background_image(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/images/licence-background.png');
    }
}