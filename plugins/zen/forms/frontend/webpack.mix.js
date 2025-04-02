const mix = require('laravel-mix')
const path = require('path');
mix.sass('src/scss/forms.scss', 'css')
mix.js('src/js/forms-app.js', 'js').vue()

mix.setPublicPath('assets');

if (mix.inProduction()) {
    mix.version();
} else {
    mix.webpackConfig({
        devtool: 'inline-source-map',
    })
}

mix.copyDirectory(path.join(__dirname, 'src/images'), 'assets/images');
