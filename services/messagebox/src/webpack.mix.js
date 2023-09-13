const mix = require('laravel-mix');
require('laravel-mix-svg-vue');

const path = require('path');
const hmrHost = process.env.MESSAGEBOX_HMR_HOST || 'localhost';
const hmrPort = parseInt(process.env.MESSAGEBOX_HMR_PORT || 9200);

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.setPublicPath('./public')
    .js(['resources/js/app.js'], 'js')
    .vue({
        extractStyles: 'public/css/app.css',
        runtimeOnly: mix.inProduction(),
    })
    .sass('resources/scss/app.scss', 'public/css')

    .webpackConfig({
        resolve: {
            alias: {
                '@': path.resolve('resources/js'),
            },
            extensions: ['*', '.js', '.jsx', '.vue', '.ts', '.tsx', '.json'],
        },
        devServer: {
            host: 'messagebox',
            port: 9000,
            proxy: {
                '/': 'http://messagebox:8080',
            },
        },
    })
    .sourceMaps()
    .svgVue()
    .version()
    .options({
        clearConsole: false,
        hmrOptions: {
            host: hmrHost,
            port: hmrPort,
        },
    });

if (mix.inProduction()) {
    mix.sourceMaps(true, 'source-map');
    mix.options({
        terser: {
            terserOptions: {
                compress: {
                    drop_console: true,
                },
            },
        },
    });
}

// Only display the main bundle stats
mix.after(stats => {
    stats.compilation.assets = Object.fromEntries(
        Object.entries(stats.compilation.assets).filter(([key]) => /\.(js|css)$/.test(key))
    );
});
