var gulp = require('gulp');  
var uglify = require('gulp-uglify');
var minifycss = require('gulp-minify-css');
var concat = require('gulp-concat');
var amdOptimize = require('amd-optimize');
var del = require('del');

var path = {};
path.src = "frontend-src/";
path.dst = "frontend/";
path.js = path.src + 'js/';
path.jslib = path.src + 'libs/';

var amdParams = {
	paths: {
		jquery: 'empty:',
		underscore: 'empty:',
		backbone: 'empty:',
		bootstrap: 'empty:',
		jquery_scroll: 'empty:',
		touchswipe: 'empty:',

		text: path.jslib + 'require/text',
		ajaxqueue: path.js + 'tools/ajaxqueue',
		image: path.js + 'tools/image',
		string: path.js + 'tools/string',
		form_mem: path.js + 'tools/form_memorizer',
		comic_queue: path.js + 'tools/comic_queue',
		api: path.js + 'tools/api',
		tabs: path.js + 'tools/tabs',
		layout: path.js + 'tools/layout',
		searchbar: path.js + 'views/widget/searchbar',
		pagebar: path.js + 'views/widget/pagebar',
		comment: path.js + 'views/widget/comment'
	}
};

gulp.task('clean', function (cb) {
	del([path.dst + '/**/*'], cb);
})

gulp.task('cssmin', ['clean'], function () {
	return gulp.src(path.src + 'css/*.css')
		.pipe(minifycss())
		.pipe(concat('style.css'))
		.pipe(gulp.dest(path.dst + 'css'));
});

gulp.task('main', ['clean'], function () {
	return gulp.src([path.js + '**/*.js', path.src + 'js/**/*.html', path.jslib + '**/*.js'])
		.pipe(amdOptimize('main', amdParams))
		.pipe(uglify())
		.pipe(concat('main.js'))
		.pipe(gulp.dest(path.dst + 'js'));
});

gulp.task('login', ['clean'], function () {
	return gulp.src([path.js + '**/*.js', path.src + 'js/**/*.html', path.jslib + '**/*.js'])
		.pipe(amdOptimize('login', amdParams))
		.pipe(uglify())
		.pipe(concat('login.js'))
		.pipe(gulp.dest(path.dst + 'js'));
});

gulp.task('mvlib', ['clean'], function () {
	return gulp.src(path.jslib + '*.js')
		.pipe(uglify({mangle: false, preserveComments: 'all'}))
		.pipe(gulp.dest(path.dst + 'js/libs'));
});

gulp.task('mvimg', ['clean'], function () {
	return gulp.src(path.src + 'image/**/*')
		.pipe(gulp.dest(path.dst + 'image'));
});

gulp.task('mvfile', ['clean'], function () {
	return gulp.src(path.src + 'files/**/*')
		.pipe(gulp.dest(path.dst + 'files'));
});

gulp.task('mvassets', ['clean', 'mvlib', 'mvimg', 'mvfile'], function () {
	return gulp.src(path.src + 'favicon.ico')
		.pipe(gulp.dest(path.dst));
});

gulp.task('watch', function () {
	gulp.watch(path.js + '**/*.js', ['default']);
});

gulp.task('default', ['cssmin', 'main', 'login', 'mvassets']);
