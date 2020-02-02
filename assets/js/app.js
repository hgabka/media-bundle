require('../css/media-chooser.css');

import bulkUpload from './bulkupload.js';
var dndUpload = require('./dndupload.js').dndUpload;
var hgabkamedia = {};

hgabkamedia.app = (function($, window, undefined) {
    var init;

    // General App init
    init = function () {
        bulkUpload.bulkUpload.init();
        dndUpload.dndUpload.init();
    }
    return {
        init: init
    };

})(jQuery, window);

$(function() {
    hgabkamedia.app.init();
});