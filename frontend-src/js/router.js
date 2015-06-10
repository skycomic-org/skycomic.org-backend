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
            ":route/:action/*param": "loadView",
            ":route/:action": "loadView",
            ":route": "loadView"
        },

        loadView: function ( route, action, param ) {
			var self = this;
			errHide();

			if (!route) {
				this.navigate(USERNAME == 'guest' ? '/discover' : '/favorite', {trigger: true});
				return;
			}
			// 選單處理
			var li = self.el.find('li[data-link="'+ location.hash +'"]');
			if ( li ) {
				li.addClass('active');
				self.el.find('li[data-link!="'+ location.hash +'"]').removeClass('active');
			}

			// lastview leave function
			if (self.lastview && typeof window[self.lastview].leave !== 'undefined') {
				window[self.lastview].leave(route);
			}
			
			var req = 'views/'+ route,
				windowobj = '__' + route;

			// global page info for views
			self.nowpage = route;
			self.lastpage = self.lastview ? self.lastview.substr(2) : '';

			// setting page
			window.comic_queue.page(route);
			
			// 讀取相對應的view檔
			require([req], function (View) {
				if (!window[windowobj]) {
					window[windowobj] = View();
				}
				window[windowobj].render(action, param);
				// trigger google analytics
				_gaq.push(['_trackPageview']);
				self.lastview = windowobj;
			});
		}
    });

	return AppRouter;
});
