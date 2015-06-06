define([], function () {
	return {
		one: function () {
			window.onresize = function () {};
			$('#sidebar').hide().removeClass('visible-desktop');
			$('#content').attr('style', "");
			$('#real-content').attr('style', "");
		},
		two: function (sidebar) {
			$('#sidebar').show().addClass('visible-desktop');
				window.onresize = function(event) {
					var minus = $(window).width() > 980 ? 340 : 50;
					$('#content').attr('style', "width: " + ($(window).width() - minus) + "px");
					$('#real-content').attr('style', "width: " + ($(window).width() - minus - 125) + "px");
				}
				window.onresize();
			require(['views/sidebar/' + sidebar], function (SideBar) {
				SideBar.render();
			});
		}
	};
});