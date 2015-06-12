(function () {
	var OriginImage = new Image();
	window.GetImageWidth = function (oImage) {
		if(OriginImage.src!=oImage.src)OriginImage.src=oImage.src;
		return OriginImage.width;
	}
	window.GetImageHeight = function (oImage) {
		if(OriginImage.src!=oImage.src)OriginImage.src=oImage.src;
		return OriginImage.height;
	}
})();