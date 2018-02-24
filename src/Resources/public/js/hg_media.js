var kunstmaanbundles = {};



var kunstmaanMediaBundle = kunstmaanMediaBundle || {};

kunstmaanMediaBundle.bulkUpload = (function(window, undefined) {

    var init, initUploader;

    init = function() {
        initUploader();
    };


    initUploader = function() {
        // Get values and elements
        var $uploader = $('#bulk-upload'),
            url = $uploader.data('url');

        var $fileList = $('#bulk-upload__file-list'),
            $uploadWrapper = $('#bulk-button-wrapper--upload'),
            $completedWrapper = $('#bulk-button-wrapper--completed'),
            $pickFilesBtn = $('#bulk-button--pick-files'),
            $uploadFilesBtn = $('#bulk-button--upload-files');


        // Setup
        var bulkUploader = new plupload.Uploader({
                                                     runtimes : 'html5',
                                                     browse_button: 'bulk-button--pick-files',
                                                     container: 'bulk-upload__container',
                                                     url: url,
                                                     processing_id: null,

                                                     filters : {
                                                         max_file_size : '100mb'
                                                     },

                                                     init: {
                                                         PostInit: function() {
                                                             $fileList.html('<p class="list-group-item">No files selected</p>');

                                                             $uploadFilesBtn.on('click', function() {
                                                                 bulkUploader.start();
                                                             });
                                                         },

                                                         FilesAdded: function(up, files) {
                                                             $fileList.html('');

                                                             plupload.each(files, function(file) {
                                                                 $fileList.append('<li class="list-group-item" id="' + file.id + '">' + file.name + ' (<small>' + plupload.formatSize(file.size) + '</small>) <strong class="js-status"></strong></li>')
                                                             });

                                                             $uploadFilesBtn.removeClass('disabled');
                                                             $uploadFilesBtn.prop('disabled', false);
                                                             $uploadFilesBtn.addClass('btn-primary');
                                                             $pickFilesBtn.removeClass('btn-primary').addClass('btn-default');
                                                         },

                                                         UploadProgress: function(up, file) {
                                                             var $fileLine = $('#' + file.id);

                                                             $fileLine.find('.js-status').html(file.percent + '%');
                                                         },

                                                         Error: function(up, err) {
                                                             var $fileLine = $('#' + up.processing_id);

                                                             $fileLine.find('.js-status').html('ERROR: ' + err.message);
                                                         },

                                                         FileUploaded: function(up, file, res) {
                                                             var $fileLine = $('#' + file.id);

                                                             $fileLine.addClass('list-group-item-success');

                                                             var obj = null;
                                                             obj = JSON.parse(res.response);

                                                             if (obj.error) {
                                                                 $fileLine.addClass('list-group-item-danger');
                                                                 $fileLine.find('.js-status').html('ERROR: ' + obj.error.message);
                                                             } else {
                                                                 $fileLine.addClass('list-group-item-success');
                                                             }
                                                         },

                                                         UploadComplete: function(up, files) {
                                                             $completedWrapper.removeClass('hidden');
                                                         },

                                                         BeforeUpload: function(up, file) {
                                                             up.processing_id = file.id;
                                                             $uploadWrapper.addClass('hidden');
                                                         }
                                                     }
                                                 });

        // Initialize uploader
        bulkUploader.init();
    };


    return {
        init: init
    };

}(window));

kunstmaanMediaBundle.dndUpload = (function(window, undefined) {

    var init, initUpload;


    // Init
    init = function() {

        var $area = $('#dnd-area');

        if($area.length) {
            var $container = $('#dnd-container'),
                $status = $('#dnd-area__upload-status'),
                dropUrl = $area.data('drop-url'),
                currentUrl = $area.data('current-url');

            initUpload($area, $container, $status, dropUrl, currentUrl);
        }
    };


    // Upload
    initUpload = function($area, $container, $status, dropUrl, currentUrl) {
        var dndUploader = new plupload.Uploader({
                                                    runtimes : 'html5',
                                                    dragdrop: true,
                                                    drop_element: 'dnd-area',
                                                    browse_button : 'dnd-area-link',
                                                    url: dropUrl,
                                                    processing_id: null,

                                                    filters : {
                                                        max_file_size : '100mb'
                                                    },

                                                    init: {
                                                        PostInit: function() {
                                                            $(window).on('dragenter', function(e) {
                                                                console.log(e.originalEvent.dataTransfer.types);
                                                                if($.inArray('text/html', e.originalEvent.dataTransfer.types) === -1 && $.inArray('text/plain', e.originalEvent.dataTransfer.types) === -1) {
                                                                    $area.addClass('dnd-area--dragover');
                                                                }
                                                            });

                                                            $area.on('dragleave drop dragend', function() {
                                                                $area.removeClass('dnd-area--dragover');
                                                            });
                                                        },

                                                        FilesAdded: function(up, files) {
                                                            plupload.each(files, function(file) {
                                                                $status.append('<li class="list-group-item" id="' + file.id + '">' + file.name + ' (<small>' + plupload.formatSize(file.size) + '</small>) <strong class="js-status"></strong></li>')
                                                            });

                                                            dndUploader.start();
                                                        },

                                                        UploadProgress: function(up, file) {
                                                            var $fileLine = $('#' + file.id);

                                                            $fileLine.find('.js-status').html(file.percent + '%');
                                                        },

                                                        UploadComplete: function(up, files) {
                                                            // Set Loading
                                                            $('body').addClass('app--loading');

                                                            $area.addClass('dnd-area--upload-done');

                                                            window.location = currentUrl;
                                                        }
                                                    }
                                                });


        // Initialize uploader
        dndUploader.init();
    };


    return {
        init: init
    };

}(window));

kunstmaanMediaBundle.app = (function($, window, undefined) {

    var init;

    init = function() {
        kunstmaanMediaBundle.bulkUpload.init();
        kunstmaanMediaBundle.dndUpload.init();
    };


    return {
        init: init
    };

}(jQuery, window));


$(function() {
    kunstmaanMediaBundle.app.init();
});
