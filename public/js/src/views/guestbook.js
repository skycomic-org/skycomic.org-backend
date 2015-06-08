define([
  'layout',
  'text!templates/guestbook.html'
  ], function (Layout, Template) {

	var View = function () {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			Template: _.template(Template),
			
			render: function(tid) {
				$(this.el).html(this.Template());
				Layout.two('fb');
			}
		});
		return new AppView();
	};
	
	return View;
});