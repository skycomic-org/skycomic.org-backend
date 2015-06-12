define([], function () {
	$(document).on('click', '.horizon-tabs > li > a', function () {
		var $li = $(this).parent(),
			$activeLi = $li.parent().find('li.active');
		$($activeLi.attr('data-tab')).removeClass('active');
		$($li.attr('data-tab')).addClass('active');
		$activeLi.removeClass('active');
		$li.addClass('active');
	});
});