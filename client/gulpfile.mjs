import gulp from 'gulp';
const { series, parallel, src, dest, task, watch } = gulp;
import { deleteSync } from 'del';
import gulpif from 'gulp-if';
import plumber from 'gulp-plumber';
import sourcemaps from 'gulp-sourcemaps';
import stripdebug from 'gulp-strip-debug';
import uglify from 'gulp-uglify';
import rollup from 'gulp-best-rollup-2';

import { nodeResolve } from "@rollup/plugin-node-resolve";
import { default as commonjs } from "@rollup/plugin-commonjs";

const paths = {
	"scripts": {
		"src": "src/javascript",
		"filter": "/*.+(js)",
		"dist": "dist/javascript"
	}
};

var debugEnabled = false;

function scripts(cb) {
    src(paths.scripts.src + paths.scripts.filter)
        .pipe(plumber({
            errorHandler: onError
        }))
        .pipe(sourcemaps.init())
        .pipe(rollup({ plugins: [
                nodeResolve({
                    browser: true
                }),
                commonjs()
            ] }, 'iife'))
        .pipe(
            gulpif(
                !debugEnabled,
                stripdebug()
            )
        )
        .pipe(uglify({mangle: false}))
        .pipe(sourcemaps.write('.'))
        .pipe(dest(paths.scripts.dist));
    cb();
}

function cleanscripts(cb) {
    deleteSync([
        paths.scripts.dist + paths.scripts.distfilter
    ]);
    cb();
}

function watchAll() {
    // watch for script changes
    watch(paths.scripts.src + "/**/*.+(js)", series(cleanscripts, scripts));
}

function enableDebug(cb) {
    debugEnabled = true;
    cb();
}

function onError(err) {
    console.log(err);
}

task('clean', series(
    parallel(
        cleanscripts
    )
));

task('build', series(
    parallel(
        cleanscripts
    ),
    parallel(
        scripts
    )
));

task('js', series(
    cleanscripts,
    scripts
));

task('default', series(
    parallel(
        cleanscripts
    ),
    parallel(
        scripts
    ),
    watchAll
));

task('debug', series(
    enableDebug,
    parallel(
        cleanscripts
    ),
    parallel(
        scripts
    ),
    watchAll
));
