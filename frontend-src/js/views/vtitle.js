define([
  'api',
  'layout',
  'tools/favorite',
  'text!templates/vtitle.html',
  'text!templates/title.html',
  'comment'
  ], function (API, Layout, Favorite, T_vtitle, T_title, comments) {

	var View = function () {
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			vtid: null,
			ul: {},
			events: {
				"click button.browse-queue-multi": "Clicked_title",
				"mouseenter button.browse-queue-multi": "Entered_title",
				"mouseleave button.browse-queue-multi": "Leaved_title",
				"click .favored-button": "Favored_selection",
				// "click #title-extract": "Extract_list",
				// "click #title-fold": "Fold_list"
			},
			data: null,
			favored: false,
			tid: null,
			tids: [],
			T_vtitle: _.template(T_vtitle),
			T_title: _.template(T_title),
			
			Clear: function () {
				this.start_tid = null;
				this.start_index = null;
				this.end_index = null;
				this.tids = [];
			},

			leave: function (nowpage) {
				if (nowpage !== 'vtitle')
					$('#ad-sidebar').attr('src', '/main/ad');
			},

			render_tid: function (tid) {
				var self = this,
					index = _.indexOf(self.tids, tid);
				return self.render_data(index, self.data[index]);
			},
			
			render_data: function (index, data) {
				var self = this,
					tid = data.tid;
				self.$el.find('#title-' + tid).html(self.T_title(data));
				self.ul[tid] = $('#chapters-'+ tid +' > ul.title');
				
				var Comments = comments($('#comments-' + tid));
				Comments.render(tid);

				window.changeTitle('《'+ data.title +'》');
			},
			
			render: function(vtid, tid) {
				var self = this;
				self.vtid = vtid;				
				self.Clear();
				ajaxLoader(self.$el);
				API.read_sync('vtitle/'+ vtid, function (data) {
					self.data = data;
					Favorite.read(function () {
						_.each(data, function (row, i){
							self.data[i].favored = Favorite.read_by_tid(row.tid) !== undefined;
						});
					});

					self.tid = data[0].tid;
					_.each(data, function (row, i) {
						if (tid == row.tid) {
							// if the param's tid is real.
							self.tid = tid;
						}
						self.tids.push(parseInt(row.tid, 10));
						API.read('viewed_titles/'+ row.tid, function (d) {
							self.data[i].viewed = d;
							self.render_data(i, self.data[i]);
						});
					});
					self.$el.html(self.T_vtitle({data: data, tid: self.tid}));
					$('ul#title-tabs > li').each(function (i, el) {
						var $el = $(el),
							mytid = $el.attr('data-tid');
						if (tid) {
							$el.bind('mouseleave', function () {
								// window.app_router.navigate('/vtitle/' + self.vtid + '/' + mytid);								window.location.hash = '#/vtitle/' + self.vtid + '/' + mytid;
							});
						}
					});
					// set global tid for adword
					window.TID = self.tid;
					$('#ad-sidebar').attr('src', '/main/ad/' + self.tid);
				});
				Layout.two('fb');
			},
			
			Clicked_title: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					index = $dom.attr('data-index'),
					tid = $dom.attr('data-tid');
				if ( self.start_index === null ) {
					self.start_index = parseInt(index, 10);
					self.start_tid = parseInt(tid, 10);
					self.color($dom);
				} else {
					self.end_index = index;
					self.JoinQueue();
				}
			},
			
			color: function ($dom) {
				$dom.attr('class', 'browse-queue-multi btn btn-success');
			},
			
			Entered_title: function (event) {
				var $dom = $(event.currentTarget),
					tid = parseInt($dom.attr('data-tid'), 10),
					self = this;
				if ( self.start_index !== null && tid == self.start_tid ) {
					var index = parseInt($dom.attr('data-index'), 10),
						$doms = self.ul[tid].find('button').filter(function (i) {
							var di = parseInt($(this).attr('data-index'), 10);
							if ( index >= self.start_index ) {
								return di >= self.start_index && di <= index;
							} else {
								return di <= self.start_index && di >= index;
							}
						});
					// color traversing doms
					self.color($doms);
				}
			},
			
			Leaved_title: function (event) {
				var $dom = $(event.currentTarget),
					self = this;
				if ( self.start_index !== null ) {
					self.render_tid(self.start_tid);
					self.color($dom);
				}
			},
			
			Favored_selection: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					tid = $dom.attr('data-tid');
				Favorite.update(tid, function () {
					Favorite.refresh(function () {
						self.render(self.vtid);
					});
				});
			},
			
			// Extract_list: function (event) {
			// 	var $dom = $(event.currentTarget),
			// 		self = this;
			// 	self.ul.removeClass('fold');
			// 	$dom.hide();
			// 	$("#title-fold").show();
			// },
			
			// Fold_list: function (event) {
			// 	var $dom = $(event.currentTarget),
			// 		self = this;
			// 	self.ul.addClass('fold');
			// 	$dom.hide();
			// 	$("#title-extract").show();
			// },
			
			JoinQueue: function () {
				window.comic_queue.getinside_init();
				this.start_index = parseInt(this.start_index, 10);
				this.end_index = parseInt(this.end_index, 10);
				var self = this,
					normal = self.start_index <= self.end_index,
					data_index = _.indexOf(self.tids, self.start_tid);
				_.each(self.data[data_index].chapters, function (obj) {
					obj.index = parseInt(obj.index, 10);
					obj.title = self.data[data_index].title;
					if ( normal && obj.index >= self.start_index && obj.index <= self.end_index ) {
						window.comic_queue.insert(obj);
					} else if (obj.index <= self.start_index && obj.index >= self.end_index) {
						window.comic_queue.insert(obj);
					}
				});
				window.location='#/browse';
			}
		});
		return new AppView();
	};
	
	return View;
});