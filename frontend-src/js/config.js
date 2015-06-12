require.config({
	shim: {
		backbone: {
			deps: ['underscore', 'jquery'],
			exports: 'Backbone'
		},
		underscore: {
			exports: '_'
		},
		jquery: {
			exports: ['jQuery', '$']
		},
		jquery_scroll: {
			deps: ['jquery']
		},
		bootstrap: {
			deps: ['jquery']
		}
	},
	paths: {
		// libs
		jquery: [BASE_URL + 'js/libs/jquery-min'],
		underscore: [BASE_URL + 'js/libs/underscore-min'],
		backbone: [BASE_URL + 'js/libs/backbone-min'],
		bootstrap: [BASE_URL + 'js/libs/bootstrap-min'],
		jquery_scroll: [BASE_URL + 'js/libs/jquery-scrollTo.min'],
		touchswipe: [BASE_URL + 'js/libs/jquery-touchswipe.min'],

		// Tools
		form_mem: 'tools/form_memorizer',
		comic_queue: 'tools/comic_queue',
		api: 'tools/api',
		tabs: 'tools/tabs',
		layout: 'tools/layout',
		ajaxqueue: 'tools/ajaxqueue',
		image: 'tools/image',
		string: 'tools/string',

		// Views
		searchbar: 'views/widget/searchbar',
		pagebar: 'views/widget/pagebar',
		comment: 'views/widget/comment'
	}
});

requirejs.onError = function (err) {
	console.error(err);
};
