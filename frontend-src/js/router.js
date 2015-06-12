define([
'views/sidebar/fb',
'views/app',
'views/author',
'views/browse',
'views/comics',
'views/discover',
'views/donation',
'views/favorite',
'views/history',
'views/login',
'views/search',
'views/title',
'views/vtitle'
], function () {

var AppRouter = Backbone.Router.extend({
	lastview: false,

	el: $('body .nav'),

	routes: {
		"": "loadView",
		":route/:action/*param": "loadView",
		":route/:action": "loadView",
		":route": "loadView"
	},

	views: {},

	loadView: function ( page, action, param ) {
		errHide();

		if (!page) {
			this.navigate(USERNAME == 'guest' ? '/discover' : '/favorite', {trigger: true});
			return;
		}
		// 選單處理
		var li = this.el.find('li[data-link="'+ location.hash +'"]');
		if ( li ) {
			li.addClass('active');
			this.el.find('li[data-link!="'+ location.hash +'"]').removeClass('active');
		}

		// lastview leave function
		if (this.lastview && typeof this.views[this.lastview].leave !== 'undefined') {
			this.views[this.lastview].leave(page);
		}
		
		var req = 'views/'+ page;

		// global page info for views
		this.nowpage = page;
		this.lastpage = this.lastview ? this.lastview.substr(2) : '';

		// setting page
		window.comic_queue.page(page);
		
		// 讀取相對應的view檔
		require([req], function (View) {
			if (!this.views[page]) {
				this.views[page] = View();
			}
			this.views[page].render(action, param);
			// trigger google analytics
			_gaq.push(['_trackPageview']);
			this.lastview = page;
		}.bind(this));
	}
});

return AppRouter;

});
