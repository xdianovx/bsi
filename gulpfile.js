import gulp from "gulp";
import browserSync from "browser-sync";
import { filePaths } from "./gulp/config/paths.js";

/**
 * Импорт задач
 */
import { reset } from "./gulp/tasks/reset.js";
import { server } from "./gulp/tasks/server.js";
import { scss } from "./gulp/tasks/scss.js";
import { javascript } from "./gulp/tasks/javascript.js";

const isBuild = process.argv.includes("--build");
const browserSyncInstance = browserSync.create();

const handleServer = server.bind(null, browserSyncInstance);
const handleSCSS = scss.bind(null, isBuild, browserSyncInstance);
const handleJS = javascript.bind(null, !isBuild, browserSyncInstance);

function watcher() {
	gulp.watch(filePaths.watch.scss, handleSCSS);
	gulp.watch(filePaths.watch.js, handleJS);
}

const devTasks = gulp.parallel(handleSCSS, handleJS);

const mainTasks = gulp.series(devTasks);

const dev = gulp.series(reset, mainTasks, gulp.parallel(watcher, handleServer));
const build = gulp.series(reset, mainTasks);

gulp.task("default", dev);

export { dev, build };
