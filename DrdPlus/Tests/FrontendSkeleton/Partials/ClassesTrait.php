<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\FrontendSkeleton\WebVersions;

trait ClassesTrait
{
    /**
     * @return string|WebVersions
     */
    protected function getWebVersionsClass(): string
    {
        return WebVersions::class;
    }

    /**
     * @return string|Cache
     */
    protected function getCacheClass(): string
    {
        return Cache::class;
    }

    /**
     * @return string|Configuration
     */
    protected function getConfigurationClass(): string
    {
        return Configuration::class;
    }

    /**
     * @return string|Request
     */
    protected function getRequestClass(): string
    {
        return Request::class;
    }

    /**
     * @return string|FrontendController
     */
    protected function getControllerClass(): string
    {
        return FrontendController::class;
    }

}