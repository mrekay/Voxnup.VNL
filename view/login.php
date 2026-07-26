<div class="card w-100 mw-400px">
    <div class="card-body p-10">

        <div class="text-center mb-10">
            <img src="<?= $cDefinitions->appLogoPath() . 'VNLBIG.png' ?>" alt="<?= $cDefinitions->appName() ?>"
                 class="h-45px mb-5"/>
            <h1 class="fs-2x fw-bold mb-2"><?= lang('LOGIN_TITLE') ?></h1>
            <div class="text-body-secondary"><?= lang('LOGIN_SUBTITLE') ?></div>
        </div>

        <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" method="post"
              action="app/login/login" onsubmit="submitForm(); return false;">

            <div class="mb-5">
                <input type="text" placeholder="<?= lang('LOGIN_USERNAME') ?>" name="email"
                       autocomplete="username" class="form-control form-control-solid"/>
            </div>

            <div class="mb-8 show-password">
                <input type="password" placeholder="<?= lang('LOGIN_PASSWORD') ?>" name="password"
                       autocomplete="current-password" class="form-control form-control-solid"/>
            </div>

            <div class="d-grid">
                <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                    <span class="indicator-label"><?= lang('BTN_LOGIN') ?></span>
                    <span class="indicator-progress"><?= lang('BTN_LOADING') ?>
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>

        </form>

    </div>
</div>
