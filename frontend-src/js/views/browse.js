define([
  'api',
  'layout',
  'comment',
  'tools/favorite',
  'text!templates/browse.html',
  'text!templates/browse_end_modal.html',
  //image: detecting image width&height
  'image','touchswipe'
  ], function (API, Layout, comments, Favorite, BrowseT, BrowseEndT) {
	var View = function () {
		var data,
			last_cid = 0, // for comment optimization
			next,
			prev,
			cid,
			page,
			CQ = window.comic_queue,
			els = {},
			uri = CDN_LINK + "images/comic/",
			cache_pages = [3,15], // 前3張後15張
			img_data = {
				scoll: 10, // 放大倍率
				width: 0,
				height: 0
			};// min = 1 max = 19

		els.page_max = $('#comic-page-max');
		els.page_input = $('#comic-page-input');
		
		var AppView = Backbone.View.extend({
			el: $("#real-content"),
			header_menu: $("#header-menu"),
			
			BrowseT: _.template(BrowseT),
			BrowseEndT: _.template(BrowseEndT),

			initialize: function() {
				CQ.bind_page_change(this.change_page);
				var self = this;
				// global event bindings
				// right click
				window.oncontextmenu = window.oncontextmenu || document.oncontextmenu;
				document.oncontextmenu = function () {
					if (app_router.nowpage == 'browse') {
						return false;
					}
				}
				// A & D
				if (navigator.appName == "Microsoft Internet Explorer") {
					document.onkeydown = function (e) {
						if (app_router.nowpage == 'browse') {
							var key = event.keyCode;
							if (key==97 || key == 65) {  // A
								self.change_page(page-1);
							} else if (key==100 || key == 68) { // D
								self.change_page(page+1);
							} else if (key == 87 || key == 119) { // W
								self.scroll('Up');
							} else if (key == 83 || key == 115) { // S
								self.scroll('Down');
							} else if (key == 107) {
								self.scale_img('+');
							} else if (key == 109) {
								self.scale_img('-');
							} else if (key == 187) {
								self.scale_img('=');
							}
						}
					};
				} else {
					document.onkeypress = function (e) {
						if (app_router.nowpage == 'browse') {
							var key = e.charCode || e.keyCode || e.which || 0;
							if (key==97 || key == 65) {  // A
								self.change_page(page-1);
							} else if (key==100 || key == 68) { // D
								self.change_page(page+1);
							} else if (key == 87 || key == 119) { // W
								self.scroll('Up');
							} else if (key == 83 || key == 115) { // S
								self.scroll('Down');
							} else if (key==45) {
								self.scale_img('+');
							} else if (key==43) {
								self.scale_img('-');
							} else if (key==61) {
								self.scale_img('=');
							}
						}
					};
				}
			},

			leave: function (nextpage) {
				// if not browse, than do leave things.
				if (nextpage != 'browse') {
					$(document.body).removeClass('browsing');
					// $(document.body).attr('style', '');
					// $('#container').attr('style', '');
					this.header_menu.show();
					CQ.clear();
				}
				$('#Modal').modal('hide');
			},
			
			// for comic_queue
			setParam: function (param) {
				cid = param.cid;
				page = param.page;
			},
			
			render: function(_cid, _page) {
				var self = this;
				cid = _cid;
				page = _page;
				if (app_router.lastpage != 'browse') {
					self.init_browse();
				}
				// url的routing
				if ( !cid ) {
					if ( !CQ.get_active() ) {
						alert("請至少選擇一本漫畫來觀看喔!");
						window.history.go(-1);
					} else {
						API.read('viewed_page/'+ CQ.get_active().cid, function (page) {
							app_router.navigate('/browse/'+CQ.get_active().cid+'/' + page, {trigger: true});
						});
					}
				} else if (!page) {
					API.read('viewed_page/'+ cid, function (page) {
						app_router.navigate('/browse/'+cid+'/' + page, {trigger: true});
					});
				} else {
					if (!CQ.queue[cid]) {
						API.read('chapter/'+ cid, function (data) {
							CQ.insert(data);
							page = _page;
							self.render_data();
						});
					} else {
						self.render_data();
					}
				}
			},

			scroll: function (way) {
				var scroll = way == 'Up' ? '-=500px' : '+=500px';
				$.scrollTo(scroll, 500);
			},

			// first time render browse
			init_browse: function () {
				var self = this;
				// one column layout
				Layout.one();	
				// render browse html
				this.$el.html(self.BrowseT());
				els.img = $('#image');

				// custimize some view
				$(document.body).addClass('browsing');
				// $(document.body).attr('style', 'background: #222;');
				// $('#real-content').attr('style', 'background: none');
				// $('#container').attr('style', 'padding-top: 0px;');
				this.header_menu.hide();

				// element-depending events binding
				// clicks
				els.img.unbind().bind('mousedown', function (event) {
					if (app_router.nowpage == 'browse') {
						switch (event.which) {
							case 1:
								self.change_page(page+1);
								break;
							case 2://middle
								return false;
							case 3:
								self.change_page(page-1);
								return false;
							default:
								return false;
								// alert('You have a strange mouse');
						}
					}
				});
				// binding event for ipad
				this.$el.swipe({
					swipeLeft:function (event, direction, distance, duration, fingerCount) {
						if (app_router.nowpage == 'browse') {
							self.change_page(page+1);
						}
					},
					swipeRight: function (event, direction, distance, duration, fingerCount) {
						if (app_router.nowpage == 'browse') {
							self.change_page(page-1);
						}
					}
				});
			},
			
			// each time rendering data
			render_data: function () {
				var self = this;
				page = parseInt(page, 10);
				// update lastview
				$.ajax({
					url: '/api/view_record/' + cid + '/' + page,
					type: 'post'
				});
				// comic queue setting
				CQ.set_active(cid);
				CQ.set_page(page);
				data = CQ.get_active();
				next = CQ.get_next();
				prev = CQ.get_prev();
				this.render_img();
				this.render_cache();
				img_data.width = GetImageWidth(els.img.get(0));
				img_data.height = GetImageHeight(els.img.get(0));
				
				// image scaleing
				if (img_data.scoll != 10)
					self.scale_img_action();

				// scroll to top
				$.scrollTo("-=1000px", 0);

				// comment rendering
				if (cid != last_cid) {
					window.Comments = comments();
					window.Comments.render(CQ.queue[cid].tid, cid, true);	
					// set global tid for adword
					window.TID = CQ.queue[cid].tid;		

					els.page_input.attr('max', CQ.queue[cid].pages);
					els.page_max.text(CQ.queue[cid].pages);
				}
				last_cid = cid;

				window.changeTitle('《'+ CQ.queue[cid].title + '》' + CQ.queue[cid].name + '(' + page + ')');
			},
			
			scale_img: function (event) {
				switch (event) {
					case '+':
						if ( img_data.scoll < 19 ) {
							img_data.scoll++;
						}
						break;
					case '-':
						if ( img_data.scoll > 1 ) {
							img_data.scoll--;
						}
						break;
					case '=':
						img_data.scoll = 10;
				}
				this.scale_img_action();
			},
			
			scale_img_action: function () {
				els.img.get(0).width = img_data.width*(10-img_data.scoll)*0.1 + img_data.width;
			},
			
			render_img: function () {
				var el = els.img;
				el.hide()
					.one('load', function() {
						el.show();
					})
					.attr('src', uri + cid +'/' + page)
				if (el.get(0).complete) {
					el.trigger('load');
				};
			},
			
			prev_page: function (p) {
				if ( p.page > 1 ) {
					return {cid: p.cid, page: p.page - 1};
				} else {
					if ( prev && CQ.queue[prev] ) {
						return {cid: prev, page: CQ.queue[prev].pages-1};
					} else {
						return false;
					}
				}
			},
			
			next_page: function (p) {
				if ( p.page < CQ.queue[cid].pages ) {
					return {cid: p.cid, page: p.page + 1};
				} else {
					if ( next ) {
						return {cid: next, page: 1};
					} else {
						return false;
					}
				}
			},

			// 看完漫畫的處理
			end_prompt: function () {
				var self = this,
					tid = CQ.queue[cid].tid,
					last = page != 1,
					favored = Favorite.read_by_tid(CQ.queue[cid].tid);
				$('#Modal').html(this.BrowseEndT({
					title: CQ.queue[cid].title,
					chapter: CQ.queue[cid].name,
					favored: favored,
					tid: tid,
					cid: cid,
					last: page != 1
				})).modal('show');

				$('#browse-favor-btn').click(function () {
					Favorite.update(tid, function () {
						Favorite.refresh(function () {
							self.render_end_prompt();
						});
					});
				});

				$('#browse-nextchapter-btn').bind('click', function () {
					API.read('title/' + tid, function (data) {
						for (var i=0, chapter = data.chapters[0]; chapter; chapter = data.chapters[++i]) {
							if (chapter.cid == cid) {
								try {
									if (last) {
										// console.log('browse/' + data.chapters[i - 1].cid + '/1');
										app_router.navigate('/browse/' + data.chapters[i - 1].cid + '/1', {trigger: true});
									} else {
										// console.log('browse/' + data.chapters[i + 1].cid + '/1');
										app_router.navigate('/browse/' + data.chapters[i + 1].cid + '/1', {trigger: true});
									}	
								} catch (e) {
									err('已經是最' + (last ? '後' : '前') + '面的一話囉!');
									$('#Modal').modal('hide');
								}
								break;
							}
						};
					});
				});
			},
			
			render_cache: function () {
				var self = this,
					prev_n = cache_pages[0],
					next_n = cache_pages[1];
				
				var check_n_appand = function (_cid, _page) {
					var url = uri + _cid +'/'+ _page,
						hash = _cid + ":" + _page;
					window.imageCache.add(url, hash);
				};
				
				for (var i = 0, p = {cid:cid, page:page}; (p = self.prev_page(p)) && i < prev_n; i++) {
					check_n_appand(p.cid, p.page);
				}
				
				for (var i = 0, p = {cid:cid, page:page}; (p = self.next_page(p)) && i < next_n; i++) {
					check_n_appand(p.cid, p.page);
				}
			},
			
			change_page: function (_page) {
				_page = parseInt(_page, 10);
				if ( _page == 0 ) {
					if ( prev ) {
						app_router.navigate('/browse/'+prev+'/'+CQ.queue[prev].pages, {trigger: true});
					} else {
						this.end_prompt();
					}
				} else if ( !CQ.queue[cid] ) {
					return false;
				} else if ( CQ.queue[cid].pages >= _page ) {
					app_router.navigate('/browse/'+cid+'/'+_page, {trigger: true});
				} else if ( next ) {
					app_router.navigate('/browse/'+next+'/1', {trigger: true});
				} else {
					this.end_prompt();
				}
			}

		});
		return new AppView();
	};
	
	return View;
});
