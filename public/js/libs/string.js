// count chinese word's length
String.prototype.Blength = function() {   
     var arr = this.match(/[^\x00-\xff]/ig);   
     return  arr == null ? this.length : this.length + arr.length;   
};

// align by length and seperator
String.prototype.Aligner = function(len, seperator) {
	var seperator = seperator || ' ',
		whites = '',
		diff = len - this.Blength(),
		i = 0;
	if ( diff > 0 ) {
		for ( ; i < diff ; i++ ) {
			whites += seperator;
		}
	} else if ( diff < 0 ) {
		return this.substr(0, len) + "\n" + this.substr(len).Aligner(len, seperator);
	}
	return this.concat(whites);
};

// just trim
String.prototype.Trim = function () { 
	return this.replace(/(^\s*)|(\s*$)/g, ""); 
};