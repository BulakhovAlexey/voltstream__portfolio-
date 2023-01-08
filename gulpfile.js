// Імпорт основного модуля
import gulp from "gulp";
// Імпорт Общие плагины
import { plugins } from "./config/gulp-plugins.js";
// Імпорт пути
import { path } from "./config/gulp-settings.js";

// Мы передаем значение глобальной переменной
global.app = {
  isBuild: process.argv.includes("--build"),
  isDev: !process.argv.includes("--build"),
  isWebP: !process.argv.includes("--nowebp"),
  isFontsReW: process.argv.includes("--rewrite"),
  gulp: gulp,
  path: path,
  plugins: plugins,
};

// Імпорт задачи
import { reset } from "./config/gulp-tasks/reset.js";
import { html } from "./config/gulp-tasks/html.js";
import { css } from "./config/gulp-tasks/css.js";
import { js } from "./config/gulp-tasks/js.js";
import { jsDev } from "./config/gulp-tasks/js-dev.js";
import { images } from "./config/gulp-tasks/images.js";
import { ftp } from "./config/gulp-tasks/ftp.js";
import { zip } from "./config/gulp-tasks/zip.js";
import { sprite } from "./config/gulp-tasks/sprite.js";
import { gitignore } from "./config/gulp-tasks/gitignore.js";
import { otfToTtf, ttfToWoff, fonstStyle } from "./config/gulp-tasks/fonts.js";

// Послідовна обробка шрифтів
const fonts = gulp.series(reset, otfToTtf, ttfToWoff, fonstStyle);
// Основные задачи будут выполняться параллельно после обработки шрифта
const devTasks = gulp.parallel(fonts, gitignore);
// Основные задачи будут выполняться параллельно после обработки шрифта
const buildTasks = gulp.series(
  fonts,
  jsDev,
  js,
  gulp.parallel(html, css, images, gitignore)
);

// Експорт задачи
export { html };
export { css };
export { js };
export { jsDev };
export { images };
export { fonts };
export { sprite };
export { ftp };
export { zip };

// Строительство сценариев для выполнения задач
const development = gulp.series(devTasks);
const build = gulp.series(buildTasks);
const deployFTP = gulp.series(buildTasks, ftp);
const deployZIP = gulp.series(buildTasks, zip);

// Експорт Сценарии
export { development };
export { build };
export { deployFTP };
export { deployZIP };

// Выполнение сценария по умолчанию
gulp.task("default", development);
