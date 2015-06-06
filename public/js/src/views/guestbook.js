define([
  'text!templates/guestbook.html'
  ], function (Template) {

	var View = function () {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			Template: _.template(Template),
			
			render: function(tid) {
				$(this.el).html(this.Template());
			}
		});
		return new AppView();
	};
	
	return View;
});