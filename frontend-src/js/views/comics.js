define([
  'api',
  'layout',
  'text!templates/comic_list.html',
  'pagebar',
  'ajaxqueue'
  ], function (API, Layout, T_ComicList, Pagebar) {
	return function () {
	
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			
			thumbnail: CDN_LINK + 'images/thumbnail/',
			
			events: {
				"click .category-p > a": "ChangeCategory",
				"click .order-p > a": "ChangeOrder",
				"click .type-p > a": "ChangeStopRenew"
			},
			
			T_ComicList: _.template(T_ComicList),
			
			categories: {},
			
			query: {
				cat_id: 0,
				type: 'online',
				page: 1,
				order: 'pop'
			},
			
			maxpage: 0,

			render: function() {
				var self = this,
					query = self.query;
				API.read('category/'+ self.query.type, function (data) {
					self.categories = data;
					if ( !window.pagebar || window.pagebar.el != self.$el ) {
						window.pagebar = Pagebar(self.$el);
					}
					window.pagebar.init(function (page) {
						self.query.page = page;
						self.render_data();
					});
					self.render_data();
				});
				Layout.two('fb');
				ajaxLoader(self.$el);
				window.changeTitle('所有漫畫瀏覽');
			},
			
			render_data: function () {
				var self = this,
					query = self.query;
				API.read_sync('vtitles/'+ query.cat_id +'/'+ query.type +'/'+ query.page +'/'+ query.order, function (d) {
					self.maxpage = d.pages;
					d.query = self.query;
					d.thumbnail = self.thumbnail;
					d.categories = self.categories;
					self.$el.html(self.T_ComicList(d));
					window.pagebar.setopt({
						nowpage: query.page,
						maxpage: d.pages
					});
					window.pagebar.render();
				});
			},
			
			ChangeCategory: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					catid = $dom.attr('data-catid');
				self.query.cat_id = catid;
				self.render_data();
			},
			
			ChangeOrder: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					order = $dom.attr('data-order');
				self.query.order = order;
				self.render_data();
			},
			
			ChangeStopRenew: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					type = $dom.attr('data-type');
				self.query.type = type;
				self.render_data();
			}
		});
		return new AppView();
		
	};
});