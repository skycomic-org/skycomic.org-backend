define([], function() {
	
	var comic_queue = function () {
		var queue = {},
			els = {},
			active = null,
			browse_mode = false,
			last_page = false,
			now_page;

		els.bottom = $('#bottom-div');
		els.lt = els.bottom.find('a[data-nav="lt"]');
		els.gt = els.bottom.find('a[data-nav="gt"]');
		els.comic_queue = $('#comic-queue-ul');
		els.queue_outside = $('#comic-queue-outside');
		els.queue_inside = $('#comic-queue-inside');
		els.page_input = $('#comic-page-input');
		els.page_now = $('#comic-page-now');
		
		var page = function (page) {
			now_page = page;
			if ( page == 'browse' ) {
				if (last_page != 'browse') {
					els.queue_outside.hide();
					els.queue_inside.show();
					els.comic_queue.find('a').attr('data-false', 'false');
					browse_mode = true;
					$('#comic-queue-ul > li > a > img').remove();	
				}
			} else {
				if (!last_page || last_page == 'browse') {
					els.queue_outside.show();
					els.queue_inside.hide();
					els.comic_queue.find('a').attr('data-false', 'true');
					browse_mode = false;
					if ( _.size(els.comic_queue.find('li > a > img')) == 0 ) {
						els.comic_queue.find('li > a').append('<img width="16px" src="/image/x.png" alt="" />');
					}	
				}
			}
			last_page = page;
		};
		
		var init = function () {
			load();
			render();
			
			// initial binding
			els.queue_inside.find('p > button').click(function () {
				window.__browse.end_prompt();
			});
			
			els.queue_outside.find('p > a').click(function () {
				getinside_init();
				return true;
			});
			
			els.comic_queue.find('a[data-false!="true"]').click(function () {
				getinside_init();
				return true;
			});
			
			els.lt.click(function () { Move.left(); });
			els.gt.click(function () { Move.right(); });
			$(document).on('click', '#comic-queue-ul > li > a > img', function () {
				var cid = $(this).parent().attr('data-cid');
				delete queue[cid];
				data_change();
				return false;
			});
			$(document).on('click', '.browse-queue', function () {
				var cid = $(this).attr('data-cid');
				$.ajax({
					url: '/api/chapter/'+cid,
					type: 'get',
					success: function (json) {
						json = json.http ? json : $.parseJSON(json);
						insert(json.data);
					}
				});
				return false;
			});
		};
		
		var getinside_init = function () {
			window.referer = location.hash;
		};
		
		var set_active = function (cid) {
			active = cid;
			Move.render();
		};
		
		var Move = {
			render: function () {
				var left_width = 0;
				for ( x in queue ) {
					if ( active == null ) {
						active = x;
						break;
					} else if ( x != active ) {
						left_width -= $('#comic-queue-ul > li[data-cid="'+ x +'"]').width();
					} else {
						break;
					}
				}
				$('#comic-queue-ul > li[data-cid="'+ x +'"] > a').addClass('active');
				$('#comic-queue-ul > li[data-cid!="'+ x +'"] > a').removeClass('active');
				els.comic_queue.css('left', left_width);
			},
			left: function () {
				var l = active;
				for ( x in queue ) {
					if ( x != active )
						l = x;
					else 
						break;
				}
				active = l;
				if ( browse_mode ) {
					window.location = '#/browse/'+active;
				}
				Move.render();
			},
			right: function () {
				var r;
				for ( x in queue ) {
					if ( x == active )
						r = active;
					else if ( r == active ) {
						r = x;
						break;
					}
				}
				active = r;
				if ( browse_mode ) {
					window.location = '#/browse/'+active;
				}
				Move.render();
			}
		};
		
		var load = function () {
			if ( window.localStorage['comic_queue'] ) {
				queue = $.parseJSON(window.localStorage['comic_queue']);
				if ( _.size(queue) != 0 ) {
					for ( x in queue ) {
						active = x;
						break;
					}
				}
			} else {
				queue = {};
			}
		};
		
		var sync = function () {
			window.localStorage['comic_queue'] = JSON.stringify(queue);
		};
		
		var render = function () {
			if ( _.size(queue) != 0 ) {
				var html = [];
				_.each(queue, function (chapter, cid) {
					html.push('<li data-cid="'+ cid +'"><a data-cid="'+ cid +'" href="#/browse/'+ cid +'">'+ chapter.title + ' ' + chapter.name +'</a></li>');
				});
				els.comic_queue.html( html.join('') );
				$('#comic-queue-ul > li > a').append('<img width="16px" src="/image/x.png" alt="" />');
			} else {
				els.comic_queue.html('<li><a href="#" data-false="true"><strong>請選擇想看的漫畫</strong></a></li>');
			}
		};
		
		var re_order = function () {
			var ordered_queue = _.sortBy(queue, function (obj) {
				return parseInt(obj.tid, 10) * 1000 + parseInt(obj.index, 10);
			});
			queue = {};
			_.each(ordered_queue, function (obj) {
				queue[obj.cid] = obj;
			});
		};
		
		var data_change = function () {
			// 要同步更新window.comic_queue物件不然會崩潰
			re_order();
			window.comic_queue.queue = queue;
			render();
			sync();
		};
		
		var insert = function (data) {
			queue[data.cid] = data;
			data_change();
			window.__browse.setParam({cid: data.cid, page:0});
			window.__browse.render_cache();
			// err('漫畫 「'+ data.title +' - '+ data.name +'」 已加到觀看列表中了，請按下方「開始觀看」按鈕觀看，或者點另一本漫畫一次看更多本!', 'info', 5);
		};
		
		var clear = function () {
			queue = {};
			data_change();
		}
		
		var get_active = function () {
			if ( queue[active] ) {
				return queue[active];
			} else {
				for ( var x in queue ) {
					active = x;
					return queue[x];
				}
			}
		};
		
		var get_prev = function () {
			var l;
			for ( var x in queue ) {
				if ( x == active ) {
					return l;
				} else {
					l = x;
				}
			}
			return false;
		};
		
		var get_next = function () {
			var hit = false;
			for ( var x in queue ) {
				if ( x == active ) {
					hit = true;
				} else if ( hit === true ) {
					return x;
				}
			}
			return false;
		};
		
		var set_page = function (p) {
			els.page_input.val(p);
			els.page_now.text(p);
		};
		
		var bind_page_change = function (func) {
			var prev_val = 1;

			var ch = function () {
				if ( false == func($(this).val()) ) {
					$(this).val(prev_val);
				} else {
					prev_val = $(this).val();
				}
			};

			els.page_input.unbind().
							bind('change',ch);
		};
		
		init();
		
		return {
			queue: queue,
			sync: sync,
			insert: insert,
			clear: clear,
			render: render,
			set_active: set_active,
			get_active: get_active,
			get_prev: get_prev,
			get_next: get_next,
			set_page: set_page,
			bind_page_change: bind_page_change,
			page: page,
			getinside_init: getinside_init
		};
	};
  
  return comic_queue;
});
