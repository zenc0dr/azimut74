const mix = require('laravel-mix')
mix.sass('src/scss/fetcher-dashboard.scss', 'css')
mix.js('src/js/fetcher-dashboard.js', 'js').vue()

mix.setPublicPath('../assets');

if (mix.inProduction()) {
    mix.version();
} else {
    mix.webpackConfig({
        devtool: 'inline-source-map',
    })
}

