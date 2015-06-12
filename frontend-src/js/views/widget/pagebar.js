define([
  'text!templates/pagebar.html'
  ], function (T_Pagebar) {
	return function (el) {
		var AppView = Backbone.View.extend({
			events: {
				"click .pagination > ul > li > a": "ChangePageEvent"
			},
			
			T_Pagebar: _.template(T_Pagebar),
			
			data: {
				nowpage: 0,
				maxpage: 0,
				showpages: []
			},

			init: function (callback) {
				this.data.showpages= [];
				this.change_page_cb = callback;
			},
			
			setopt: function (opts) {
				this.data.maxpage = parseInt(opts.maxpage, 10);
				this.data.nowpage = parseInt(opts.nowpage || 1, 10);
			},
			
			render: function () {
				var self = this,
					d = self.data,
					option = [2,2];
				d.showpages = [];
				if ( d.nowpage <= 2 ) {
					option = [0,4];
				} else if (d.nowpage == 3) {
					option = [1,3];					
				} else if ( d.nowpage >= (d.maxpage - 1) ) {
					option = [4,0];
				} else if ( d.nowpage == (d.maxpage - 2) ) {
					option = [3,1];
				}
				d.showpages.push(1);
				for ( var i = option[0]; i > 0; i-- ) {
					var p = d.nowpage - i;
					if ( p < d.maxpage ) 
						d.showpages.push(p);
				}
				d.nowpage != 1 && d.nowpage != d.maxpage && d.showpages.push(d.nowpage);
				for ( var i = 1; i <= option[1]; i++ ) {
					var p = d.nowpage + i;
					if ( p < d.maxpage ) 
						d.showpages.push(p);
				}
				d.maxpage != 1 && d.showpages.push(d.maxpage);
				if ( d.maxpage <= 1 ) {
					self.$el.find('.pagination').html('');
				} else {
					self.$el.find('.pagination').html(self.T_Pagebar(d));
				}
				return this;
			},

			change_page: function (page) {
				var self = this;
				if ( page >= 1 && page <= self.maxpage ) {
					self.data.nowpage = page;
					self.render();
				}
			},
			
			ChangePageEvent: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					page = $dom.attr('data-page');
				if ( page >= 1 && page <= self.data.maxpage ) {
					self.data.nowpage = page;
					// self.render();
					self.change_page_cb(page);
				}
			}
		});
		return new AppView({el: el});
	};
});