<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

class PageCache extends Cache
{
    public function __construct(WebVersions $webVersions, Dirs $dirs, bool $isInProduction, string $cachePrefix = null)
    {
        parent::__construct($webVersions, $dirs, $isInProduction, $cachePrefix ?? 'page-' . \md5($dirs->getCacheRoot()));
    }
}