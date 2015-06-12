define(['jquery'], function () {
window.imageCache = (function () {
	var pub = {},
		els = [
			$('#image-cache'),
			$('#image-cache2'),
		],
		el = els[0],
		elNumber = 0,
		limit = 50, // limit of single caching slot
		capacity = 0,
		items = {};

	pub.add = function (url, hash) {
		if (!hash) {
			hash = url;
		}
		if (!items[hash]) {
			if (capacity > limit) {
				// switch to next cache
				elNumber = 1 - elNumber;
				el = els[elNumber];
				// empty it first
				el.empty();
				capacity = 0;
			}
			el.append('<img src="'+ url +'" />');
			items[hash] = 1;
			capacity++;
		}
		return pub;
	};

	return pub;
})();
});
