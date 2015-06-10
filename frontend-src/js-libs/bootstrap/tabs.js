define([], function () {
	var $tabs = [$('.nav-tabs > li'), $('.pills > li')];
	_.each($tabs, function ($dom) {
		$dom.find('a').live('mouseenter', function () {
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
