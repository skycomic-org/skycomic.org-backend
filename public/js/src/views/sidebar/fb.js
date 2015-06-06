define([
  'api',
  'text!templates/sidebar.html'
  ], function (API, T_Sidebar) {
	var data = [],
		template = _.template(T_Sidebar),
		el = $('#sidebar');
	return {
		refresh_data: function () {
			ajaxLoader(el);
			API.read('fb_page', function (fb_data) {
				data = fb_data;
				el.html(template({data:data}));
			});
		},
		
		render: function () {
			if ( !window.sidebar || window.sidebar != 'plurk' ) {
				window.sidebar = 'plurk';
				this.refresh_data();
			}
			el.show();
		}
	};
});