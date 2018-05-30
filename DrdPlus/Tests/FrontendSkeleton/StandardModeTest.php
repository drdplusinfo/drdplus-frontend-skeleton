<?php
namespace DrdPlus\Tests\FrontendSkeleton;

class StandardModeTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_get_notes_styled(): void
    {
        if (!$this->getTestsConfiguration()->hasNotes()) {
            self::assertEmpty($this->getHtmlDocument()->getElementsByClassName('note'));
        } else {
            self::assertNotEmpty($this->getHtmlDocument()->getElementsByClassName('note'));
        }
    }

    /**
     * @test
     */
    public function I_am_not_distracted_by_development_classes(): void
    {
        $htmlDocument = $this->getHtmlDocument();
        self::assertCount(0, $htmlDocument->getElementsByClassName('covered-by-code'));
        self::assertCount(0, $htmlDocument->getElementsByClassName('generic'));
        self::assertCount(0, $htmlDocument->getElementsByClassName('excluded'));
    }
}