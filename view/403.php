<div class="text-center">
    <img src="<?= $cDefinitions->appLogoPath() . 'VNL.png' ?>" alt="" class="h-60px mb-6 rounded-3"/>
    <div class="vnl-error-code text-danger">403</div>
    <h1 class="fs-2x fw-bold mt-6 mb-3"><?= lang('ERR_403_TITLE') ?></h1>
    <p class="text-body-secondary mw-450px mx-auto mb-9"><?= lang('ERR_403_TEXT') ?></p>
    <a href="/" class="btn btn-primary">
        <i class="bi bi-house me-2"></i><?= lang('LBL_RETURN_HOME') ?>
    </a>
</div>
