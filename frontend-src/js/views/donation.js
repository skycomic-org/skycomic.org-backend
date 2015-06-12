define([
  'layout',
  'text!templates/donation.html'
  ], function (Layout, Template) {

	var View = function () {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			Template: _.template(Template),
			
			render: function(done) {
				if (done) {
					window.app_router.navigate('/new', {trigger: true});
					setTimeout(function () {
						$('#real-content').prepend('<div class="alert alert-success"><a class="close" href="#">&times;</a>感謝您的贊助！SkyComic因為有你而更加進步:)</div>');
						$(".alert-message").alert();
					}, 1000);
				} else {
					this.$el.html(this.Template());	
				}
				Layout.two('fb');
			}
		});
		return new AppView();
	};
	
	return View;
});