<?php
namespace Tests\DrdPlus\FrontendSkeleton;

use Gt\Dom\HTMLDocument;

class StandardModeTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_get_notes_styled(): void
    {
        $content = $this->getContent();
        $html = new HTMLDocument($content);
        self::assertNotEmpty($html->getElementsByClassName('note'));
    }

    /**
     * @test
     */
    public function I_am_not_distracted_by_development_classes(): void
    {
        $content = $this->getContent();
        $html = new HTMLDocument($content);
        self::assertCount(0, $html->getElementsByClassName('covered-by-code'));
        self::assertCount(0, $html->getElementsByClassName('generic'));
        self::assertCount(0, $html->getElementsByClassName('excluded'));
    }
}