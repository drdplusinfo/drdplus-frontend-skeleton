<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton\Web;

use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\FrontendSkeleton\CssFiles;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use Granam\Strict\Object\StrictObject;

class Head extends StrictObject
{
    /** @var Configuration */
    private $configuration;
    /** @var HtmlHelper */
    private $htmlHelper;
    /** @var CssFiles */
    private $cssFiles;
    /** @var JsFiles */
    private $jsFiles;

    public function __construct(Configuration $configuration, HtmlHelper $htmlHelper, CssFiles $cssFiles, JsFiles $jsFiles)
    {
        $this->configuration = $configuration;
        $this->htmlHelper = $htmlHelper;
        $this->cssFiles = $cssFiles;
        $this->jsFiles = $jsFiles;
    }

    public function getHeadString(): string
    {
        return <<<HTML
<title>{$this->getPageTitle()}</title>
<link rel="shortcut icon" href="/favicon.ico">
<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
{$this->getRenderedJsScripts()}
{$this->getRenderedCssFiles()}
HTML;
    }

    protected function getPageTitle(): string
    {
        $name = $this->getConfiguration()->getWebName();
        $smiley = $this->getConfiguration()->getTitleSmiley();

        return $smiley !== ''
            ? ($smiley . ' ' . $name)
            : $name;
    }

    protected function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    protected function getRenderedJsScripts(): string
    {
        $renderedJsFiles = [<<<HTML
<script async src="https://www.googletagmanager.com/gtag/js?id={$this->getConfiguration()->getGoogleAnalyticsId()}"></script>
HTML
        ];
        foreach ($this->getJsFiles() as $jsFile) {
            $renderedJsFiles[] = "<script type='text/javascript' src='/js/{$jsFile}'></script>";
        }

        return \implode("\n", $renderedJsFiles);
    }

    protected function getJsFiles(): JsFiles
    {
        return $this->jsFiles;
    }

    protected function getHtmlHelper(): HtmlHelper
    {
        return $this->htmlHelper;
    }

    protected function getRenderedCssFiles(): string
    {
        $renderedCssFiles = [];
        foreach ($this->getCssFiles() as $cssFile) {
            if (\strpos($cssFile, 'no-script.css') !== false) {
                $renderedCssFiles[] = <<<HTML
<noscript>
    <link rel="stylesheet" type="text/css" href="/css/{$cssFile}">
</noscript>
HTML;
            } else {
                $renderedCssFiles[] = <<<HTML
<link rel="stylesheet" type="text/css" href="/css/$cssFile">
HTML;
            }
        }

        return implode("\n", $renderedCssFiles);
    }

    protected function getCssFiles(): CssFiles
    {
        return $this->cssFiles;
    }
}