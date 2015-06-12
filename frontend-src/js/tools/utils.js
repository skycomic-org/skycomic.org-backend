define(['jquery'], function () {
	var el = $('#global-err'),
		span = el.find('span');

	el.find('a').click(function () {
		el.hide();
		return false;
	});

	window.err = function (msg, level, seconds, html) {
		html ? span.html(msg) : span.text(msg);
		if (level) {
			el.attr('class', 'alert alert-' + level);
		} else {
			el.attr('class', 'alert alert-error');
		}
		el.show();
		if (seconds) {
			setTimeout(function () {
				el.hide();
			}, parseInt(seconds, 10) * 1000);
		}
	};

	window.errHide = function () { el.hide() };

	// ajaxLoader utility
	window.ajaxLoader = function (el) {
		el.html('<p class="center"><img src="image/loader.gif"></p>');
	};

	// ChangeTitle utility
	window.changeTitle = function (title) {
		document.title = title ? title + ' - SkyComic, 地表最快的漫畫網站' : 'SkyComic, 地表最快的漫畫網站';
	};

	// alert initlize
	$(".alert-message") && $(".alert-message").alert();
	
	// special utility(好怪)
	$(document).on('click', "a[data-false='true']", function () {
		return false;
	});
});
