<script>
    // Clickjacking koruması: sayfa bir frame içine alınmışsa dışarı çık
    if (window.top != window.self) {
        window.top.location.replace(window.self.location.href);
    }

    $('input').keyup(function (e) {
        var code = e.keyCode || e.which;
        if (code == 13) { //Enter keycode
            submitForm();
        }
    });

    function submitForm() {
        var form = $('#kt_sign_in_form');
        var formData = form.serialize();
        var button = $('#kt_sign_in_submit');

        button.attr('data-vnl-indicator', 'on').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/login/login',
            data: formData,
            success: function (cevap) {
                showModalMessage(cevap.message, false);
                setTimeout(function () {
                    window.location.href = '/index';
                }, 2000);

            }, error: function (err) {
                button.removeAttr('data-vnl-indicator').prop('disabled', false);
                if (err.responseJSON && err.responseJSON.message) {
                    showModalMessage(err.responseJSON.message, true);
                } else {
                    // JS dil köprüsü kaldırıldı; metin sunucuda lang() ile gömülüyor.
                    showModalMessage(<?= json_encode(lang('ERR_GENERAL'), JSON_UNESCAPED_UNICODE) ?>, true);
                }
            }
        });
        return false;
    }

</script>
