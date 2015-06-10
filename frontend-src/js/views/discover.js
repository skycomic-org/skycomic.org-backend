define([
  'api',
  'layout',
  'text!templates/discover.html',
  'string'
  ], function (API, Layout, discoverT) {
	var View = function () {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),

			thumbnail: CDN_LINK + 'images/thumbnail/',
			
			discoverT: _.template(discoverT),

			render: function() {
				var self = this;
				ajaxLoader(self.el);

				API.read('new', function (data) {
					try{
						data.thumbnail = self.thumbnail;
						self.el.html(self.discoverT(data));	
					} catch (e) {
						// console.log(e);
					}
				});
				Layout.two('fb');
				window.changeTitle('探索');
			}
		});
		return new AppView();
	};
	
	return View;
});