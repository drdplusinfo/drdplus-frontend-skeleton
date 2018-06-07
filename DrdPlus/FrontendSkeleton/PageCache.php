<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

class PageCache extends Cache
{
    public function __construct(CacheRoot $cacheRoot, WebVersions $webVersions, bool $isInProduction, string $webRoot)
    {
        parent::__construct($cacheRoot, $webVersions, $isInProduction, 'page-' . \md5($webRoot));
    }

}