<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton\Web;

use Granam\Strict\Object\StrictObject;

class Body extends StrictObject
{
    /** @var WebFiles */
    private $webFiles;

    public function __construct(WebFiles $webFiles)
    {
        $this->webFiles = $webFiles;
    }

    public function __toString()
    {
        return $this->getBodyString();
    }

    public function getBodyString(): string
    {
        $content = '';
        foreach ($this->getWebFiles() as $webFile) {
            if (\preg_match('~[.]php$~', $webFile)) {
                \ob_start();
                /** @noinspection PhpIncludeInspection */
                include $webFile;
                $content .= \ob_get_clean();
            } elseif (\preg_match('~[.]md$~', $webFile)) {
                $content .= \Parsedown::instance()->parse(\file_get_contents($webFile));
            } else {
                $content .= \file_get_contents($webFile);
            }
        }

        return <<<HTML
<div class="main">
  <div class="background-image"></div>
  $content
</div>
HTML;
    }

    protected function getWebFiles(): WebFiles
    {
        return $this->webFiles;
    }
}