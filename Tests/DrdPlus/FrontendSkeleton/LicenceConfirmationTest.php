<?php
namespace Tests\DrdPlus\FrontendSkeleton;

use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;

class LicenceConfirmationTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_have_to_confirm_owning_of_a_licence_first(): void
    {
        if (\defined('FREE_ACCESS') && FREE_ACCESS) {
            self::assertFalse(
                false,
                'Text-only and free content is accessible for anyone and licence need not to be confirmed'
            );

            return;
        }
        $html = new HTMLDocument($this->getOwnershipConfirmationContent());
        $forms = $html->getElementsByTagName('form');
        self::assertCount(3, $forms);
        foreach ($forms as $index => $form) {
            switch ($index) {
                case 0:
                    $this->I_can_continue_with_trial($form);
                    break;
                case 1:
                    $this->I_can_buy_licence($form);
                    break;
                case 2:
                    $this->I_can_continue_after_confirmation_of_owning($form);
            }
        }
    }

    private function I_can_buy_licence(Element $buyForm): void
    {
        self::assertStringStartsWith('https://obchod.altar.cz', $buyForm->getAttribute('action'));
        self::assertRegExp(
            '~^' . preg_quote('https://obchod.altar.cz/', '~') . '\w+~',
            $buyForm->getAttribute('action'),
            'Missing direct link to current article in e-shop, (put it into eshop_url.txt file)'
        );
        self::assertContains((string)$buyForm->getAttribute('method'), ['' /* get as default */, 'get']);
        self::assertSame('buy', $buyForm->getElementsByTagName('input')->current()->getAttribute('name'));
        self::assertEmpty($buyForm->getAttribute('onsubmit'), 'No confirmation should be required to access e-shop');
    }

    private function I_can_continue_after_confirmation_of_owning(Element $confirmForm): void
    {
        self::assertSame('post', $confirmForm->getAttribute('method'));
        self::assertSame('confirm', $confirmForm->getElementsByTagName('input')->current()->getAttribute('name'));
        self::assertStringStartsWith('return window.confirm', $confirmForm->getAttribute('onsubmit'));
    }

    private function I_can_continue_with_trial(Element $trialForm): void
    {
        self::assertSame('post', $trialForm->getAttribute('method'));
        self::assertSame('trial', $trialForm->getElementsByTagName('input')->current()->getAttribute('name'));
        self::assertEmpty($trialForm->getAttribute('onsubmit'), 'No confirmation should be required for trial access');
    }

    /**
     * @test
     */
    public function I_can_confirm_ownership(): void
    {
        $html = new HTMLDocument($this->getContent()); // includes confirmation via cookie
        $forms = $html->getElementsByTagName('form');
        self::assertCount(0, $forms, 'No forms expected in confirmed content');
    }

    /**
     * @test
     * @backupGlobals
     */
    public function Crawlers_can_pass_without_licence_owning_confirmation(): void
    {
        foreach (RequestTest::getCrawlerUserAgents() as $crawlerUserAgent) {
            $_SERVER['HTTP_USER_AGENT'] = $crawlerUserAgent;
            self::assertSame(
                $this->getOwnershipConfirmationContent(true /* not cached */),
                $this->getContent(),
                'Expected rules content for a crawler, skipping ownership confirmation page'
            );
        }
    }
}