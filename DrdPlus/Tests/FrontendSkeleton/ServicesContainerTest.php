<?php
declare(strict_types=1);

namespace DrdPlus\Tests\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\ServicesContainer;
use DrdPlus\Tests\FrontendSkeleton\Partials\AbstractContentTest;

class ServicesContainerTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_get_web_versions(): void
    {
        $servicesContainerClass = static::getSutClass();
        /** @var ServicesContainer $controller */
        $controller = new $servicesContainerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertNotEmpty($controller->getWebVersions());
    }

    /**
     * @test
     */
    public function I_can_get_web_files(): void
    {
        $servicesContainerClass = static::getSutClass();
        /** @var ServicesContainer $controller */
        $controller = new $servicesContainerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertNotEmpty($controller->getWebFiles());
    }

    /**
     * @test
     */
    public function I_can_get_request(): void
    {
        $servicesContainerClass = static::getSutClass();
        /** @var ServicesContainer $controller */
        $controller = new $servicesContainerClass($this->createConfiguration(), $this->createHtmlHelper());
        self::assertNotEmpty($controller->getRequest());
    }

    /**
     * @test
     */
    public function I_can_get_page_cache_with_properly_set_production_mode(): void
    {
        $servicesContainerClass = static::getSutClass();
        /** @var ServicesContainer $controller */
        $controller = new $servicesContainerClass($this->createConfiguration(), $this->createHtmlHelper(null, true /* in production */));
        self::assertTrue($controller->getPageCache()->isInProduction(), 'Expected page cache to be in production mode');
        $servicesContainerClass = static::getSutClass();
        /** @var ServicesContainer $controller */
        $controller = new $servicesContainerClass($this->createConfiguration(), $this->createHtmlHelper(null, false /* not in production */));
        self::assertFalse($controller->getPageCache()->isInProduction(), 'Expected page cache to be not in production mode');
    }

}