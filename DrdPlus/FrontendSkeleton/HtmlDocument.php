<?php
declare(strict_types=1);

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