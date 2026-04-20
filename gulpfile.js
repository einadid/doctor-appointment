const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const browserSync = require('browser-sync').create();

// SASS Compile করো
function compileSass() {
    return gulp.src('sass/main.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest('assets/css'))
        .pipe(browserSync.stream());
}

// Browser Sync
function serve() {
    browserSync.init({
        server: {
            baseDir: './'
        }
    });

    gulp.watch('sass/**/*.scss', compileSass);
    gulp.watch('*.html').on('change', browserSync.reload);
    gulp.watch('pages/*.html').on('change', browserSync.reload);
}

// Default task
exports.default = gulp.series(compileSass, serve);
exports.sass = compileSass;
