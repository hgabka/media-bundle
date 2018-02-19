var hgabkaMediaBundle = hgabkaMediaBundle || {};

hgabkaMediaBundle.app = (function($, window, undefined) {

    var init;

    init = function() {
        hgabkaMediaBundle.bulkUpload.init();
        hgabkaMediaBundle.dndUpload.init();
    };


    return {
        init: init
    };

}(jQuery, window));

$(function() {
    hgabkaMediaBundle.app.init();
});
