<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Controller;

class ControllerTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_get_page_title(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertSame($this->getTestsConfiguration()->getExpectedPageTitle(), $controller->getPageTitle());
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\MissingFileWithPageName
     * @expectedExceptionMessageRegExp ~'Not from this world/name[.]txt'~
     */
    public function I_can_not_get_page_title_if_text_file_with_its_name_does_not_exist(): void
    {
        $controller = new Controller('Not from this world');
        $controller->getPageTitle();
    }
}