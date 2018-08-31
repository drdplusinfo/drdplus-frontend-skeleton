<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\FrontendSkeleton\HtmlDocument;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use DrdPlus\FrontendSkeleton\Redirect;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;
use Gt\Dom\Element;
use Gt\Dom\TokenList;

class FrontendControllerTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_add_body_class(): void
    {
        $controller = $this->createController();
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
        $controller = $this->createController(null, $configurationWithoutFixedMenu);
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
        $controller = $this->createController(null, $configurationWithFixedMenu);
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
        $controller = $this->createController(null, $configurationWithShownHomeButton);
        self::assertTrue($controller->isShownHomeButton(), 'Home button should be set as shown');
        if ($this->isSkeletonChecked()) {
            /** @var Element $homeButton */
            $homeButton = $this->getHtmlDocument()->getElementById('home_button');
            self::assertNotEmpty($homeButton, 'Home button is missing');
            self::assertSame(
                HtmlHelper::turnToLocalLink('https://www.drdplus.info'),
                $homeButton->getAttribute('href'), 'Link of home button should lead to home'
            );
        }
        $configurationWithHiddenHomeButton = $this->createCustomConfiguration([Configuration::WEB => [Configuration::SHOW_HOME_BUTTON => false]]);
        self::assertFalse($configurationWithHiddenHomeButton->isShowHomeButton(), 'Expected configuration with hidden home button');
        $controller = $this->createController(null, $configurationWithHiddenHomeButton);
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
    public function I_can_set_and_get_redirect(): void
    {
        $controller = $this->createController();
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
}