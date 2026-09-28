const mix = require('laravel-mix');

mix.js(__dirname + '/Resources/assets/js/stock-taking-new.js', 'js')
   .postCss(__dirname + '/Resources/assets/css/stock-taking-new.css', 'css')
   .setPublicPath(__dirname + '/../../public/modules/stocktakingnew');
