define([
  'api',
  'layout',
  'text!templates/author.html',
  ], function (API, Layout, T_author) {

	var View = function () {
		var AppView = Backbone.View.extend({

			el: $("#real-content"),

			T_author: _.template(T_author),

			render: function(author_id) {
				var self = this;
				API.read('author/'+ author_id, function (data) {
					self.el.html(self.T_author(data));
				});
				Layout.two('fb');
			}
		});
		return new AppView();
	};
	
	return View;
});