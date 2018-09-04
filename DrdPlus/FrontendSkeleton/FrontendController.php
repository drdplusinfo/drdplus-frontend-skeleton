<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DeviceDetector\Parser\Bot;
use DrdPlus\FrontendSkeleton\Web\Content;
use Granam\Strict\Object\StrictObject;

class FrontendController extends StrictObject
{
    /** @var ServicesContainer */
    private $servicesContainer;
    /** @var Request */
    private $request;
    /** @var array */
    private $bodyClasses;
    /** @var WebCache */
    protected $pageCache;
    /** @var Redirect|null */
    private $redirect;
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

    public function setRedirect(Redirect $redirect): void
    {
        $this->redirect = $redirect;
        $this->content = null; // reload content with new redirect
    }

    public function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }

    protected function getConfiguration(): Configuration
    {
        return $this->getServicesContainer()->getConfiguration();
    }

    protected function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = new Request(new Bot());
        }

        return $this->request;
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
        return $this->getRequest()->getValue(Request::UPDATE) === 'web';
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
        if ($this->content === null) {
            $this->content = new Content(
                $this->getServicesContainer()->getHtmlHelper(),
                $this->getServicesContainer()->getWebVersions(),
                $this->getServicesContainer()->getHead(),
                $this->getServicesContainer()->getMenu(),
                $this->getServicesContainer()->getBody(),
                $this->getServicesContainer()->getWebCache(),
                $this->getRedirect()
            );
        }

        return $this->content;
    }
}