define([
  'ajaxqueue'
  ], function () {
  var API = function () {
	var read_sync = function (url, success) {
		read(url, success, true);
	};
	var read = function (url, success, sync) {
		url = '/api/'+ url;
		var ajaxopt = {
			url: url,
			success: function (jsonText) {
				json = (jsonText && jsonText.http) ? jsonText : $.parseJSON(jsonText);
				if ( json.http.code == 200 ) {
					success(json.data);
				} else {
					this.error({
						responseText: jsonText,
						status: json.http.code
					}, 'error', json.http.msg);
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				json = $.parseJSON(jqXHR.responseText);
				if (json && json.http) {
					if ( json.http.code == 403 ) {
						window.location.href='/auth/login';
					} else {
						$('#real-content').prepend('<div class="alert alert-error"><a class="close" href="#">&times;</a>['+ json.http.code +']'+ json.http.msg +'</div>');
						$(".alert-message").alert();
					}
				} else {
					$('#real-content').prepend('<div class="alert alert-error"><a class="close" href="#">&times;</a>連線錯誤，請重新整理</div>');
					$(".alert-message").alert();
				}
			}
		};
		if ( sync === true ) {
			$.ajaxQueue(ajaxopt);
		} else {
			$.ajax(ajaxopt);
		}
	};
	
	return {
		read: read,
		read_sync: read_sync
	};
	
  } ();
  
  return API;
});
