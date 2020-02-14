var Encore = require('@symfony/webpack-encore');
var webpack = require('webpack');
Encore
.addEntry('hgabkamedia', [
    './vendor/hgabka/media-bundle/assets/js/app.js'
])
