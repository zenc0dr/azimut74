const mix = require('laravel-mix');
const { CleanWebpackPlugin } = require('clean-webpack-plugin')

mix.setPublicPath('assets')
mix.setResourceRoot('../')
mix.sass('src/assets/scss/widget.scss', 'css')
mix.js('src/main.js', 'js').vue()
mix.copyDirectory('src/assets/images', 'assets/images')

if (mix.inProduction()) {
    mix.version();
} else {
    mix.webpackConfig({
        devtool: 'inline-source-map',
        // output: {
        //     chunkFilename: 'js/[name].bundle.js',
        //     publicPath: '/plugins/zen/dolphin/vue/dolphin-widget/assets/',
        // },
        plugins: [
            new CleanWebpackPlugin(),
        ]
    })
}

/*
Разбиение на чанки пока не удалось реализовать, по всей видимости не соблюдается порядок загрузки
скриптов bundle, надо будет разобраться
*/
