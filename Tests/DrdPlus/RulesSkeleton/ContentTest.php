<?php
namespace Tests\DrdPlus\RulesSkeleton;

use Gt\Dom\Element;

class ContentTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function Every_plus_after_2d6_is_upper_indexed(): void
    {
        self::assertSame(
            0,
            \preg_match(
                '~.{0,10}2k6\s*(?!<span class="upper-index">\+</span>).{0,20}\+~',
                $this->getRulesContentWithoutIds(),
                $matches
            ),
            \var_export($matches, true)
        );
    }

    private function getRulesContentWithoutIds(): string
    {
        $document = clone $this->getRulesHtmlDocument();
        /** @var Element $body */
        $body = $document->getElementsByTagName('body')[0];
        $this->removeIds($body);

        return $document->saveHTML();
    }

    private function removeIds(Element $element): void
    {
        if ($element->hasAttribute('id')) {
            $element->removeAttribute('id');
        }
        foreach ($element->children as $child) {
            $this->removeIds($child);
        }
    }

    /**
     * @test
     */
    public function Every_registered_trademark_and_trademark_symbols_are_upper_indexed(): void
    {
        self::assertSame(
            0,
            \preg_match(
                '~.{0,10}(?:(?<!<span class="upper-index">)\s*[®™]|[®™]\s*(?!</span>).{0,10})~u',
                $this->getRulesContent(),
                $matches
            ),
            \var_export($matches, true)
        );
    }

    /**
     * @test
     */
    public function Authors_got_heading(): void
    {
        $authorsHeading = $this->getRulesHtmlDocument()->getElementById('autori');
        self::assertNotEmpty($authorsHeading, 'Authors should have heading');
        self::assertSame(
            'h3',
            $authorsHeading->nodeName,
            'Authors heading should be h3, but is ' . $authorsHeading->nodeName
        );
    }
}