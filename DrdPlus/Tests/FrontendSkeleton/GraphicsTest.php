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
    public function Main_page_uses_generic_image_for_background(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/images/generic/frontend-skeleton/background.png');
    }
}