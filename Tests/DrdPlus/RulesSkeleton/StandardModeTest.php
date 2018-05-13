<?php
namespace Tests\DrdPlus\RulesSkeleton;

use Gt\Dom\HTMLDocument;

class StandardModeTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_get_notes_styled(): void
    {
        $content = $this->getRulesContent();
        $html = new HTMLDocument($content);
        self::assertNotEmpty($html->getElementsByClassName('note'));
    }

    /**
     * @test
     */
    public function I_am_not_distracted_by_development_classes(): void
    {
        $content = $this->getRulesContent();
        $html = new HTMLDocument($content);
        self::assertCount(0, $html->getElementsByClassName('covered-by-code'));
        self::assertCount(0, $html->getElementsByClassName('generic'));
        self::assertCount(0, $html->getElementsByClassName('excluded'));
    }
}