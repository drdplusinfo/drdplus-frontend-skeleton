<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class Dirs extends StrictObject
{
    /** @var string */
    protected $documentRoot;
    /** @var string */
    protected $webRoot;
    /** @var string */
    protected $vendorRoot;
    /** @var string */
    protected $partsRoot;
    /** @var string */
    protected $genericPartsRoot;
    /** @var string */
    protected $cssRoot;
    /** @var string */
    protected $jsRoot;
    /** @var string */
    protected $dirForVersions;
    /** @var string */
    protected $cacheRoot;

    public function __construct(string $documentRoot = null)
    {
        $this->documentRoot = $documentRoot ?? (\PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());
        $this->populateSubRoots($this->documentRoot);
    }

    protected function populateSubRoots(string $documentRoot): void
    {
        $this->webRoot = $documentRoot . '/web';
        $this->vendorRoot = $documentRoot . '/vendor';
        $this->partsRoot = $documentRoot . '/parts';
        $this->genericPartsRoot = __DIR__ . '/../../parts/frontend-skeleton';
        $this->cssRoot = $documentRoot . '/css';
        $this->jsRoot = $documentRoot . '/js';
        $this->dirForVersions = $documentRoot . '/versions';
        $this->cacheRoot = $documentRoot . '/cache/' . \PHP_SAPI;
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
    public function getCssRoot(): string
    {
        return $this->cssRoot;
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
    public function getDirForVersions(): string
    {
        return $this->dirForVersions;
    }

    /**
     * @return string
     */
    public function getCacheRoot(): string
    {
        return $this->cacheRoot;
    }
}