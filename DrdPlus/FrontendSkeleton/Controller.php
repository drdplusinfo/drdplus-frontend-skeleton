<?php
namespace DrdPlus\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use Granam\Strict\Object\StrictObject;

class Controller extends StrictObject
{
    /** @var string */
    private $documentRoot;
    /** @var string */
    private $webRoot;
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
    /** @var string */
    private $webName;
    /** @var string */
    private $pageTitle;
    /** @var WebFiles */
    private $webFiles;
    /** @var WebVersions */
    private $webVersions;
    /** @var Request */
    private $request;

    public function __construct(
        string $documentRoot,
        string $webRoot = null,
        string $vendorRoot = null,
        string $partsRoot = null,
        string $genericPartsRoot = null
    )
    {
        $this->documentRoot = $documentRoot;
        $this->webRoot = $webRoot ?? ($documentRoot . '/web');
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
    public function getWebRoot(): string
    {
        return $this->webRoot;
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

    public function getWebName(): string
    {
        if ($this->webName === null) {
            if (!\file_exists($this->getDocumentRoot() . '/name.txt')) {
                throw new Exceptions\MissingFileWithPageName("Can not find file '{$this->getDocumentRoot()}/name.txt'");
            }
            $webName = \trim((string)\file_get_contents($this->getDocumentRoot() . '/name.txt'));
            if ($webName === '') {
                throw new Exceptions\FileWithPageNameIsEmpty("File '{$this->getDocumentRoot()}/name.txt' is empty");
            }
            $this->webName = $webName;
        }

        return $this->webName;
    }

    public function getPageTitle(): string
    {
        if ($this->pageTitle === null) {
            $name = $this->getWebName();
            $smiley = \file_exists($this->getDocumentRoot() . '/title_smiley.txt')
                ? \trim(\file_get_contents($this->getDocumentRoot() . '/title_smiley.txt'))
                : '';

            $this->pageTitle = ($smiley !== '')
                ? ($smiley . ' ' . $name)
                : $name;
        }

        return $this->pageTitle;
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
        if ($this->webFiles === null) {
            $this->webFiles = new WebFiles($this->getWebRoot());
        }

        return $this->webFiles;
    }

    public function getWebVersions(): WebVersions
    {
        if ($this->webVersions === null) {
            $this->webVersions = new WebVersions($this->getDocumentRoot());
        }

        return $this->webVersions;
    }

    public function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = new Request(new Bot());
        }

        return $this->request;
    }
}