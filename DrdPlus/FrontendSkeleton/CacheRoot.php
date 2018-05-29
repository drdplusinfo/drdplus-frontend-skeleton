<?php
namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class CacheRoot extends StrictObject
{
    /** @var string */
    private $cacheRootDir;

    /**
     * @param string $documentRoot
     */
    public function __construct(string $documentRoot)
    {
        $this->cacheRootDir = "{$documentRoot}/cache/" . \PHP_SAPI;
    }

    /**
     * @return string
     */
    public function getCacheRootDir(): string
    {
        return $this->cacheRootDir;
    }
}