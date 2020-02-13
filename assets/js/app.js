require('../css/media-chooser.css');
require('../css/media.css');

import bulkUpload from './bulkupload.js';
var dndUpload = require('./dndupload.js').dndUpload;
var bulkmove = require('./bulkmove.js').bulkmove;
var hgabkamedia = {};

hgabkamedia.app = (function($, window, undefined) {
    var init;

    // General App init
    init = function () {
        bulkUpload.bulkUpload.init();
        dndUpload.dndUpload.init();
        bulkmove.bulkMove.init();
    }
    return {
        init: init
    };

})(jQuery, window);

$(function() {
    hgabkamedia.app.init();
    
    let $body = $('body');

    $body.on('click', '.js-media-chooser-del-preview-btn', function (e) {
        var $this = $(this),
            linkedID = $this.data('linked-id'),
            $widget = $('#' + linkedID + '-widget'),
            $input = $('#' + linkedID),
            $input2 = $('#' + linkedID + '_id');

        $this.parent('.media-chooser__preview').find('.media-chooser__preview__img').attr({'src': '', 'srcset': '', 'alt': ''});

        $(".media-thumbnail__icon").remove();

        $widget.removeClass('media-chooser--choosen');
        $input.val('');
        if ($input2.length) {
            $input2.val('');
        }
    });
});
