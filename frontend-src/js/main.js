var initMain = function () {
	require([
		'router',
		'comic_queue',
		'views/browse',
		'searchbar',
		'tabs', 'tools/utils', 'tools/image_cache', 'views/widget/comic_slot'
	], function (Router, ComicQueue, BrowseView, Searchbar) {
		// initialize searchbar
		Searchbar.init();

		// initialize comic queue
		window.comic_queue = ComicQueue();

		// Instantiate the router
		window.app_router = new Router;

		// Start Backbone history a neccesary step for bookmarkable URL's
		Backbone.history.start();

		// comic queue need browse utility...Q_Q
		window.__browse = window.__browse || BrowseView();
	});
};

var init = function () {
	require(['jquery', 'underscore', 'backbone', 'bootstrap', 'jquery_scroll'], function () {
		initMain();
	});
};

require(['config'], function () {
	init();
});
