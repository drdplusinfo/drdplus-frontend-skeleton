<?php
namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class Controller extends StrictObject
{
    /** @var string */
    private $documentRoot;
    /** @var string */
    private $vendorRoot;
    /** @var string */
    private $partsRoot;
    /** @var string */
    private $genericPartsRoot;
    /** @var string */
    private $jsRoot;
    /** @var string */
    private $cssRoot;
    /** @var */
    private $title;

    public function __construct(
        string $documentRoot,
        string $vendorRoot = null,
        string $partsRoot = null,
        string $genericPartsRoot = null
    )
    {
        $this->documentRoot = $documentRoot;
        $this->vendorRoot = $vendorRoot ?? ($documentRoot . '/vendor');
        $this->partsRoot = $partsRoot ?? ($documentRoot . '/parts');
        $this->genericPartsRoot = $genericPartsRoot ?? (__DIR__ . '/../../parts/frontend-skeleton');
        $this->cssRoot = $documentRoot . '/css';
        $this->jsRoot = $documentRoot . '/js';
    }

    /**
     * @return string
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    /**
     * @return string
     */
    public function getVendorRoot(): string
    {
        return $this->vendorRoot;
    }

    /**
     * @return string
     */
    public function getPartsRoot(): string
    {
        return $this->partsRoot;
    }

    /**
     * @return string
     */
    public function getGenericPartsRoot(): string
    {
        return $this->genericPartsRoot;
    }

    /**
     * @return string
     */
    public function getJsRoot(): string
    {
        return $this->jsRoot;
    }

    /**
     * @return string
     */
    public function getCssRoot(): string
    {
        return $this->cssRoot;
    }

    public function getCssFiles(): CssFiles
    {
        return new CssFiles($this->getCssRoot());
    }

    public function getJsFiles(): JsFiles
    {
        return new JsFiles($this->getJsRoot());
    }

    public function getPageTitle(): string
    {
        if ($this->title === null) {
            if (!\file_exists($this->getDocumentRoot() . '/name.txt')) {
                throw new Exceptions\MissingFileWithPageName("Can not find file '{$this->getDocumentRoot()}/name.txt'");
            }
            $name = \trim((string)\file_get_contents($this->getDocumentRoot() . '/name.txt'));
            $smiley = \file_exists($this->getDocumentRoot() . '/title_smiley.txt')
                ? \trim(\file_get_contents($this->getDocumentRoot() . '/title_smiley.txt'))
                : '';

            $this->title = ($smiley !== '')
                ? ($smiley . ' ' . $name)
                : $name;
        }

        return $this->title;
    }

    public function getMenu(): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getGenericPartsRoot() . '/menu.php';

        return \ob_get_clean();
    }

    public function getCustomBodyContent(): string
    {
        if (!\file_exists($this->getPartsRoot() . '/custom_body_content.php')) {
            return '';
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        $content = '<div id="customBodyContent">';
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getPartsRoot() . '/custom_body_content.php';
        $content .= \ob_get_clean();
        $content .= '</div>';

        return $content;
    }

    public function getWebContent(): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $this;
        $content = '';
        foreach ($this->getWebFiles() as $webFile) {
            if (\preg_match('~\.php$~', $webFile)) {
                \ob_start();
                /** @noinspection PhpIncludeInspection */
                include $webFile;
                $content .= \ob_get_clean();
            } else {
                $content .= \file_get_contents($webFile);
            }
        }

        return $content;
    }

    public function getWebFiles(): WebFiles
    {
        return new WebFiles($this->getDocumentRoot() . '/web');
    }

    public function getWebVersions(): WebVersions
    {
        return new WebVersions($this->getDocumentRoot());
    }

    public function getRequest(): Request
    {
        return new Request();
    }
}