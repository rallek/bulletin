'use strict';

var currentRKBulletinModuleEditor = null;
var currentRKBulletinModuleInput = null;

/**
 * Returns the attributes used for the popup window. 
 * @return {String}
 */
function getRKBulletinModulePopupAttributes()
{
    var pWidth, pHeight;

    pWidth = screen.width * 0.75;
    pHeight = screen.height * 0.66;

    return 'width=' + pWidth + ',height=' + pHeight + ',scrollbars,resizable';
}

/**
 * Open a popup window with the finder triggered by a CKEditor button.
 */
function RKBulletinModuleFinderCKEditor(editor, bullUrl)
{
    // Save editor for access in selector window
    currentRKBulletinModuleEditor = editor;

    editor.popup(
        Routing.generate('rkbulletinmodule_external_finder', { objectType: 'notice', editor: 'ckeditor' }),
        /*width*/ '80%', /*height*/ '70%',
        'location=no,menubar=no,toolbar=no,dependent=yes,minimizable=no,modal=yes,alwaysRaised=yes,resizable=yes,scrollbars=yes'
    );
}


var rKBulletinModule = {};

rKBulletinModule.finder = {};

rKBulletinModule.finder.onLoad = function (baseId, selectedId)
{
    var imageModeEnabled;

    imageModeEnabled = jQuery("[id$='onlyImages']").prop('checked');
    if (!imageModeEnabled) {
        jQuery('#imageFieldRow').addClass('hidden');
        jQuery("[id$='pasteAs'] option[value=6]").addClass('hidden');
        jQuery("[id$='pasteAs'] option[value=7]").addClass('hidden');
        jQuery("[id$='pasteAs'] option[value=8]").addClass('hidden');
        jQuery("[id$='pasteAs'] option[value=9]").addClass('hidden');
    } else {
        jQuery('#searchTermRow').addClass('hidden');
    }

    jQuery('input[type="checkbox"]').click(rKBulletinModule.finder.onParamChanged);
    jQuery('select').not("[id$='pasteAs']").change(rKBulletinModule.finder.onParamChanged);
    
    jQuery('.btn-default').click(rKBulletinModule.finder.handleCancel);

    var selectedItems = jQuery('#rkbulletinmoduleItemContainer a');
    selectedItems.bind('click keypress', function (event) {
        event.preventDefault();
        rKBulletinModule.finder.selectItem(jQuery(this).data('itemid'));
    });
};

rKBulletinModule.finder.onParamChanged = function ()
{
    jQuery('#rKBulletinModuleSelectorForm').submit();
};

rKBulletinModule.finder.handleCancel = function (event)
{
    var editor;

    event.preventDefault();
    editor = jQuery("[id$='editor']").first().val();
    if ('tinymce' === editor) {
        rKBulletinClosePopup();
    } else if ('ckeditor' === editor) {
        rKBulletinClosePopup();
    } else {
        alert('Close Editor: ' + editor);
    }
};


function rKBulletinGetPasteSnippet(mode, itemId)
{
    var quoteFinder;
    var itemPath;
    var itemUrl;
    var itemTitle;
    var itemDescription;
    var imagePath;
    var pasteMode;

    quoteFinder = new RegExp('"', 'g');
    itemPath = jQuery('#path' + itemId).val().replace(quoteFinder, '');
    itemUrl = jQuery('#url' + itemId).val().replace(quoteFinder, '');
    itemTitle = jQuery('#title' + itemId).val().replace(quoteFinder, '').trim();
    itemDescription = jQuery('#desc' + itemId).val().replace(quoteFinder, '').trim();
    imagePath = jQuery('#imagePath' + itemId).length > 0 ? jQuery('#imagePath' + itemId).val().replace(quoteFinder, '') : '';
    pasteMode = jQuery("[id$='pasteAs']").first().val();

    // item ID
    if (pasteMode === '3') {
        return '' + itemId;
    }

    // relative link to detail page
    if (pasteMode === '1') {
        return mode === 'url' ? itemPath : '<a href="' + itemPath + '" title="' + itemDescription + '">' + itemTitle + '</a>';
    }
    // absolute url to detail page
    if (pasteMode === '2') {
        return mode === 'url' ? itemUrl : '<a href="' + itemUrl + '" title="' + itemDescription + '">' + itemTitle + '</a>';
    }

    if (pasteMode === '6') {
        // relative link to image file
        return mode === 'url' ? imagePath : '<a href="' + imagePath + '" title="' + itemDescription + '">' + itemTitle + '</a>';
    }
    if (pasteMode === '7') {
        // image tag
        return '<img src="' + imagePath + '" alt="' + itemTitle + '" width="300" />';
    }
    if (pasteMode === '8') {
        // image tag with relative link to detail page
        return mode === 'url' ? itemPath : '<a href="' + itemPath + '" title="' + itemTitle + '"><img src="' + imagePath + '" alt="' + itemTitle + '" width="300" /></a>';
    }
    if (pasteMode === '9') {
        // image tag with absolute url to detail page
        return mode === 'url' ? itemUrl : '<a href="' + itemUrl + '" title="' + itemTitle + '"><img src="' + imagePath + '" alt="' + itemTitle + '" width="300" /></a>';
    }


    return '';
}


// User clicks on "select item" button
rKBulletinModule.finder.selectItem = function (itemId)
{
    var editor, html;

    editor = jQuery("[id$='editor']").first().val();
    if ('tinymce' === editor) {
        html = rKBulletinGetPasteSnippet('html', itemId);
        tinyMCE.activeEditor.execCommand('mceInsertContent', false, html);
        // other tinymce commands: mceImage, mceInsertLink, mceReplaceContent, see http://www.tinymce.com/wiki.php/Command_identifiers
    } else if ('ckeditor' === editor) {
        if (null !== window.opener.currentRKBulletinModuleEditor) {
            html = rKBulletinGetPasteSnippet('html', itemId);

            window.opener.currentRKBulletinModuleEditor.insertHtml(html);
        }
    } else {
        alert('Insert into Editor: ' + editor);
    }
    rKBulletinClosePopup();
};

function rKBulletinClosePopup()
{
    window.opener.focus();
    window.close();
}




//=============================================================================
// RKBulletinModule item selector for Forms
//=============================================================================

rKBulletinModule.itemSelector = {};
rKBulletinModule.itemSelector.items = {};
rKBulletinModule.itemSelector.baseId = 0;
rKBulletinModule.itemSelector.selectedId = 0;

rKBulletinModule.itemSelector.onLoad = function (baseId, selectedId)
{
    rKBulletinModule.itemSelector.baseId = baseId;
    rKBulletinModule.itemSelector.selectedId = selectedId;

    // required as a changed object type requires a new instance of the item selector plugin
    jQuery('#rKBulletinModuleObjectType').change(rKBulletinModule.itemSelector.onParamChanged);

    jQuery('#' + baseId + '_catidMain').change(rKBulletinModule.itemSelector.onParamChanged);
    jQuery('#' + baseId + '_catidsMain').change(rKBulletinModule.itemSelector.onParamChanged);
    jQuery('#' + baseId + 'Id').change(rKBulletinModule.itemSelector.onItemChanged);
    jQuery('#' + baseId + 'Sort').change(rKBulletinModule.itemSelector.onParamChanged);
    jQuery('#' + baseId + 'SortDir').change(rKBulletinModule.itemSelector.onParamChanged);
    jQuery('#rKBulletinModuleSearchGo').click(rKBulletinModule.itemSelector.onParamChanged);
    jQuery('#rKBulletinModuleSearchGo').keypress(rKBulletinModule.itemSelector.onParamChanged);

    rKBulletinModule.itemSelector.getItemList();
};

rKBulletinModule.itemSelector.onParamChanged = function ()
{
    jQuery('#ajax_indicator').removeClass('hidden');

    rKBulletinModule.itemSelector.getItemList();
};

rKBulletinModule.itemSelector.getItemList = function ()
{
    var baseId;
    var params;

    baseId = rKBulletinModule.itemSelector.baseId;
    params = {
        ot: baseId,
        sort: jQuery('#' + baseId + 'Sort').val(),
        sortdir: jQuery('#' + baseId + 'SortDir').val(),
        q: jQuery('#' + baseId + 'SearchTerm').val()
    }
    if (jQuery('#' + baseId + '_catidMain').length > 0) {
        params[catidMain] = jQuery('#' + baseId + '_catidMain').val();
    } else if (jQuery('#' + baseId + '_catidsMain').length > 0) {
        params[catidsMain] = jQuery('#' + baseId + '_catidsMain').val();
    }

    jQuery.ajax({
        type: 'POST',
        url: Routing.generate('rkbulletinmodule_ajax_getitemlistfinder'),
        data: params
    }).done(function(res) {
        // get data returned by the ajax response
        var baseId;
        baseId = rKBulletinModule.itemSelector.baseId;
        rKBulletinModule.itemSelector.items[baseId] = res.data;
        jQuery('#ajax_indicator').addClass('hidden');
        rKBulletinModule.itemSelector.updateItemDropdownEntries();
        rKBulletinModule.itemSelector.updatePreview();
    });
};

rKBulletinModule.itemSelector.updateItemDropdownEntries = function ()
{
    var baseId, itemSelector, items, i, item;

    baseId = rKBulletinModule.itemSelector.baseId;
    itemSelector = jQuery('#' + baseId + 'Id');
    itemSelector.length = 0;

    items = rKBulletinModule.itemSelector.items[baseId];
    for (i = 0; i < items.length; ++i) {
        item = items[i];
        itemSelector.get(0).options[i] = new Option(item.title, item.id, false);
    }

    if (rKBulletinModule.itemSelector.selectedId > 0) {
        jQuery('#' + baseId + 'Id').val(rKBulletinModule.itemSelector.selectedId);
    }
};

rKBulletinModule.itemSelector.updatePreview = function ()
{
    var baseId, items, selectedElement, i;

    baseId = rKBulletinModule.itemSelector.baseId;
    items = rKBulletinModule.itemSelector.items[baseId];

    jQuery('#' + baseId + 'PreviewContainer').addClass('hidden');

    if (items.length === 0) {
        return;
    }

    selectedElement = items[0];
    if (rKBulletinModule.itemSelector.selectedId > 0) {
        for (var i = 0; i < items.length; ++i) {
            if (items[i].id == rKBulletinModule.itemSelector.selectedId) {
                selectedElement = items[i];
                break;
            }
        }
    }

    if (null !== selectedElement) {
        jQuery('#' + baseId + 'PreviewContainer')
            .html(window.atob(selectedElement.previewInfo))
            .removeClass('hidden');
        rKBulletinInitImageViewer();
    }
};

rKBulletinModule.itemSelector.onItemChanged = function ()
{
    var baseId, itemSelector, preview;

    baseId = rKBulletinModule.itemSelector.baseId;
    itemSelector = jQuery('#' + baseId + 'Id').get(0);
    preview = window.atob(rKBulletinModule.itemSelector.items[baseId][itemSelector.selectedIndex].previewInfo);

    jQuery('#' + baseId + 'PreviewContainer').html(preview);
    rKBulletinModule.itemSelector.selectedId = jQuery('#' + baseId + 'Id').val();
    rKBulletinInitImageViewer();
};
