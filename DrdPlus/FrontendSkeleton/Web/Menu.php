<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton\Web;

use DrdPlus\FrontendSkeleton\Configuration;
use DrdPlus\FrontendSkeleton\Request;
use DrdPlus\FrontendSkeleton\WebVersions;
use Granam\Strict\Object\StrictObject;

class Menu extends StrictObject
{
    /** @var Configuration */
    private $configuration;
    /** @var WebVersions */
    private $webVersions;
    /** @var Request */
    private $request;

    public function __construct(Configuration $configuration, WebVersions $webVersions, Request $request)
    {
        $this->configuration = $configuration;
        $this->webVersions = $webVersions;
        $this->request = $request;
    }

    public function __toString()
    {
        return $this->getMenuString();
    }

    public function getMenuString(): string
    {
        $fixed = $this->getConfiguration()->isMenuPositionFixed()
            ? 'fixed'
            : '';
        $homeButton = '';
        if ($this->getConfiguration()->isShowHomeButton()) {
            $homeButton = <<<HTML
<span class="menu">
    <a id="homeButton" class="internal-url" href="https://www.drdplus.info">
        <img class="home" src="/images/generic/skeleton/frontend-drd-plus-dragon-menu-2x22.png">
    </a>
</span>
HTML;
        }
        $webVersions = $this->getWebVersions();
        $allVersions = $webVersions->getAllMinorVersions();
        $versions = '';
        if (\count($allVersions) > 1) {
            $currentVersion = $webVersions->getCurrentMinorVersion();
            $otherVersionsArray = [];
            foreach ($webVersions->getAllMinorVersions() as $webVersion) {
                if ($webVersion === $currentVersion) {
                    continue;
                }
                $otherVersionsArray[] = <<<HTML
<li>
    <a href="{$this->getRequest()->getCurrentUrl([Request::VERSION => $webVersion])}">
        {$webVersions->getVersionHumanName($webVersion)}
    </a>
</li>
HTML;
            }
            $otherVersions = \implode("\n", $otherVersionsArray);
            $versions = <<<HTML
<span class="current-version">{$webVersions->getVersionHumanName($currentVersion)}</span>
<ul class="other-versions">
  {$otherVersions}
</ul>
HTML;
        }

        return <<<HTML
  <div class="contacts visible top permanent $fixed" id="menu">
    <div class="container">
      {$homeButton}
      <div class="version">
          {$versions}
      </div>
      <span class="contact">
        <a href="mailto:info@drdplus.info">
          <span class="mobile"><i class="fas fa-envelope"></i></span>
          <span class="tablet">info@drdplus.info</span>
          <span class="desktop"><i class="fas fa-envelope"></i> info@drdplus.info</span>
        </a>
      </span>
      <span class="contact">
        <a target="_blank" class="rpgforum-contact" href="https://rpgforum.cz/forum/viewtopic.php?f=238&t=14870">
          <span class="mobile"><i class="fas fa-dice-six"></i></span>
          <span class="tablet">RPG fórum</span>
          <span class="desktop"><i class="fas fa-dice-six"></i> RPG fórum</span>
        </a>
      </span>
      <span class="contact">
        <a target="_blank" class="facebook-contact" href="https://www.facebook.com/drdplus.info">
          <span class="mobile"><i class="fab fa-facebook-square"></i></span>
          <span class="tablet">Facebook</span>
          <span class="desktop"><i class="fab fa-facebook-square"></i> Facebook</span>
        </a>
      </span>
    </div>
  </div>
  <div class="contacts-placeholder invisible">
    Placeholder for contacts
  </div>
HTML;
    }

    protected function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    protected function getWebVersions(): WebVersions
    {
        return $this->webVersions;
    }

    protected function getRequest(): Request
    {
        return $this->request;
    }
}