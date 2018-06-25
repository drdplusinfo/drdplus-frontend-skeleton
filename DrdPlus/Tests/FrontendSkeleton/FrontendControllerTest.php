<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\Redirect;
use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\FrontendSkeleton\WebFiles;
use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\FrontendSkeleton\WebVersionSwitcher;
use Gt\Dom\Element;
use Gt\Dom\TokenList;
use Mockery\MockInterface;

class FrontendControllerTest extends AbstractContentTest
{

    /**
     * @test
     */
    public function I_can_pass_every_sub_root(): void
    {
        $controller = new FrontendController(
            'Google Analytics Foo',
            $this->createHtmlHelper(),
            $this->getDocumentRoot(),
            'some web root',
            'some vendor root',
            'some parts root',
            'some generic parts root'
        );
        self::assertSame('Google Analytics Foo', $controller->getGoogleAnalyticsId());
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
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        self::assertSame($this->getTestsConfiguration()->getExpectedWebName(), $controller->getWebName());
    }

    /**
     * @test
     */
    public function I_can_get_page_title(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        self::assertSame($this->getTestsConfiguration()->getExpectedPageTitle(), $controller->getPageTitle());
    }

    /**
     * @test
     * @expectedException \DrdPlus\FrontendSkeleton\Exceptions\MissingFileWithPageName
     * @expectedExceptionMessageRegExp ~'Not from this world/name[.]txt'~
     */
    public function I_can_not_get_page_title_if_text_file_with_its_name_does_not_exist(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), 'Not from this world');
        $controller->getPageTitle();
    }

    /**
     * @test
     */
    public function I_can_get_web_versions(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        self::assertEquals(new WebVersions($this->getDocumentRoot()), $controller->getWebVersions());
    }

    /**
     * @test
     */
    public function I_can_get_web_files(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        self::assertEquals(new WebFiles($this->getWebFilesRoot()), $controller->getWebFiles());
    }

    /**
     * @test
     */
    public function I_can_get_request(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        self::assertEquals(new Request(new Bot()), $controller->getRequest());
    }

    /**
     * @test
     */
    public function I_can_change_web_root(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        self::assertSame($this->getDocumentRoot() . '/web', $controller->getWebRoot());
        $controller->setWebRoot('another web root');
        self::assertSame('another web root', $controller->getWebRoot());
    }

    /**
     * @test
     */
    public function I_can_add_body_class(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
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
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(false /* not in production */), $this->getDocumentRoot());
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
            $content = $this->fetchNonCachedContent($controller);
            $htmlDocument = new HtmlDocument($content);
            $contacts = $htmlDocument->getElementById('contacts');
            self::assertNotEmpty($contacts, 'Contacts are missing');
            self::assertTrue($contacts->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertTrue(
                $contacts->classList->contains('fixed'),
                'Contacts should be fixed as controller says so;'
                . ' current classes are ' . \implode(',', $this->tokenListToArray($contacts->classList))
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
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(false /* not in production */), $this->getDocumentRoot());
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
            $content = $this->fetchNonCachedContent($controller);
            $htmlDocument = new HtmlDocument($content);
            $homeButton = $htmlDocument->getElementById('home_button');
            self::assertEmpty($homeButton, 'Home button should not be used at all');
        }
    }

    /**
     * @test
     */
    public function I_can_get_page_cache_with_properly_set_production_mode(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(true /* in production */), $this->getDocumentRoot());
        self::assertTrue($controller->getPageCache()->isInProduction(), 'Expected page cache to be in production mode');
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(false /* not in production */), $this->getDocumentRoot());
        self::assertFalse($controller->getPageCache()->isInProduction(), 'Expected page cache to be not in production mode');
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function I_can_get_wanted_version(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        $this->I_will_get_master_as_default_wanted_version($controller);
        $this->I_will_get_version_from_cookie($controller);
        $this->I_will_get_version_from_get($controller);
    }

    private function I_will_get_master_as_default_wanted_version(FrontendController $controller): void
    {
        self::assertSame('master', $controller->getWantedVersion());
    }

    private function I_will_get_version_from_cookie(FrontendController $controller): void
    {
        unset($_GET['version']);
        $_COOKIE['version'] = 'foo_from_cookie';
        self::assertSame('foo_from_cookie', $controller->getWantedVersion(), 'Wanted version should be taken from cookie');
        unset($_COOKIE['version']);
        $this->I_will_get_master_as_default_wanted_version($controller);
    }

    private function I_will_get_version_from_get(FrontendController $controller): void
    {
        $_GET['version'] = 'bar_from_get';
        self::assertSame('bar_from_get', $controller->getWantedVersion(), 'Wanted version should be taken from get, then from cookie');
        $_COOKIE['version'] = 'some_version_from_cookie';
        self::assertSame('bar_from_get', $controller->getWantedVersion(), 'Wanted version should be taken from get, then from cookie');
        unset($_GET['version']);
        self::assertSame('some_version_from_cookie', $controller->getWantedVersion(), 'Wanted version should be taken from get, then from cookie');
    }

    /**
     * @test
     * @backupGlobals enabled
     * @throws \ReflectionException
     */
    public function I_can_switch_to_wanted_version(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        $controllerReflection = new \ReflectionClass($controller);
        $versionSwitcherProperty = $controllerReflection->getProperty('webVersionSwitcher');
        $versionSwitcherProperty->setAccessible(true);
        $this->I_will_be_switched_to_master_as_default_wanted_version($controller, $versionSwitcherProperty);
        $this->I_will_be_switched_to_version_from_cookie($controller, $versionSwitcherProperty);
        $this->I_will_be_switched_to_version_from_get($controller, $versionSwitcherProperty);
    }

    /**
     * @param string $expectedVersionToSwitchTo
     * @return WebVersionSwitcher|MockInterface
     */
    private function createWebVersionSwitcher(string $expectedVersionToSwitchTo): WebVersionSwitcher
    {
        $webVersionSwitcher = $this->mockery(WebVersionSwitcher::class);
        $webVersionSwitcher->expects('switchToVersion')
            ->with($expectedVersionToSwitchTo)
            ->andReturn(true);

        return $webVersionSwitcher;
    }

    private function I_will_be_switched_to_master_as_default_wanted_version(
        FrontendController $controller,
        \ReflectionProperty $versionSwitcherProperty
    ): void
    {
        $versionSwitcherProperty->setValue($controller, $this->createWebVersionSwitcher('master'));
        self::assertTrue($controller->switchToWantedVersion());
        self::assertSame('master', $controller->getWantedVersion());
    }

    private function I_will_be_switched_to_version_from_cookie(
        FrontendController $controller,
        \ReflectionProperty $versionSwitcherProperty
    ): void
    {
        $versionSwitcherProperty->setValue($controller, $this->createWebVersionSwitcher('foo_from_cookie'));
        $_COOKIE['version'] = 'foo_from_cookie';
        self::assertTrue($controller->switchToWantedVersion());
    }

    private function I_will_be_switched_to_version_from_get(
        FrontendController $controller,
        \ReflectionProperty $versionSwitcherProperty
    ): void
    {
        $versionSwitcherProperty->setValue($controller, $this->createWebVersionSwitcher('bar_from_get'));
        $_GET['version'] = 'bar_from_get';
        self::assertTrue($controller->switchToWantedVersion());
    }

    /**
     * @test
     */
    public function I_can_set_and_get_redirect(): void
    {
        $controller = new FrontendController('Google Analytics Foo', $this->createHtmlHelper(), $this->getDocumentRoot());
        self::assertNull($controller->getRedirect());
        $controller->setRedirect($redirect = new Redirect('redirect to the future', 999));
        self::assertSame($redirect, $controller->getRedirect());
    }

    /**
     * @test
     */
    public function I_can_set_redirect_via_html_meta(): void
    {
        self::assertCount(0, $this->getMetaRefreshes($this->getHtmlDocument()), 'No meta tag with refresh meaning expected so far');
        $controller = new FrontendController(
            'Google Analytics Foo',
            $this->createHtmlHelper(false /* not in production */),
            $this->getDocumentRoot()
        );
        $controller->setRedirect(new Redirect('https://example.com/outsider', 12));
        $content = $this->fetchNonCachedContent($controller);
        $htmlDocument = new HtmlDocument($content);
        $metaRefreshes = $this->getMetaRefreshes($htmlDocument);
        self::assertCount(1, $metaRefreshes, 'One meta tag with refresh meaning expected');
        $metaRefresh = \current($metaRefreshes);
        self::assertSame('12; url=https://example.com/outsider', $metaRefresh->getAttribute('content'));
    }

    /**
     * @param HtmlDocument $document
     * @return array|Element[]
     */
    private function getMetaRefreshes(HtmlDocument $document): array
    {
        $metaElements = $document->head->getElementsByTagName('meta');
        $metaRefreshes = [];
        foreach ($metaElements as $metaElement) {
            if ($metaElement->getAttribute('http-equiv') === 'Refresh') {
                $metaRefreshes[] = $metaElement;
            }
        }

        return $metaRefreshes;
    }
}