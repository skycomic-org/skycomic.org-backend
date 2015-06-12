define([
  'collections/comments',
  'text!templates/comment.html',
  'text!templates/leave_comment.html'
  ], function (Comments, T_Comment, T_LeaveComment) {
	return function (_el) {
		var tid, cid, el;
		el = _el || $("#comments");
		
		var AppView = Backbone.View.extend({
			el: el,
			
			events: {
				'click div.uneditable-input': 'e_posting',
				'click div.leave-comment .cancel': 'e_canceling',
				'click div.leave-comment .cancel-edit': 'e_canceling_edit',
				'click div.leave-comment .success': 'e_submitting',
				'click a.push': 'e_push',
				'click a.hush': 'e_hush',
				'click a.edit': 'e_edit',
				'click a.delete': 'e_delete'
			},
			
			page: 1,
			Comments: null,
			center: false,
			
			T_Comment: _.template(T_Comment),
			T_LeaveComment: _.template(T_LeaveComment),

			initialize: function() {
				var self = this;
				require(['pagebar'], function (p) {
					if ( !window.pagebar || window.pagebar.el != self.$el ) {
						window.pagebar = p(self.$el);
					}
					window.pagebar.init(function (page) {
						self.page = page;
						self.fetch();
					});
				});
			},
			
			render: function (_tid, _cid, center) {
				tid = _tid;cid = _cid;
				var self = this;
				self.center = center === true ? true : false;
				if ( cid ) {
					self.Comments = Comments({
						type: 'chapter',
						id: cid,
						view: self
					});
				} else {
					self.Comments = Comments({
						type: 'title',
						id: tid,
						view: self
					});
				}
				self.Comments.my_fetch();
			},
			
			render_data: function () {
				var self = this;
				self.$el.html(self.T_Comment({
					comments: self.Comments,
					T_LeaveComment: self.T_LeaveComment,
					center: self.center
				}));
				window.pagebar && window.pagebar.render();
			},
			
			e_posting: function (event) {
				var $dom = $(event.currentTarget),
					self = this;
				$dom.slideUp('slow').next().slideDown('slow').find('textarea').focus();
			},
			
			e_canceling: function (event) {
				var $dom = $(event.currentTarget),
					self = this;
				$dom.parent().prev().val('').parent().slideUp('slow').prev().slideDown('slow');
			},
			
			e_canceling_edit: function (event) {
				var $dom = $(event.currentTarget),
					self = this;
				$dom.parent().parent().slideUp('slow').prevUntil('div').slideDown('slow');
			},
			
			e_push: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					comment_id = $dom.parent().parent().attr('data-cid');
				$.ajax({
					url: '/api/comment_push/'+ comment_id,
					type: 'post',
					data: {'push': '1'},
					success: function () {
						self.Comments.my_fetch();
					}
				});
			},
			
			e_hush: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					comment_id = $dom.parent().parent().attr('data-cid');
				$.ajax({
					url: '/api/comment_push/'+ comment_id,
					type: 'post',
					data: {'push': '-1'},
					success: function () {
						self.Comments.my_fetch();
					}
				});
			},
			
			e_delete: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					comment_id = $dom.parent().parent().attr('data-cid');
				$.ajax({
					url: '/api/comment/'+ comment_id,
					type: 'delete',
					success: function () {
						self.Comments.my_fetch();
					}
				});
			},
			
			e_edit: function (event) {
				var $dom = $(event.currentTarget),
					self = this,
					$p = $dom.parent().parent().nextUntil('div.leave-comment');
					$div = $p.next();
				$p.slideUp('slow');
				$div.slideDown('slow');
			},
			
			e_submitting: function (event) {
				var $dom = $(event.currentTarget).parent().prev(),
					self = this,
					comment_id = $dom.parent().attr('data-id'),
					parent_id = $dom.parent().attr('data-parent_id'),
					ajax = {};
				ajax.data = {
					content: $dom.val()
				};
				if ( parent_id && parent_id != 0 ) {
					ajax.data.parent_id = parent_id;
				}
				if ( comment_id != '0' ) {
					ajax.type = 'put';
					ajax.url = '/api/comment/' + comment_id;
				} else {
					ajax.url = '/api/comment/'+tid;
					if ( cid ) {
						ajax.url += '/'+ cid;
					}
					ajax.type = 'post';
				}
				ajax.success = function () {self.Comments.my_fetch();};
				// console.log(ajax);
				$.ajax(ajax);
			}
		});
		return new AppView();
	};
});