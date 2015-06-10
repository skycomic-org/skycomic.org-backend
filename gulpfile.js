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
path.jslib = path.src + 'js-libs/';

gulp.task('clean', function (cb) {
	del([path.dst + '/**/*'], cb);
})

gulp.task('cssmin', ['clean'], function () {
	return gulp.src(path.src + 'css/*.css')
		.pipe(minifycss())
		.pipe(concat('style.css'))
		.pipe(gulp.dest(path.dst + 'css'));
});

gulp.task('buildjs', ['clean'], function () {
	return gulp.src([path.js + '**/*.js', path.src + 'js/**/*.html', path.jslib + '**/*.js'])
		.pipe(amdOptimize('main', {
			paths: {
				text: path.jslib + 'require/text',
				touchswipe: path.jslib + 'jquery/jquery-touchswipe.min',
				ajaxqueue: path.jslib + 'ajaxqueue',
				image: path.jslib + 'image',
				string: path.jslib + 'string',
				form_mem: path.js + 'tools/form_memorizer',
				comic_queue: path.js + 'tools/comic_queue',
				api: path.js + 'tools/api',
				tabs: path.js + 'tools/tabs',
				layout: path.js + 'tools/layout',
				searchbar: path.js + 'views/widget/searchbar',
				pagebar: path.js + 'views/widget/pagebar',
				comment: path.js + 'views/widget/comment'
			}
		}))
		.pipe(uglify())
		.pipe(concat('script.js'))
		.pipe(gulp.dest(path.dst + 'js'));
});

gulp.task('mvlib', ['clean'], function () {
	return gulp.src(path.src + 'js-libs/**/*')
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

gulp.task('default', ['cssmin', 'buildjs', 'mvassets']);
