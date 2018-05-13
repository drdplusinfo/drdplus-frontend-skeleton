<?php
$contactsClasses = ['position' => 'top'];
if (!empty($contactsBottom)) {
    $contactsClasses['position'] = 'bottom';
}
if (!empty($contactsFixed)) {
    $contactsClasses['fixed'] = 'fixed';
}
?>
    <div class="contacts visible <?= implode(' ', $contactsClasses) ?> permanent" id="contacts">
        <div class="container">
            <?php if (empty($hideHomeButton)) { ?>
                <span class="menu">
                    <a class="internal" href="https://www.drdplus.info">
                        <img class="home" src="images/generic/drd-plus-dragon-menu-2x22.png">
                    </a>
                </span>
            <?php } ?>
            <div class="version">
                <?php /** @var \DrdPlus\FrontendSkeleton\Versions $rulesVersions */
                $allVersions = $rulesVersions->getAllVersions();
                if (count($allVersions) > 1) {
                    $currentVersion = $rulesVersions->getCurrentVersion(); ?>
                    <span class="current-version"><?= $rulesVersions->getVersionName($currentVersion) ?></span>
                    <ul class="other-versions">
                        <?php /** @var \DrdPlus\FrontendSkeleton\Versions $rulesVersions */
                        /** @var \DrdPlus\FrontendSkeleton\Request $request */
                        foreach ($rulesVersions->getAllVersions() as $version) {
                            if ($version === $currentVersion) {
                                continue;
                            } ?>
                            <li><a href="<?= $request->getCurrentUrl(['version' => $version]) ?>">
                                    <?= $rulesVersions->getVersionName($version) ?>
                                </a></li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
            <span class="contact"><a href="mailto:info@drdplus.info">info@drdplus.info</a></span>
            <span class="contact"><a target="_blank" class="rpgforum-contact"
                                     href="https://rpgforum.cz/forum/viewtopic.php?f=238&t=14870">RPG fórum</a></span>
            <span class="contact"><a target="_blank" class="facebook-contact"
                                     href="https://www.facebook.com/drdplus.info">Facebook</a></span>
        </div>
    </div>
<?php if (empty($contactsBottom) /* contacts are top */) { ?>
    <div class="contacts-placeholder invisible">
        Placeholder for contacts
    </div>
<?php } ?>