define([
  'api',
  'layout',
  'text!templates/history.html',
  'string'
  ], function (API, Layout, Template) {
	var View = function () {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			
			template: _.template(Template),

			render: function() {
				var self = this;
				ajaxLoader(self.$el);

				API.read('view_record', function (data) {
					try{
						self.$el.html(self.template({ 
							records: data
						}));
					} catch (e) {
						// console.log(e);
					}
				});
				Layout.two('fb');
				window.changeTitle('觀看紀錄');
			}
		});
		return new AppView();
	};
	
	return View;
});