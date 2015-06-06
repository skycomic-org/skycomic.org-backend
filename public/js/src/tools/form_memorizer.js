define([
  ], function(){
  
  var Form_mem = function () {
	var pub = {},
		dom = window.data.Form_mem.dom || {};
		data = window.data.Form_mem.data || {};
	
	pub.init = function () {
		var $dom = $(dom);
		_.each(data, function (value, key) {
			var $input = $dom.find('[name='+ key +']');
			$input.val(value);
		});
	};

	return pub;
  } ();
  
  return Form_mem;
});
