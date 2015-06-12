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
				ajaxLoader(this.$el);

				API.read('new', function (data) {
					data.thumbnail = this.thumbnail;
					this.$el.html(this.discoverT(data));
				}.bind(this));
				Layout.two('fb');
				window.changeTitle('探索');
			}
		});
		return new AppView();
	};

	return View;
});