<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

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
        self::assertFileExists($this->getDocumentRoot() . '/images/generic/skeleton/frontend-background.png');
    }
}