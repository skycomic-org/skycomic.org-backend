define([
  'api',
  'layout',
  'tools/favorite',
  'text!templates/favorite.html',
  'string'
  ], function (API, Layout, Favorite, FavoriteT) {
	var View = function () {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			
			template: _.template(FavoriteT),

			render: function() {
				var self = this;
				ajaxLoader(self.$el);

				Favorite.read(function (data) {
					try{
						self.$el.html(self.template({ 
							favorites: data
						}));
					} catch (e) {
						// console.log(e);
					}
				});
				Layout.two('fb');
				window.changeTitle('我的最愛');
			}
		});
		return new AppView();
	};
	
	return View;
});