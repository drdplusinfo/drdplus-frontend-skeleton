<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\Redirect;
use DrdPlus\FrontendSkeleton\WebVersions;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Gt\Dom\Element;
use Gt\Dom\TokenList;

class FrontendControllerTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_pass_every_sub_root(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertSame($this->getTestsConfiguration()->getExpectedGoogleAnalyticsId(), $controller->getGoogleAnalyticsId());
    }

    /**
     * @test
     */
    public function I_can_get_web_name(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertSame($this->getTestsConfiguration()->getExpectedWebName(), $controller->getWebName());
    }

    /**
     * @test
     */
    public function I_can_get_page_title(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertSame($this->getTestsConfiguration()->getExpectedPageTitle(), $controller->getPageTitle());
    }

    /**
     * @test
     */
    public function I_can_get_web_versions(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertNotEmpty($controller->getWebVersions());
    }

    /**
     * @test
     */
    public function I_can_get_web_files(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertNotEmpty($controller->getWebFiles());
    }

    /**
     * @test
     */
    public function I_can_get_request(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertNotEmpty($controller->getRequest());
    }

    /**
     * @test
     */
    public function I_can_add_body_class(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertSame([], $controller->getBodyClasses());
        $controller->addBodyClass('rumbling');
        $controller->addBodyClass('cracking');
        self::assertSame(['rumbling', 'cracking'], $controller->getBodyClasses());
    }

    /**
     * @test
     */
    public function I_can_ask_if_menu_is_fixed(): void
    {
        $configurationWithoutFixedMenu = $this->createCustomConfiguration([Configuration::WEB => [Configuration::MENU_POSITION_FIXED => false]]);
        self::assertFalse($configurationWithoutFixedMenu->isMenuPositionFixed(), 'Expected configuration with menu position not fixed');
        $controller = $this->createController(null, null, $configurationWithoutFixedMenu);
        self::assertFalse($controller->isMenuPositionFixed(), 'Contacts are expected to be simply on top by default');
        if ($this->isSkeletonChecked()) {
            /** @var Element $menu */
            $menu = $this->getHtmlDocument()->getElementById('menu');
            self::assertNotEmpty($menu, 'Contacts are missing');
            self::assertTrue($menu->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertFalse($menu->classList->contains('fixed'), 'Contacts should not be fixed as controller does not say so');
        }
        $configurationWithFixedMenu = $this->createCustomConfiguration([Configuration::WEB => [Configuration::MENU_POSITION_FIXED => true]]);
        self::assertTrue($configurationWithFixedMenu->isMenuPositionFixed(), 'Expected configuration with menu position fixed');
        $controller = $this->createController(null, null, $configurationWithFixedMenu);
        self::assertTrue($controller->isMenuPositionFixed(), 'Menu should be fixed');
        if ($this->isSkeletonChecked()) {
            $content = $this->fetchNonCachedContent($controller);
            $htmlDocument = new HtmlDocument($content);
            $menu = $htmlDocument->getElementById('menu');
            self::assertNotEmpty($menu, 'Contacts are missing');
            self::assertTrue($menu->classList->contains('top'), 'Contacts should be positioned on top');
            self::assertTrue(
                $menu->classList->contains('fixed'),
                'Contacts should be fixed as controller says so;'
                . ' current classes are ' . \implode(',', $this->tokenListToArray($menu->classList))
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
        $configurationWithShownHomeButton = $this->createCustomConfiguration([Configuration::WEB => [Configuration::SHOW_HOME_BUTTON => true]]);
        self::assertTrue($configurationWithShownHomeButton->isShowHomeButton(), 'Expected configuration with shown home button');
        $controller = $this->createController(null, null, $configurationWithShownHomeButton);
        self::assertTrue($controller->isShownHomeButton(), 'Home button should be set as shown');
        if ($this->isSkeletonChecked()) {
            /** @var Element $homeButton */
            $homeButton = $this->getHtmlDocument()->getElementById('home_button');
            self::assertNotEmpty($homeButton, 'Home button is missing');
            self::assertSame('https://www.drdplus.info', $homeButton->getAttribute('href'), 'Link of home button should lead to home');
        }
        $configurationWithHiddenHomeButton = $this->createCustomConfiguration([Configuration::WEB => [Configuration::SHOW_HOME_BUTTON => false]]);
        self::assertFalse($configurationWithHiddenHomeButton->isShowHomeButton(), 'Expected configuration with hidden home button');
        $controller = $this->createController(null, null, $configurationWithHiddenHomeButton);
        self::assertFalse($controller->isShownHomeButton(), 'Home button should be hidden');
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
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper(null, true /* in production */));
        self::assertTrue($controller->getPageCache()->isInProduction(), 'Expected page cache to be in production mode');
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper(null, false /* not in production */));
        self::assertFalse($controller->getPageCache()->isInProduction(), 'Expected page cache to be not in production mode');
    }

    /**
     * @test
     */
    public function I_can_set_and_get_redirect(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
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
        $controller = $this->createController();
        $controller->setRedirect(new Redirect('https://example.com/outsider', 12));
        $content = $this->fetchNonCachedContent($controller);
        $htmlDocument = new HtmlDocument($content);
        $metaRefreshes = $this->getMetaRefreshes($htmlDocument);
        self::assertCount(1, $metaRefreshes, 'One meta tag with refresh meaning expected');
        $metaRefresh = \current($metaRefreshes);
        self::assertSame('12; url=https://example.com/outsider', $metaRefresh->getAttribute('content'));
    }

    protected function createController(
        HtmlHelper $htmlHelper = null,
        string $documentRoot = null,
        Configuration $configuration = null
    ): FrontendController
    {
        $controllerClass = static::getSutClass();
        $dirs = $this->createDirs($documentRoot);
        $configuration = $configuration ?? $this->createConfiguration($dirs);

        return new $controllerClass(
            $configuration,
            $htmlHelper ?? $this->createHtmlHelper($dirs, false, false, false, false)
        );
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_get_current_version(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        $reflection = new \ReflectionClass(FrontendController::class);
        self::assertTrue($reflection->hasProperty('configuration'), FrontendController::class . ' no more has configuration property');
        $webVersionsProperty = $reflection->getProperty('configuration');
        $webVersionsProperty->setAccessible(true);
        $configuration = $this->mockery(Configuration::class);
        $webVersionsProperty->setValue($controller, $configuration);
        $configuration->expects('getWebLastStableVersion')
            ->andReturn('foo');
        self::assertSame('foo', $controller->getCurrentVersion());
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function I_can_get_current_patch_version(): void
    {
        $controllerClass = static::getSutClass();
        /** @var FrontendController $controller */
        $controller = new $controllerClass($this->createConfiguration(), $this->createHtmlHelper());
        $reflection = new \ReflectionClass(FrontendController::class);
        self::assertTrue($reflection->hasProperty('webVersions'), FrontendController::class . ' no more has webVersions property');
        $webVersionsProperty = $reflection->getProperty('webVersions');
        $webVersionsProperty->setAccessible(true);
        $webVersions = $this->mockery(WebVersions::class);
        $webVersionsProperty->setValue($controller, $webVersions);
        $webVersions->expects('getCurrentPatchVersion')
            ->andReturn('foo');
        self::assertSame('foo', $controller->getCurrentPatchVersion());
    }
}