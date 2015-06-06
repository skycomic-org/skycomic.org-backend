define([
  'text!templates/error_report.html'
  ], function (Template) {
  var T_error_report = _.template(Template);
  var Login = function (default_page) {
	var $doms = {
		form: $('#register-login-form'),
		fields: $('.register-fields')
	};

	var show_register = function () {
		$doms.fields.show();
		$(this).removeClass('.register-hide');
		$doms.form.find('.primary').hide();
		$doms.form.get(0).action = '/auth/register';
		$(this).click(submit_register);
		$('#id').focus();
		return false;
	};
	
	var submit_register = function () {
		$doms.form.get(0).submit();
		// console.log('submit');
	};
	
	// init	
	if (default_page == 'register') {
		$doms.fields.show();
		$(this).removeClass('.register-hide');
		$doms.form.find('.primary').hide();
		$doms.form.get(0).action = '/auth/register';
		$(".register-hide").click(submit_register);
		$('#id').focus();
	} else {
		$doms.fields.hide();
		$(".register-hide").click(show_register);
		$('#id').focus();
	}
	
	
	var AppRouter = Backbone.Router.extend({
		els: {
			header_nav: $('body .nav')
		},
	
        routes: {
            ":route/:action/*param": "loadView",
            ":route/:action": "loadView",
            ":route": "loadView"
        },

        loadView: function ( route, action, param ) {
			if ( route == 'error-report' ) {
				$('#content').html(T_error_report());
			}
		}
    });
    // Instantiate the router
    window.app_router = new AppRouter;
    // Start Backbone history a neccesary step for bookmarkable URL's
    Backbone.history.start();
	
  };
  
  return Login;
});
