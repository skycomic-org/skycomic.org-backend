define([
  'api'
  ], function (API) {

	var View = function () {
		var AppView = Backbone.View.extend({
			render: function(tid) {
				var self = this;
				API.read('tid2vtid/'+ tid, function (vtid) {
					window.app_router.navigate('/vtitle/' + vtid + '/' + tid, {trigger: true, replace: true});
				});
			}
		});
		return new AppView();
	};
	
	return View;
});
