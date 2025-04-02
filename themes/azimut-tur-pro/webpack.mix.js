const mix = require('laravel-mix')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')
const path = require('path');
const glob = require('glob');
const fs = require('fs');
const themeName = 'azimut-tur-pro';
require('laravel-mix-versionhash')
require('laravel-mix-purgecss')

mix.setResourceRoot('../')



const cssFileList = [
    'layouts/master/master',
    'layouts/print/print',
    'pages/index/index',
    'pages/cruise/cruise',
    'pages/cruises/cruises',
    'pages/ship/ship',
    'pages/ships/ships',
    'pages/references/references',
    'pages/dolphin/dolphin-start-page', // Стартовая страница модуля "Дельфин"
    'pages/dolphin/dolphin-page', // Посадочная страница модуля "Дельфин"
    'pages/dolphin/dolphin-ext-offers', // Подбор цен и бронирование "Дельфин"
    'pages/dolphin/dolphin-schedule', // Страница "Расписание"
    'pages/group-tours/group-tours-page', // Посадочная страница групповых туров
    'pages/group-tours/group-tours-tour', // Страница тура групповых туров
    'pages/dolphin/dolphin-ext-print', // Страница для печати
];

const jsFileList = [
    'layouts/master/master',
    'pages/index/index',
    'pages/cruise/cruise',
    'pages/cruises/cruises',
    'pages/ship/ship',
    'pages/ships/ships',
    'pages/references/references',
    'pages/dolphin/dolphin-start-page', // Стартовая страница модуля "Дельфин"
    'pages/dolphin/dolphin-page', // Посадочная страница модуля "Дельфин"
    'pages/dolphin/dolphin-ext-offers', // Подбор цен и бронирование "Дельфин"
    'pages/dolphin/dolphin-schedule', // Страница "Расписание"
    'pages/group-tours/group-tours-page', // Посадочная страница групповых туров
    'pages/group-tours/group-tours-tour', // Страница тура групповых туров
];


cssFileList.forEach((fileName) => {
    mix.sass(`src/${fileName}.scss`, 'css')
        // .purgeCss({
        //     content: [
        //         '../**/*.htm',
        //         '../**/*.vue',
        //     ],
        //     safelist: { deep: [/hljs/] },
        // });
});

jsFileList.forEach((fileName) => {
    mix.js(`src/${fileName}.js`, 'js').vue()
});


// Отделение шапки для старого сайта
mix.js('src/components/header/pluggable-header.js', 'js')
mix.sass('src/components/header/pluggable-header.scss', 'css')


mix.setPublicPath('assets');

if (mix.inProduction()) {
    mix.versionHash()
} else {
    // mix.disableSuccessNotifications()
    mix.webpackConfig({
        devtool: 'inline-source-map'
    })
    mix.options({
        clearConsole: true,
    });
}


mix.copyDirectory(path.join(__dirname, 'src/images'), 'assets/images');
//mix.copyDirectory(path.join(__dirname, 'src/fonts'), 'assets/fonts');

mix.alias({
    '@src': path.join(__dirname, 'src'),
    '@partials': path.join(__dirname, 'partials'),
    '@pages': path.join(__dirname, 'pages'),
    '@components': path.join(__dirname, 'src/components'),
    '@plugins': path.join(__dirname, '/../../plugins')
});


// Собираем картинки из сырцов компонентов и перемешаем их в ассеты с сохранением структуры
mix.then(() => {
    glob.sync('./src/components/**/*.@(svg|png|jpg)', { noext: false }).forEach(file => {
        const filePath = path.dirname(file);
        const fileExt = path.extname(file);
        const fileName = path.basename(file, fileExt);
        const componentName = path.normalize(filePath).split(path.sep).pop();

        const destPath = path.resolve(__dirname, 'assets', 'images', 'components', componentName);

        if (!fs.existsSync(destPath)) fs.mkdirSync(destPath, { recursive: true });
        fs.copyFileSync(file, path.resolve(destPath, path.basename(file)));
    });
});


mix.autoload({
    jquery: ['$', 'window.jQuery', 'jQuery']
});

mix.extract(['jquery']);


/* https://github.com/johnagan/clean-webpack-plugin */
mix.webpackConfig({
    plugins: [
        new CleanWebpackPlugin(),
    ]
});

mix.sourceMaps(false, 'source-map');

mix.disableSuccessNotifications();

mix.version();
