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
    /** @var string */
    private $pageTitle;

    public function __construct(Configuration $configuration, HtmlHelper $htmlHelper, CssFiles $cssFiles)
    {
        $this->configuration = $configuration;
        $this->htmlHelper = $htmlHelper;
        $this->cssFiles = $cssFiles;
    }

    public function getHeadString(): string
    {
        $googleAnalyticsId = $this->getConfiguration()->getGoogleAnalyticsId();

        return <<<HTML
<title>{$this->getPageTitle()}</title>
<link rel="shortcut icon" href="/favicon.ico">
<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
<script id="googleAnalyticsId" data-google-analytics-id="{$googleAnalyticsId}"
async src="https://www.googletagmanager.com/gtag/js?id={$googleAnalyticsId}"></script>
{$this->getRenderedJsScripts()}
{$this->getRenderedCssFiles()}
HTML;
    }

    private function getPageTitle(): string
    {
        if ($this->pageTitle === null) {
            $name = $this->getConfiguration()->getWebName();
            $smiley = $this->getConfiguration()->getTitleSmiley();
            $this->pageTitle = ($smiley !== '')
                ? ($smiley . ' ' . $name)
                : $name;
        }

        return $this->pageTitle;
    }

    protected function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    private function getRenderedJsScripts(): string
    {
        $renderedJsFiles = [];
        foreach ($this->getJsFiles() as $jsFile) {
            $renderedJsFiles[] = "<script type='text/javascript' src='/js/{$jsFile}'></script>";
        }

        return \implode("\n", $renderedJsFiles);
    }

    private function getJsFiles(): JsFiles
    {
        return new JsFiles($this->getConfiguration()->getDirs(), $this->getHtmlHelper()->isInProduction());
    }

    protected function getHtmlHelper(): HtmlHelper
    {
        return $this->htmlHelper;
    }

    private function getRenderedCssFiles(): string
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