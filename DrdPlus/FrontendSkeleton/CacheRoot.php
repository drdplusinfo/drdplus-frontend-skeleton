<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

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

    public function __toString()
    {
        return $this->getCacheRootDir();
    }
}