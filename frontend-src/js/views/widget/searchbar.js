define([], function () {
	return {
		init: function () {
			$('#search-form button').click(function () {
				var search_txt = $("#search").val();
				window.location = '#/search/'+search_txt;
				return false;
			});
		}
	};
});