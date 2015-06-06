define([
  'api'
  ], function(API) {
	return function () {
		var data,
			callbacks = [];
		
		var initial = function () {
			_refresh();
		};
		
		var _refresh = function () {
			API.read_sync('favorite', function (d) {
				data = d;
				_.each(callbacks, function (cb) {
					cb(data);
				});
				callbacks = [];
			});
		};
		
		var refresh = function (cb) {
			callbacks.push(cb);
			_refresh();
		};
		
		var read = function (cb) {
			if ( !data ) {
				callbacks.push(cb);
			} else {
				cb(data);
			}
		};
		
		var read_by_tid = function (tid) {
			return _.find(data, function (d) {
				return d.tid == tid;
			});
		};
		
		var update = function (tid, cb) {
			var del = read_by_tid(tid) !== undefined;
			$.ajax({
				url: '/api/favorite/'+tid,
				type: 'post',
				data: {'delete': del},
				success: function (json) {
					cb && cb();
				}
			});
		};
		
		initial();
		return {
			refresh: refresh,
			read: read,
			read_by_tid: read_by_tid,
			update: update
		};
	} ();
});
