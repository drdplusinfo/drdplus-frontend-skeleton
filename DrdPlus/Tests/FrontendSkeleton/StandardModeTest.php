<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class StandardModeTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_get_notes_styled(): void
    {
        if (!$this->getTestsConfiguration()->hasNotes()) {
            self::assertEmpty(
                $this->getHtmlDocument()->getElementsByClassName('note'),
                "No elements with 'note' class expected according to tests config"
            );
        } else {
            self::assertNotEmpty(
                $this->getHtmlDocument()->getElementsByClassName('note'),
                "Expected at least a single element with 'note' class according to tests config"
            );
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