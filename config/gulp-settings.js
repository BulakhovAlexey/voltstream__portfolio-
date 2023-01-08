// Мы получаем имя папки проекта
import * as nodePath from "path";
const rootFolder = nodePath.basename(nodePath.resolve());

// Способы к папке с исходными данными и папкой с результатом
const buildFolder = `./dist`;
const srcFolder = `./src`;

// Способы к папкам и файлам проекта
export const path = {
  build: {
    html: `${buildFolder}/`,
    js: `${buildFolder}/js/`,
    css: `${buildFolder}/css/`,
    images: `${buildFolder}/img/`,
    fonts: `${buildFolder}/fonts/`,
    files: `${buildFolder}/files/`,
  },
  src: {
    html: `${srcFolder}/*.html`,
    pug: `${srcFolder}/pug/*.pug`,
    js: `${srcFolder}/js/app.js`,
    scss: `${srcFolder}/scss/style.scss`,
    images: `${srcFolder}/img/**/*.{jpg,jpeg,png,gif,webp}`,
    svg: `${srcFolder}/img/**/*.svg`,
    fonts: `${srcFolder}/fonts/*.*`,
    files: `${srcFolder}/files/**/*.*`,
    svgicons: `${srcFolder}/svgicons/*.svg`,
  },
  clean: buildFolder,
  buildFolder: buildFolder,
  rootFolder: rootFolder,
  srcFolder: srcFolder,
  // Путь к желаемой папке на удаленном сервере.
  ftp: ``,
  // Пример: скачать в папке 2022 дальше в папку с именем проекта
  // ftp: `2022/${rootFolder}`
};

// Настройка FTP -соединения
export const configFTP = {
  host: "", // Адрес сервера FTP
  user: "", // Имя пользователя
  password: "", // пароль
  parallel: 5, //Количество одновременных потоков
};
