<div class="keyword">
    <?php
    /** @var \DrdPlus\FrontendSkeleton\FrontendController $controller */
    echo $controller->getDirs()->getDocumentRoot();
    ?>
</div>
<div>
    <?php if ($controller->getWebVersions()->isCurrentVersionStable()) { ?>
      Stabilní verze <strong><?= $controller->getCurrentVersion() ?></strong>
    <?php } else { ?>
      Vývojová verze <strong><?= $controller->getCurrentVersion() ?></strong>
    <?php } ?>
</div>

<div class="modal fade" id="confirmOwnership" tabindex="-1" role="dialog" aria-labelledby="confirmOwnershipModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmOwnershipModalLabel">Vlastním <?= $controller->getWebName() ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        BOy
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Zavřít</button>
        <button type="button" class="btn btn-primary">Potvrzuji</button>
      </div>
    </div>
  </div>
</div>

<p><a data-toggle="modal" href="#confirmOwnership" class="btn btn-primary btn-large">Launch demo modal</a></p>