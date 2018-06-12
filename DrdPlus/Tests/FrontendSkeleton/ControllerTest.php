<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use DrdPlus\FrontendSkeleton\Controller;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\FrontendSkeleton\WebFiles;
use DrdPlus\FrontendSkeleton\WebVersions;
use Gt\Dom\Element;

class ControllerTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_pass_every_sub_root(): void
    {
        $controller = new Controller(
            $this->getDocumentRoot(),
            'some web root',
            'some vendor root',
            'some parts root',
            'some generic parts root'
        );
        self::assertSame($this->getDocumentRoot(), $controller->getDocumentRoot());
        self::assertSame('some web root', $controller->getWebRoot());
        self::assertSame('some vendor root', $controller->getVendorRoot());
        self::assertSame('some parts root', $controller->getPartsRoot());
        self::assertSame('some generic parts root', $controller->getGenericPartsRoot());
    }

    /**
     * @test
     */
    public function I_can_get_web_name(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertSame($this->getTestsConfiguration()->getExpectedWebName(), $controller->getWebName());
    }

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

    /**
     * @test
     */
    public function I_can_get_web_versions(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertEquals(new WebVersions($this->getDocumentRoot()), $controller->getWebVersions());
    }

    /**
     * @test
     */
    public function I_can_get_web_files(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertEquals(new WebFiles($this->getWebFilesRoot()), $controller->getWebFiles());
    }

    /**
     * @test
     */
    public function I_can_get_request(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertEquals(new Request(new Bot()), $controller->getRequest());
    }

    /**
     * @test
     */
    public function I_can_change_web_root(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertSame($this->getDocumentRoot() . '/web', $controller->getWebRoot());
        $controller->setWebRoot('another web root');
        self::assertSame('another web root', $controller->getWebRoot());
    }

    /**
     * @test
     */
    public function I_can_add_body_class(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertSame([], $controller->getBodyClasses());
        $controller->addBodyClass('rumbling');
        $controller->addBodyClass('cracking');
        self::assertSame(['rumbling', 'cracking'], $controller->getBodyClasses());
    }

    /**
     * @test
     */
    public function I_can_set_contacts_fixed(): void
    {
        $controller = new Controller($this->getDocumentRoot());
        self::assertFalse($controller->isContactsFixed(), 'Contacts are expected to be simply on top by default');
        if ($this->isSkeletonChecked()) {
            /** @var Element $contacts */
            $contacts = $this->getHtmlDocument()->getElementById('contacts');
            self::assertNotEmpty($contacts, 'Contacts are missing');
            self::assertTrue($contacts->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertFalse($contacts->classList->contains('fixed'), 'Contacts should not be fixed as controller does not say so');
        }
        $controller->setContactsFixed();
        self::assertTrue($controller->isContactsFixed(), 'Failed to set contacts as fixed');
        if ($this->isSkeletonChecked()) {
            \ob_start();
            include $this->getDocumentRoot() . '/index.php';
            $content = \ob_get_clean();
            $htmlDocument = new HtmlDocument($content);
            $contacts = $htmlDocument->getElementById('contacts');
            self::assertNotEmpty($contacts, 'Contacts are missing');
            self::assertTrue($contacts->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertTrue($contacts->classList->contains('fixed'), 'Contacts should be fixed as controller says so');
        }
    }
    /**
     * @test
     */
    public function I_can_hide_home_button(): void
    {
        // TODO
        $controller = new Controller($this->getDocumentRoot());
        self::assertFalse($controller->isContactsFixed(), 'Contacts are expected to be simply on top by default');
        if ($this->isSkeletonChecked()) {
            /** @var Element $contacts */
            $contacts = $this->getHtmlDocument()->getElementById('contacts');
            self::assertNotEmpty($contacts, 'Contacts are missing');
            self::assertTrue($contacts->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertFalse($contacts->classList->contains('fixed'), 'Contacts should not be fixed as controller does not say so');
        }
        $controller->setContactsFixed();
        self::assertTrue($controller->isContactsFixed(), 'Failed to set contacts as fixed');
        if ($this->isSkeletonChecked()) {
            \ob_start();
            include $this->getDocumentRoot() . '/index.php';
            $content = \ob_get_clean();
            $htmlDocument = new HtmlDocument($content);
            $contacts = $htmlDocument->getElementById('contacts');
            self::assertNotEmpty($contacts, 'Contacts are missing');
            self::assertTrue($contacts->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertTrue($contacts->classList->contains('fixed'), 'Contacts should be fixed as controller says so');
        }
    }
}