define([], function () {
	var selectors = ['.nav-tabs > li a', '.pills > li a'];
	_.each(selectors, function (selector) {
		$('document').on('mouseenter', selector, function () {
			var $this = $(this).parent(),
				$active = $this.parent().find('.active');
			$($active.attr('data-tab')).removeClass('active');
			$($this.attr('data-tab')).addClass('active');
			$active.removeClass('active');
			$this.addClass('active');
			return false;
		});
	});
});
