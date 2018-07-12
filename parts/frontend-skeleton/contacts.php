<?php /** @var \DrdPlus\FrontendSkeleton\FrontendController $controller */ ?>
  <div class="contacts visible top <?php if ($controller->isContactsFixed()) { ?>fixed<?php } ?> permanent" id="contacts">
    <div class="container">
        <?php if ($controller->isShownHomeButton()) { ?>
          <span class="menu">
                    <a id="homeButton" class="internal" href="https://www.drdplus.info">
                        <img class="home" src="/images/generic/skeleton/frontend-drd-plus-dragon-menu-2x22.png">
                    </a>
                </span>
        <?php } ?>
      <div class="version">
          <?php
          $webVersions = $controller->getWebVersions();
          $allVersions = $webVersions->getAllVersions();
          if (\count($allVersions) > 1) {
              $currentVersion = $webVersions->getCurrentVersion(); ?>
            <span class="current-version"><?= $webVersions->getVersionName($currentVersion) ?></span>
            <ul class="other-versions">
                <?php
                $request = $controller->getRequest();
                foreach ($webVersions->getAllVersions() as $webVersion) {
                    if ($webVersion === $currentVersion) {
                        continue;
                    } ?>
                  <li>
                    <a href="<?= $request->getCurrentUrl(['version' => $webVersion]) ?>">
                        <?= $webVersions->getVersionName($webVersion) ?>
                    </a>
                  </li>
                <?php } ?>
            </ul>
          <?php } ?>
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
<?php if (empty($contactsBottom) /* contacts are top */) { ?>
  <div class="contacts-placeholder invisible">
    Placeholder for contacts
  </div>
<?php } ?>