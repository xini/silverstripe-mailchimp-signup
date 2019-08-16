const { src, dest, watch, series } = require('gulp');
const del = require('del');
const path = require('path');
const plumber = require('gulp-plumber');
const sourcemaps = require('gulp-sourcemaps');
const concat = require('gulp-concat');
const stripDebug = require('gulp-strip-debug');
const uglify = require('gulp-uglify');

const paths = {
	"src": "./src/",
	"dist": "./dist/",
	
	"scripts": {
		"src": "javascript",
		"filter": "/**/*.+(js)",
		"dist": "javascript"
	}
};

const script_builds = {
	"jquery.min.js": [
		"node_modules/jquery/dist/jquery.js",
	],
	
	"jquery-validate.min.js": [
		"node_modules/jquery-validation/dist/jquery.validate.js",
	],
	
	"mailchimp-validation.min.js": [
		"src/javascript/mailchimp-validation.js"
	]
};

function buildScripts(cb) {
	var scriptNames = Object.keys(script_builds);
	scriptNames.forEach(function(scriptName) {
		src(
			script_builds[scriptName],
			{
				cwd: path.join(process.cwd(), './'),
				nosort: true
			}
		)
		.pipe(plumber({
			errorHandler: onError
		}))
		.pipe(sourcemaps.init())
		.pipe(concat(scriptName))
		.pipe(stripDebug())
		.pipe(uglify({mangle: false}))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(paths.dist + paths.scripts.dist));
	});
	cb();
}

function cleanScripts(cb) {
	del([
		paths.dist + paths.scripts.dist + "*.(js|map)"
	]);
	cb();
}

function watchScripts() {
	// watch for script changes
	watch(paths.src + paths.scripts.src + paths.scripts.filter, series(cleanScripts, buildScripts));
}

function onError(err) {
    console.log(err);
}

exports.build = series(
	cleanScripts,
	buildScripts,
);

exports.default = series(
	cleanScripts,
	buildScripts,
	watchScripts
);
