/*
	*
	*	PRICE MASK
	*
	* */


	function setSelectionRange(input, selectionStart, selectionEnd) {
		if (input.setSelectionRange) {
			input.focus();
			input.setSelectionRange(selectionStart, selectionEnd);
		}
		else if (input.createTextRange) {
			var range = input.createTextRange();
			range.collapse(true);
			range.moveEnd('character', selectionEnd);
			range.moveStart('character', selectionStart);
			range.select();
		}
	}

	function setCaretToPos(input, pos) {
		setSelectionRange(input, pos, pos);
	}

	$(document).on("keydown", "input[money]", function (e) {
		var val = $(this).val();
		var valLength = $(this).val().length;
		var dotPosition = valLength - 3;
		var key = e.which;
		var kusurat = '.00';
		var cPosition = e.target.selectionStart;

		if (getOS() == 'MacOS') {
			e.ctrlKey = e.metaKey;
		}

		var selected_all = e.target.selectionStart === 0 && e.target.selectionEnd === valLength;

		if (
			(key > 47 && key < 59) || (key > 95 && key < 106)
		) {
			if (e.shiftKey) {
				return false;
			}

			if (!e.ctrlKey && !selected_all && e.target.selectionStart < dotPosition + 1 && e.target.selectionEnd > dotPosition) {
				// seçilen kısımda "." mevcut
				return false;
			}


			if (selected_all) {
				$(this).val('');
			}

//			console.log(cPosition + ' -- ' + valLength);
			if (valLength > 0 && cPosition >= valLength) {
				return false;
			}
			else if (cPosition > dotPosition) {
				setSelectionRange($(this)[0], cPosition, cPosition + 1);
			}

			var s = ($(this).val() == '');
			var vvv = $(this).val() + e.key;
			if (s) {
				vvv += kusurat;
				$(this).val(vvv);
				setCaretToPos($(this)[0], 1);
			}
			else {
				return true;
			}

			return false;

		}

		else if ((key == 65 && e.ctrlKey) || (key == 67 && e.ctrlKey) || (key == 86 && e.ctrlKey) || (key > 36 && key < 41) || (key > 111 && key < 124) || (key > 34 && key < 37) || key === 9 || key === 13 || key === 27) {
			// CTRL + A, CTRL + C, CTRL + V,  arrows, fn, home-end , tab, enter, esc
			return true;
		}
		else if (key == 44 || key == 110 || key == 188 || key == 190) {
			if (selected_all) {
				$(this).val('');
			}

			if (!e.ctrlKey && !selected_all && e.target.selectionStart < dotPosition + 1 && e.target.selectionEnd > dotPosition) {
				// seçilen kısımda "." mevcut
				return false;
			}

			if (cPosition == dotPosition) {
				setCaretToPos($(this)[0], cPosition + 1);
				return false;
			}
			else {
				return false;
			}

		}
		else if (key == 8) {
			// backspace
			if (selected_all) {
				$(this).val('');
			}

			if (!e.ctrlKey && !selected_all && e.target.selectionStart < dotPosition + 1 && e.target.selectionEnd > dotPosition) {
				// seçilen kısımda "." mevcut
				return false;
			}


			cPosition = e.target.selectionEnd;//seçimli durum için silme işlemi tersden
			if (cPosition - dotPosition == 2) {
				$(this).val($(this).val() + '0');
				setSelectionRange($(this)[0], valLength - 2, valLength - 1);
				return true;
			}
			else if (cPosition - dotPosition == 3) {
				$(this).val($(this).val() + '0');
				setSelectionRange($(this)[0], valLength - 1, valLength);
				return true;
			}
			else if (cPosition - dotPosition == 1) {

				setCaretToPos($(this)[0], cPosition - 1);
				return false;
			}
			else {
				return true;
			}

		}
		else if (key == 46) {
			// delete
			if (selected_all) {
				$(this).val('');
			}
			// console.log(val.substr(valLength - 2, valLength));
//			console.log(val.substr(cPosition, cPosition + 1));


			if (!e.ctrlKey && !selected_all && e.target.selectionStart < dotPosition + 1 && e.target.selectionEnd > dotPosition) {
				// seçilen kısımda "." mevcut
				return false;
			}


			if (cPosition - dotPosition == 1) {
				$(this).val($(this).val() + '0');
				setSelectionRange($(this)[0], valLength - 2, valLength - 1);
				return true;
			}
			else if (cPosition - dotPosition == 2) {
				$(this).val($(this).val() + '0');
				setSelectionRange($(this)[0], valLength - 1, valLength);
				return true;
			}
			else if (cPosition - dotPosition == 0) {
				setCaretToPos($(this)[0], cPosition + 1);
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return false;
		}
	});

	$(document).on("paste", "input[money]", function (e) {
		var pastedData = e.originalEvent.clipboardData.getData('text');
		$(this).val(formatCurrency(pastedData));
		return false;
	});

	$(document).on("focus", "input[money]", function () {
		var val = $(this).val();
		$(this).val(formatCurrency(val));
		return false;
	});

	function formatCurrency(total) {
		if (total < 0) {
			total = Math.abs(total);
		}
		total = total.replace(/[,]/g, '.'); // , => . çevir


		var result = total.split('.');
		var resultLength = result.length;

		var isDot = (resultLength > 1 ? true : false);


		var s = '';
		if (isDot) { // Veride Nokta Var.
			var i = 0;
			$.each(result, function (key, val) {
				i++;
				if (resultLength == i) { // Son Nokta.
					s += '.' + val;
				}
				else {
					s += val;
				}
			});
			total = s;
		}

		//  1.5246,50
		// 15212
		// 1.1.1.20
		//
		// 256.25
		// 256,25723
		$res_format = parseFloat(total, 10).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1").toString();
		return ($res_format === "NaN" ? '' : $res_format);
	}

	/*
	*
	*	PRICE MASK END
	*
	* */