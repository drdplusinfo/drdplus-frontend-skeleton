<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

class HtmlDocument extends \Gt\Dom\HTMLDocument
{
    public function __construct($document = '')
    {
        parent::__construct($document);
        $this->formatOutput = true;
    }

    public function saveHTML(\DOMNode $node = null): string
    {
        $html = parent::saveHTML($node);

        return \str_replace('</script><', "</script>\n<", $html);
    }
}