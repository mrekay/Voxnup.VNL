
(function ($) {
	$.fn.fixedPanelTitle = function () {
		$items = this;
		$(window).scroll(function () {
			$items.each(function () {
				var title_name = '.panel-heading';
				if ($(this).find(title_name).length === 0) {
					if ($(this).find('.portlet-header').length > 0) {
						title_name = '.portlet-header';
					}
				}

				if ($(window).scrollTop() >= $(this).offset().top && $(window).scrollTop() <= ($(this).offset().top + $(this).outerHeight() - $(this).find(title_name).outerHeight())) {
					var panel_width = $(this).width()+0.2;
					$(this).find(title_name).css('width', panel_width + 'px').addClass('panel-heading-fixed-top');
					$(this).css('padding-top', $(this).find(title_name).outerHeight() + 'px');
				}
				else {
					$(this).css('padding-top', 0);
					$(this).find(title_name).removeAttr('style').removeClass('panel-heading-fixed-top');
				}
			});
		});

		$(window).resize(function () {
			$items.each(function () {
				var title_name = '.panel-heading';
				if ($(this).find(title_name).length === 0) {
					if ($(this).find('.portlet-header').length > 0) {
						title_name = '.portlet-header';
					}
				}
				var panel_width = $(this).width()+0.2;
				$(this).find(title_name).css('width', panel_width + 'px');
			});
		});

		$('ul.nav-tabs li a').click(function () {
			$items.each(function () {
				$(this).css('visibility', 'hidden');
			});
			// setTimeout(function () {
				// console.log('tab-clicked');
				$items.each(function () {
					var title_name = '.panel-heading';
					if ($(this).find(title_name).length === 0) {
						if ($(this).find('.portlet-header').length > 0) {
							title_name = '.portlet-header';
						}
					}
					$(this).css('padding-top', 0);
					$(this).find(title_name).removeAttr('style').removeClass('panel-heading-fixed-top');
					$(this).css('visibility', 'visible');
				});
			// }, 10);
		});


		return this;
	};
}(jQuery));

