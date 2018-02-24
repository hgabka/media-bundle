var kunstmaanbundles = {};

kunstmaanbundles.sidebartoggle = (function(window, undefined) {

    var init,
        toggle;

    init = function() {
        toggle();
    };

    toggle = function() {
        var $appMain = $('#app__main'),
            $toggleButton = $('#app__sidebar-toggle');

        // Set default session state
        if(sessionStorage.getItem('altered-state') === 'true' && $toggleButton && document.documentElement.clientWidth >= 992) {
            $appMain.toggleClass('app__main--altered-state');
        }

        // Toggle button
        $toggleButton.on('click', function() {
            $appMain.toggleClass('app__main--altered-state');

            if($appMain.hasClass('app__main--altered-state')) {
                sessionStorage.setItem('altered-state', 'true');
            } else {
                sessionStorage.setItem('altered-state', 'false');
            }
        });
    };

    return {
        init: init
    };

})(window);
kunstmaanbundles.sidebartree = (function($, window, undefined) {

    var init,
        canBeMoved,
        buildTree, searchTree,
        $sidebar = $('#app__sidebar'),
        $sidebarNavContainer = $('#app__sidebar__navigation'),
        $searchField = $('#app__sidebar__search'),
        movingConfirmation = $sidebarNavContainer.data('moving-confirmation') || "You sure?";

    init = function() {
        if($sidebarNavContainer !== 'undefined' && $sidebarNavContainer !== null) {
            buildTree();
            searchTree();
        }
    };

    canBeMoved = function (node, parent) {
        if (!node.data.page || !node.data.page.class || !parent.data.page || !parent.data.page.children) {
            return false;
        }


        for (var i = parent.data.page.children.length, e; e = parent.data.page.children[--i]; ) {
            if (e.class === node.data.page.class) {
                return true;
            }
        }

        return false;

    };

    buildTree = function() {

        // Show when ready
        $sidebarNavContainer.on('ready.jstree', function() {

            // DOCS: http://www.jstree.com/api/#/?q=.jstree%20Event&f=ready.jstree
            $sidebar.addClass('app__sidebar--tree-ready');
        });


        // Go to url
        $sidebarNavContainer.on('changed.jstree', function(e, data) {
            // DOCS: http://www.jstree.com/api/#/?q=.jstree%20Event&f=changed.jstree
            var href = data.event.currentTarget.href;

            if (data.event.ctrlKey || data.event.metaKey) {
                window.open(href);
            } else {
                document.location.href = href;
            }
        });


        // Drag and drop callback
        $sidebarNavContainer.on('move_node.jstree', function(e, data) {
            // DOCS: http://www.jstree.com/api/#/?q=.jstree%20Event&f=move_node.jstree

            // Vars
            var $container = $(this),
                parentNode = data.parent,
                reorderUrl = $container.data('reorder-url'),
                params = {
                    nodes : []
                };

            // Recalc id's
            $('#' + parentNode).find('> ul > li').each(function() {
                var newId = $(this).attr('id').replace(/node-/,'');

                params.nodes.push(newId);
            });

            if (data.old_parent !== data.parent) {
                params.parent = {};
                params.parent[data.node.id.replace(/node-/, '')] = data.parent.replace(/node-/, '');
                if (0 === params.nodes.length) {
                    params.nodes.push(data.node.id.replace(/node-/, ''));
                }
            }

            //; Save
            $.post(
                reorderUrl,
                params,
                function(){
                    console.log('move_node saved');
                }
            );
        });


        // Create
        $sidebarNavContainer.jstree({
                                        'core': {
                                            'check_callback': function (operation, node, node_parent, node_position, more) {
                                                // operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
                                                // in case of 'rename_node' node_position is filled with the new node name

                                                if(operation === 'move_node') {

                                                    // No dnd outsite root
                                                    if(!node_parent || node_parent.id === '#') {
                                                        return false;
                                                    }

                                                    // Only on same level please
                                                    if(node.parent === node_parent.id) {
                                                        return true;
                                                    }

                                                    return canBeMoved(node, node_parent) && !(more && more.core && !confirm(movingConfirmation
                                                                                                                                .replace('%title%', node.text.replace(/^\s+|\s+$/g, ''))
                                                                                                                                .replace('%target%', node_parent.text.replace(/^\s+|\s+$/g, ''))
                                                    ));

                                                } else {

                                                    return true;
                                                }
                                            }
                                        },
                                        'plugins': [
                                            'types',
                                            'search',
                                            'dnd'
                                        ],
                                        'types': {
                                            '#': {
                                                'icon': 'fa fa-home'
                                            },
                                            'default': {
                                                'icon' : 'fa fa-file-o'
                                            },
                                            'offline': {
                                                'icon': 'fa fa-chain-broken'
                                            },
                                            'folder': {
                                                'icon': 'fa fa-folder-o'
                                            },
                                            'image': {
                                                'icon': 'fa fa-picture-o'
                                            },
                                            'files': {
                                                'icon': 'fa fa-files-o'
                                            },
                                            'slideshow': {
                                                'icon': 'fa fa-desktop'
                                            },
                                            'video': {
                                                'icon': 'fa fa-film'
                                            },
                                            'media': {
                                                'icon': 'fa fa-folder-o'
                                            }
                                        },
                                        'search' : {
                                            'show_only_matches': true
                                        }
                                    });

    };

    searchTree = function() {

        if($searchField !== 'undefined' && $searchField !== null) {
            $searchField.on('keyup', function() {

                var searchValue = $searchField.val();

                $sidebarNavContainer.jstree(true).search(searchValue);
            });
        }
    };

    return {
        init: init
    };

})(jQuery, window);

kunstmaanbundles.app = (function($, window, undefined) {

    var init, appScroll,
        $mainActions = $('#page-main-actions-top');


    // General App init
    init = function() {
        cargobay.toggle.init();
        cargobay.scrollToTop.init();

        appScroll();

        kunstmaanbundles.sidebartoggle.init();
        kunstmaanbundles.sidebartree.init();
        kunstmaanbundles.sidebarsearchfocus.init();
 /*       kunstmaanbundles.urlchoosertree.init();
        kunstmaanbundles.sidebarsearchfocus.init();
        kunstmaanbundles.filter.init();
        kunstmaanbundles.sortableTable.init();
        kunstmaanbundles.checkIfEdited.init();
        kunstmaanbundles.preventDoubleClick.init();
        kunstmaanbundles.datepicker.init();*/
        kunstmaanbundles.autoCollapseButtons.init();
        kunstmaanbundles.autoCollapseTabs.init();
    /*    kunstmaanbundles.richEditor.init();
        kunstmaanbundles.ajaxModal.init();
        kunstmaanbundles.advancedSelect.init();

        kunstmaanbundles.pageEditor.init();
        kunstmaanbundles.pagepartEditor.init();

        kunstmaanbundles.slugChooser.init();
        kunstmaanbundles.urlChooser.init();
        kunstmaanbundles.mediaChooser.init();
        kunstmaanbundles.iconChooser.init();
        kunstmaanbundles.bulkActions.init();
        kunstmaanbundles.nestedForm.init();
        kunstmaanbundles.appLoading.init();
        kunstmaanbundles.tooltip.init();
        kunstmaanbundles.colorpicker.init();
        kunstmaanbundles.charactersLeft.init();
        kunstmaanbundles.rangeslider.init();
        kunstmaanbundles.googleOAuth.init();
        kunstmaanbundles.appNodeVersionLock.init();
        kunstmaanbundles.appEntityVersionLock.init()*/
    };


    // On Scroll
    appScroll = function() {
        if($mainActions) {
            var _onScroll, _requestTick, _update,
                latestKnownScrollY = 0,
                ticking = false;

            _onScroll = function() {
                latestKnownScrollY = window.pageYOffset;
                _requestTick();
            };

            _requestTick = function() {
                if(!ticking) {
                    window.requestAnimationFrame(_update);
                }

                ticking = true;
            };

            _update = function() {
                ticking = false;
                var currentScrollY = latestKnownScrollY;

                kunstmaanbundles.mainActions.updateScroll(currentScrollY, $mainActions);
            };

            window.onscroll = function(e) {
                _onScroll();
            };
        }
    };


    return {
        init: init
    };

})(jQuery, window);

kunstmaanbundles.sidebarsearchfocus = (function(window, undefined) {

    var init,
        focus;

    init = function() {
        focus();
    };

    focus = function() {
        var $toggleButton = $('.app__sidebar__search-toggle-btn'),
            $searchInput = $('#app__sidebar__search');

        $toggleButton.on('click touchstart mousedown', function(e) {
            e.preventDefault();
        }).on('touchend mouseup', function() {
            $searchInput.focus();
        });
    };

    return {
        init: init
    };

})(window);

kunstmaanbundles.autoCollapseButtons = (function ($, window, undefined) {

    var init, createMoreDropdown,
        buttonsVisible,
        $autoCollapseButtons, $btnGroup, $allButtons, $buttonsRedundant, $moreButtonContainer, $moreButton, $caret, $dropdownList;

    init = function () {
        buttonsVisible = 2;

        $autoCollapseButtons = $('.js-auto-collapse-buttons');
        $btnGroup = $autoCollapseButtons.find('.btn-group');
        if ($btnGroup.parent().attr('data-visible-buttons')) {
            buttonsVisible = $btnGroup.parent().data('visible-buttons');
        }
        $allButtons = $btnGroup.children('button, a'); // select only anchors and buttons

        // add more-dropdown when there are at least 2 buttons for dropdown
        if ($allButtons.length > (buttonsVisible + 1)) {
            $buttonsRedundant = $allButtons.slice(buttonsVisible);
            createMoreDropdown();
        }
    };

    createMoreDropdown = function () {
        // create dom elements
        $moreButtonContainer = $('<div class="btn-group btn-group--more">').appendTo($btnGroup);
        var label = MORE_BUTTON_LABEL
            ? MORE_BUTTON_LABEL
            : 'More';
        $moreButton = $('<button class="btn btn-default btn--raise-on-hover dropdown-toggle" data-toggle="dropdown">').text(label + ' ').appendTo($moreButtonContainer);
        $caret = $('<span class="fa fa-caret-down">').appendTo($moreButton);
        $dropdownList = $('<ul class="dropdown-menu dropdown-menu-right dropdown-menu--more">').appendTo($moreButtonContainer);

        // move buttons to dropdown list & remove styling
        $buttonsRedundant.each(function () {
            var $li = $('<li>');

            $(this).removeClass().addClass('btn-dropdown-menu js-save-btn').appendTo($li);
            $li.appendTo($dropdownList);
        });
    };

    return {
        init: init
    };

})(jQuery, window);

kunstmaanbundles.autoCollapseTabs = (function($, window, undefined) {

    var $tabs, $btnMore, $dropdown,
        init, dropdownItems, tabsHeight, children, singleTabHeight, initTabLogic, replaceUrlParam, doCheck;

    init = function() {
        $tabs = $('.js-auto-collapse-tabs');
        $btnMore = $('.tab__more');
        $dropdown = $('#collapsed');
        singleTabHeight = $tabs.find('li:first-child').innerHeight(); // get single height

        initTabLogic();
        doCheck();

        $(window).on('resize', function() {
            doCheck();
        }); // when window is resized
    };

    initTabLogic = function () {
        // If there is a tab defined in the url, we activate it
        var currentTabElement = $('#currenttab');
        if (typeof(currentTabElement) != 'undefined' && currentTabElement != null && currentTabElement.val() && currentTabElement.val().length > 0) {
            $('.js-auto-collapse-tabs.nav-tabs a[href="' + $('#currenttab').val() + '"]').tab('show');
        }

        // When tab click, add the current tab in the url
        $('.js-auto-collapse-tabs.nav-tabs a').click(function (e) {
            $(this).tab('show');

            var activeTab = this.hash.substr(1);
            if (history.pushState) {
                window.history.pushState({}, null, replaceUrlParam(window.location.href, 'currenttab', activeTab));
            }

            if (typeof(currentTabElement) != 'undefined' && currentTabElement != null) {
                currentTabElement.val(activeTab);
            }
        });

        // When the form get ssubmitted, change the action url
        $('#pageadminform .js-save-btn').on('click', function() {
            var form = $('#pageadminform');
            form.attr('action', window.location.href);
        });
    };

    replaceUrlParam = function (url, paramName, paramValue) {
        var pattern = new RegExp('(' + paramName + '=).*?(&|$)'),
            newUrl = url;

        if (url.search(pattern) >= 0) {
            newUrl = url.replace(pattern, '$1' + paramValue + '$2');
        } else {
            newUrl = newUrl + (newUrl.indexOf('?')>0 ? '&' : '?') + paramName + '=' + paramValue;
        }

        return newUrl;
    };

    doCheck = function() {
        tabsHeight = $tabs.innerHeight();
        children = $tabs.children('li:not(:last-child):not(:first-child)'); // Don't count the 'more' tab and always show first tab

        if (tabsHeight >= singleTabHeight) {

            while (tabsHeight > singleTabHeight && children.size() > 0) {
                $btnMore.show(); // show immediately when first tab is added to dropdown

                // move tab to dropdown
                $(children[children.size()-1]).prependTo($dropdown);

                // recalculate
                tabsHeight = $tabs.innerHeight();
                children = $tabs.children('li:not(:last-child):not(:first-child)');
            }

        } else {
            dropdownItems = $dropdown.children('li');

            while (tabsHeight < singleTabHeight && dropdownItems.size() > 0) {
                $(dropdownItems[0]).insertBefore($tabs.children('li:last-child'));

                // recalculate
                tabsHeight = $tabs.innerHeight();
                dropdownItems = $dropdown.children('li');
            }

            if (tabsHeight > singleTabHeight) { // double chk height again
                doCheck();
            }
        }

        // hide the more button if dropdown is empty
        dropdownItems = $dropdown.children('li');
        if (dropdownItems.size() <= 0) {
            $btnMore.hide();

        } else {
            $btnMore.show();

            // check if active element is in dropdown
            if ($dropdown.children('li.active').size() > 0) {
                $btnMore.addClass('active');
            } else {
                $btnMore.removeClass('active');
            }
        }
    };

    return {
        init: init
    };

})(jQuery, window);

kunstmaanbundles.mainActions = (function(window, undefined) {

    var updateScroll;

    updateScroll = function(currentScrollY, $menu) {

        if(currentScrollY >= 120){
            $menu.addClass('page-main-actions--top--show');
        }

        if(currentScrollY < 120){
            $menu.removeClass('page-main-actions--top--show');
        }
    };

    return {
        updateScroll: updateScroll
    };

})(window);


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
    kunstmaanbundles.app.init();
    kunstmaanMediaBundle.app.init();
});
