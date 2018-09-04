<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\HtmlHelper;
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
                $this->getHtmlDocument()->getElementsByClassName(HtmlHelper::NOTE_CLASS),
                "No elements with '" . HtmlHelper::NOTE_CLASS . "' class expected according to tests config"
            );
        } else {
            self::assertNotEmpty(
                $this->getHtmlDocument()->getElementsByClassName(HtmlHelper::NOTE_CLASS),
                "Expected at least a single element with '" . HtmlHelper::NOTE_CLASS . "' class according to tests config"
            );
        }
    }

    /**
     * @test
     */
    public function I_am_not_distracted_by_development_classes(): void
    {
        $htmlDocument = $this->getHtmlDocument();
        self::assertCount(0, $htmlDocument->getElementsByClassName(HtmlHelper::COVERED_BY_CODE_CLASS));
        self::assertCount(0, $htmlDocument->getElementsByClassName(HtmlHelper::GENERIC_CLASS));
        self::assertCount(0, $htmlDocument->getElementsByClassName(HtmlHelper::EXCLUDED_CLASS));
    }
}