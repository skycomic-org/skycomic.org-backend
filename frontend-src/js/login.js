var initMain = function () {
	require([
		'views/login', 
		'form_mem'
		], function (Login, Form_Mem) {
		window.Login = new Login('login');
		window.Form_mem = Form_Mem;
		Form_Mem.init();
	});
};

var init = function () {
	require(['jquery', 'underscore', 'backbone', 'bootstrap', 'jquery_scroll'], function () {
		initMain();
	});
};

require(['config'], function () {
	init();
});
