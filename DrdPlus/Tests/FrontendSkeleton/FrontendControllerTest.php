<?php
namespace DrdPlus\Tests\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\FrontendSkeleton\WebFiles;
use DrdPlus\FrontendSkeleton\WebVersions;
use Gt\Dom\Element;
use Gt\Dom\TokenList;

class FrontendControllerTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_pass_every_sub_root(): void
    {
        $controller = new FrontendController(
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
        $controller = new FrontendController($this->getDocumentRoot());
        self::assertSame($this->getTestsConfiguration()->getExpectedWebName(), $controller->getWebName());
    }

    /**
     * @test
     */
    public function I_can_get_page_title(): void
    {
        $controller = new FrontendController($this->getDocumentRoot());
        self::assertSame($this->getTestsConfiguration()->getExpectedPageTitle(), $controller->getPageTitle());
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\MissingFileWithPageName
     * @expectedExceptionMessageRegExp ~'Not from this world/name[.]txt'~
     */
    public function I_can_not_get_page_title_if_text_file_with_its_name_does_not_exist(): void
    {
        $controller = new FrontendController('Not from this world');
        $controller->getPageTitle();
    }

    /**
     * @test
     */
    public function I_can_get_web_versions(): void
    {
        $controller = new FrontendController($this->getDocumentRoot());
        self::assertEquals(new WebVersions($this->getDocumentRoot()), $controller->getWebVersions());
    }

    /**
     * @test
     */
    public function I_can_get_web_files(): void
    {
        $controller = new FrontendController($this->getDocumentRoot());
        self::assertEquals(new WebFiles($this->getWebFilesRoot()), $controller->getWebFiles());
    }

    /**
     * @test
     */
    public function I_can_get_request(): void
    {
        $controller = new FrontendController($this->getDocumentRoot());
        self::assertEquals(new Request(new Bot()), $controller->getRequest());
    }

    /**
     * @test
     */
    public function I_can_change_web_root(): void
    {
        $controller = new FrontendController($this->getDocumentRoot());
        self::assertSame($this->getDocumentRoot() . '/web', $controller->getWebRoot());
        $controller->setWebRoot('another web root');
        self::assertSame('another web root', $controller->getWebRoot());
    }

    /**
     * @test
     */
    public function I_can_add_body_class(): void
    {
        $controller = new FrontendController($this->getDocumentRoot());
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
        $controller = new FrontendController($this->getDocumentRoot());
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
            /** @noinspection PhpIncludeInspection */
            include $this->getDocumentRoot() . '/index.php';
            $content = \ob_get_clean();
            $htmlDocument = new HtmlDocument($content);
            $contacts = $htmlDocument->getElementById('contacts');
            self::assertNotEmpty($contacts, 'Contacts are missing');
            self::assertTrue($contacts->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertTrue(
                $contacts->classList->contains('fixed'),
                'Contacts should be fixed as controller says so'
                . 'Current classes are ' . \implode(',', $this->tokenListToArray($contacts->classList))
            );
        }
    }

    private function tokenListToArray(TokenList $tokenList): array
    {
        $array = [];
        for ($index = 0; $index < $tokenList->length; $index++) {
            $array[] = $tokenList->item($index);
        }

        return $array;
    }

    /**
     * @test
     */
    public function I_can_hide_home_button(): void
    {
        $controller = new FrontendController($this->getDocumentRoot());
        self::assertTrue($controller->isShownHomeButton(), 'Home button should be shown by default');
        if ($this->isSkeletonChecked()) {
            /** @var Element $homeButton */
            $homeButton = $this->getHtmlDocument()->getElementById('home_button');
            self::assertNotEmpty($homeButton, 'Home button is missing');
            self::assertSame('https://www.drdplus.info', $homeButton->getAttribute('href'), 'Link of home button should lead to home');
        }
        $controller->hideHomeButton();
        self::assertFalse($controller->isShownHomeButton(), 'Failed to hide home button');
        if ($this->isSkeletonChecked()) {
            \ob_start();
            /** @noinspection PhpIncludeInspection */
            include $this->getDocumentRoot() . '/index.php';
            $content = \ob_get_clean();
            $htmlDocument = new HtmlDocument($content);
            $homeButton = $htmlDocument->getElementById('home_button');
            self::assertEmpty($homeButton, 'Home button should be already hidden');
        }
    }
}