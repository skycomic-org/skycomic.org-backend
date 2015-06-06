define([
  'models/comment'
  ], function (comment) {
	return function (opt) {
		// opt: {type: [title|chapter], id: int}
		var Fetched = false;
		var Comments = Backbone.Collection.extend({
			model: comment,

			url: '/api/comment',
			
			initialize: function () {
				this.view = opt.view;
			},
			
			parse: function (res) {
				var self = this;
				window.pagebar && window.pagebar.setopt({maxpage: res.data.pages, nowpage: self.view.page});
				return res.data.data;
			},
			
			my_fetch: function () {
				Fetched = true;
				var self = this,
					url = this.url;
				this.url += opt.type == 'title' ? '_tid' : '_cid';
				this.url += '/'+ opt.id +'/'+ self.view.page;
				this.fetch({
					success: function () {
						self.view.render_data();
					}
				});
				this.url = url;
			},
		});
		return new Comments;
	};
});