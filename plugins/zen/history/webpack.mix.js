const mix = require('laravel-mix')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')
const path = require('path');
const glob = require('glob');
const fs = require('fs');

require('laravel-mix-versionhash')
require('laravel-mix-purgecss')

mix.setResourceRoot('../')


const cssFileList = [
    'widgets/history/history', // Виджет для поиска
    'scss/backend'
];

const jsFileList = [
    'widgets/history/history', // Виджет для поиска
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


mix.setPublicPath('assets');

if (mix.inProduction()) {
    //mix.versionHash()
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
    '@widgets': path.join(__dirname, 'widgets'),
    '@components': path.join(__dirname, 'src/components')
});


// Собираем картинки из сырцов компонентов и перемешаем их в ассеты с сохранением структуры
mix.then(() => {
    glob.sync('./src/components/**/*.@(svg|png|jpg)', { noext: false }).forEach(file => {
        const filePath = path.dirname(file);
        const fileExt = path.extname(file);
        const fileName = path.basename(file, fileExt);
        const componentName = path.normalize(filePath).split(path.sep).pop();

        const destPath = path.resolve(__dirname, 'assets', 'images', 'components', componentName);

        if (!fs.existsSync(destPath)) {
            fs.mkdirSync(destPath, { recursive: true });
        }
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
