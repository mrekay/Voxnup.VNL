<script>
    // Clickjacking koruması: sayfa bir frame içine alınmışsa dışarı çık
    if (window.top != window.self) {
        window.top.location.replace(window.self.location.href);
    }

    /* İki adımlı giriş (3.4.0): parola adımı {"data":{"step":"totp"}} dönerse
       kod alanı açılır ve sonraki gönderim /app/login/totp'a gider.
       CSRF başlığını vnl.js her isteğe kendiliğinden ekler. */
    var vnlLoginStep = 'password';

    $('#kt_sign_in_form input').keyup(function (e) {
        var code = e.keyCode || e.which;
        if (code == 13) { //Enter keycode
            submitForm();
        }
    });

    function submitForm() {
        var form = $('#kt_sign_in_form');
        var button = $('#kt_sign_in_submit');

        if (button.prop('disabled')) return false;

        var url = vnlLoginStep === 'totp' ? '/app/login/totp' : '/app/login/login';
        var formData = vnlLoginStep === 'totp'
            ? {code: form.find('[name=code]').val()}
            : form.find('#login_step_password :input').serialize();

        button.attr('data-vnl-indicator', 'on').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: url,
            data: formData,
            customErrMessage: true,
            success: function (cevap) {
                if (cevap.data && cevap.data.step === 'totp') {
                    vnlLoginStep = 'totp';
                    button.removeAttr('data-vnl-indicator').prop('disabled', false);
                    $('#login_step_password').addClass('d-none');
                    $('#login_step_totp').removeClass('d-none').find('input').val('').trigger('focus');
                    return;
                }

                window.location.href = (cevap.data && cevap.data.url) ? cevap.data.url : '/';

            }, error: function (err) {
                button.removeAttr('data-vnl-indicator').prop('disabled', false);

                /* Bekleyen ikinci adımın süresi dolduysa baştan başla. */
                if (err.responseJSON && err.responseJSON.devcode === 'VNL_TOTP_EXPIRED') {
                    vnlLoginStep = 'password';
                    $('#login_step_totp').addClass('d-none');
                    $('#login_step_password').removeClass('d-none');
                }

                /* Sunucu metni HTML olarak değil düz metin olarak basılır. */
                var message = (err.responseJSON && err.responseJSON.message)
                    ? err.responseJSON.message
                    : <?= json_encode(lang('ERR_GENERAL'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

                showModalMessage($('<div>').text(message).html(), true);
            }
        });
        return false;
    }

</script>
