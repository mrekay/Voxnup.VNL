<div class="card w-100 mw-400px">
    <div class="card-body p-10">

        <div class="text-center mb-10">
            <img src="<?= $cDefinitions->appLogoPath() . 'VNL.png' ?>" alt="" class="h-45px mb-6 rounded-3"/>
            <div class="vnl-overline mb-2">// login</div>
            <h1 class="fs-2x fw-bold mb-2"><?= lang('LOGIN_TITLE') ?></h1>
            <div class="text-body-secondary"><?= lang('LOGIN_SUBTITLE') ?></div>
        </div>

        <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" method="post"
              action="app/login/login" onsubmit="submitForm(); return false;">

            <div id="login_step_password">
                <div class="mb-5">
                    <input type="text" placeholder="<?= lang('LOGIN_USERNAME') ?>" name="email"
                           autocomplete="username" class="form-control form-control-solid"/>
                </div>

                <div class="mb-5 show-password">
                    <input type="password" placeholder="<?= lang('LOGIN_PASSWORD') ?>" name="password"
                           autocomplete="current-password" class="form-control form-control-solid"/>
                </div>

                <?php if (UserModule::rememberDays() > 0) { ?>
                    <div class="form-check mb-8">
                        <input class="form-check-input" type="checkbox" name="rememberMe" value="1" id="inp_remember_me">
                        <label class="form-check-label" for="inp_remember_me"><?= lang('LOGIN_REMEMBER_ME') ?></label>
                    </div>
                <?php } else { ?>
                    <div class="mb-8"></div>
                <?php } ?>
            </div>

            <!-- İki adımlı doğrulama (3.4.0): parola doğruysa ve hesapta TOTP açıksa görünür. -->
            <div id="login_step_totp" class="mb-8 d-none">
                <div class="text-body-secondary mb-3"><?= lang('LOGIN_TOTP_HINT') ?></div>
                <input type="text" placeholder="<?= lang('LOGIN_TOTP_CODE') ?>" name="code"
                       inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*"
                       class="form-control form-control-solid text-center fs-3 vnl-code"/>
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
