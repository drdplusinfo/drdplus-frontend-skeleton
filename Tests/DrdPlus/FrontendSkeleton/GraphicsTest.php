<?php
namespace Tests\DrdPlus\FrontendSkeleton;

class GraphicsTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function Page_has_monochrome_background_image(): void
    {
        self::assertFileExists($this->getDocumentRoot() . '/images/main-background.png');
    }
}