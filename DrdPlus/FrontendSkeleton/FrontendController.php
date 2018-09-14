<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DrdPlus\FrontendSkeleton\Web\Content;
use Granam\Strict\Object\StrictObject;

class FrontendController extends StrictObject
{
    /** @var ServicesContainer */
    private $servicesContainer;
    /** @var array */
    private $bodyClasses;
    /** @var WebCache */
    protected $pageCache;
    /** @var Content */
    protected $content;

    public function __construct(ServicesContainer $servicesContainer, array $bodyClasses = [])
    {
        $this->servicesContainer = $servicesContainer;
        $this->bodyClasses = $bodyClasses;
    }

    protected function getServicesContainer(): ServicesContainer
    {
        return $this->servicesContainer;
    }

    protected function getConfiguration(): Configuration
    {
        return $this->getServicesContainer()->getConfiguration();
    }

    public function getBodyClasses(): array
    {
        return $this->bodyClasses;
    }

    public function addBodyClass(string $class): void
    {
        $this->bodyClasses[] = $class;
    }

    public function isMenuPositionFixed(): bool
    {
        return $this->getConfiguration()->isMenuPositionFixed();
    }

    public function isShownHomeButton(): bool
    {
        return $this->getConfiguration()->isShowHomeButton();
    }

    public function isRequestedWebVersionUpdate(): bool
    {
        return $this->getServicesContainer()->getRequest()->getValue(Request::UPDATE) === 'web';
    }

    public function updateWebVersion(): int
    {
        $updatedVersions = 0;
        // sadly we do not know which version has been updated, so we will update all of them
        foreach ($this->getServicesContainer()->getWebVersions()->getAllMinorVersions() as $version) {
            $this->getServicesContainer()->getWebVersions()->update($version);
            $updatedVersions++;
        }

        return $updatedVersions;
    }

    public function persistCurrentVersion(): bool
    {
        return $this->getServicesContainer()->getCookiesService()->setMinorVersionCookie(
            $this->getServicesContainer()->getWebVersions()->getCurrentMinorVersion()
        );
    }

    public function getContent(): Content
    {
        if ($this->content) {
            return $this->content;
        }
        $servicesContainer = $this->getServicesContainer();
        if ($servicesContainer->getRequest()->areRequestedTables()) {
            $this->content = new Content(
                $servicesContainer->getHtmlHelper(),
                $servicesContainer->getWebVersions(),
                $servicesContainer->getHeadForTables(),
                $servicesContainer->getMenu(),
                $servicesContainer->getTablesBody(),
                $servicesContainer->getTablesWebCache(),
                Content::TABLES
            );

            return $this->content;
        }

        $this->content = new Content(
            $servicesContainer->getHtmlHelper(),
            $servicesContainer->getWebVersions(),
            $servicesContainer->getHead(),
            $servicesContainer->getMenu(),
            $servicesContainer->getBody(),
            $servicesContainer->getWebCache(),
            Content::FULL
        );

        return $this->content;
    }
}