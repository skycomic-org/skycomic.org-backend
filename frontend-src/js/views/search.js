define([
  'api',
  'layout',
  'text!templates/search.html'
  ], function (API, Layout, Template) {

	var View = function (text) {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),

			Template: _.template(Template),

			initialize: function() {
			},
			
			render: function(_text) {
				text = _text;
				var self = this;
				
				API.read('search/'+ encodeURIComponent(text), function (data) {
					self.$el.html(self.Template({data: data}));
					window.changeTitle('搜尋「'+ _text +'」的結果');
				});
				Layout.two('fb');
			}
		});
		return new AppView();
	};
	
	return View;
});
