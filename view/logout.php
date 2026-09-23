<div class="card w-100 mw-400px">
    <div class="card-body p-10 text-center">
        <img src="<?= $cDefinitions->appLogoPath() . 'VNL.png' ?>" alt="" class="h-45px mb-6 rounded-3"/>
        <h1 class="fs-2x fw-bold mb-3"><?= lang('LOGOUT_TITLE') ?></h1>
        <p class="text-body-secondary mb-8"><?= lang('LOGOUT_TEXT') ?></p>

        <div class="d-flex gap-3 justify-content-center">
            <a href="/" class="btn btn-soft-secondary"><?= lang('BTN_CANCEL') ?></a>
            <button type="button" class="btn btn-primary" data-vnl-logout>
                <i class="bi bi-box-arrow-right me-2"></i><?= lang('LBL_SIGN_OUT') ?>
            </button>
        </div>
    </div>
</div>
