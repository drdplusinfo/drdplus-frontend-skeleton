<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

class WebCache extends Cache
{
    public function __construct(WebVersions $webVersions, Dirs $dirs, Request $request, bool $isInProduction, string $cachePrefix = null)
    {
        parent::__construct($webVersions, $dirs, $request, $isInProduction, $cachePrefix ?? 'page-' . \md5($dirs->getCacheRoot()));
    }
}