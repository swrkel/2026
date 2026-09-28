const dotenvExpand = require('dotenv-expand');
dotenvExpand(require('dotenv').config({ path: '../../.env' }));

const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

mix.setPublicPath('../../public').mergeManifest();

mix.js(__dirname + '/Resources/assets/js/app.js', 'modules/distribution/js/app.js')
    .js(__dirname + '/Resources/assets/js/distribution_payment.js', 'modules/distribution/js/distribution_payment.js')
    .sass(__dirname + '/Resources/assets/sass/app.scss', 'modules/distribution/css/app.css');

if (mix.inProduction()) {
    mix.version();
}
