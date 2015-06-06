// Require.js allows us to configure shortcut alias
require.config({
	baseUrl: BASE_URL + 'js/' + JS_VERSION,
	paths: {
		text: ['//cdnjs.cloudflare.com/ajax/libs/require-text/2.0.5/text', '../libs/require/text'],
		form_mem: 'tools/form_memorizer'
	}
});

require([
	'views/login', 
	'form_mem'
	], function (Login, Form_Mem) {
	window.Login = new Login('login');
	window.Form_mem = Form_Mem;
	Form_Mem.init();
});