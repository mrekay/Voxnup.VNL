<script>


	$('input').keyup(function (e){
		var code = e.keyCode || e.which;
		if(code == 13) { //Enter keycode
			submitForm();
		}
	});

	function submitForm() {
		// spinnerShow();
		var form = $('form[name=singinform]');
		var formData = form.serialize();

		$.ajax({
			type    : 'POST',
			dataType: 'json',
			url     : 'app/login/login',
			data    : formData,
			success : function (cevap) {
                showModalMessage(cevap.message, false);
					setTimeout(function () {
						window.location.href = '/index';
					}, 2000);

			}, error: function (err) {
                showModalMessage(err.message,true);
			}
		});
		return false;
	}

</script>
