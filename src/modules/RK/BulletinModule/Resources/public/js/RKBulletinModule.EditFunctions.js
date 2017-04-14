'use strict';

/**
 * Initialises a user field with auto completion.
 */
function rKBulletinInitUserField(fieldName, getterName)
{
    if (jQuery('#' + fieldName + 'LiveSearch').length < 1) {
        return;
    }
    jQuery('#' + fieldName + 'LiveSearch').removeClass('hidden');

    jQuery('#' + fieldName + 'Selector').typeahead({
        highlight: true,
        hint: true,
        minLength: 2
    }, {
        limit: 15,
        // The data source to query against. Receives the query value in the input field and the process callbacks.
        source: function (query, syncResults, asyncResults) {
            // Retrieve data from server using "query" parameter as it contains the search string entered by the user
            jQuery('#' + fieldName + 'Indicator').removeClass('hidden');
            jQuery.getJSON(Routing.generate('rkbulletinmodule_ajax_' + getterName.toLowerCase(), { fragment: query }), function( data ) {
                jQuery('#' + fieldName + 'Indicator').addClass('hidden');
                asyncResults(data);
            });
        },
        templates: {
            empty: '<div class="empty-message">' + jQuery('#' + fieldName + 'NoResultsHint').text() + '</div>',
            suggestion: function(user) {
                var html;

                html = '<div class="typeahead">';
                html += '<div class="media"><a class="pull-left" href="javascript:void(0)">' + user.avatar + '</a>';
                html += '<div class="media-body">';
                html += '<p class="media-heading">' + user.uname + '</p>';
                html += '</div>';
                html += '</div>';

                return html;
            }
        }
    }).bind('typeahead:select', function(ev, user) {
        // Called after the user selects an item. Here we can do something with the selection.
        jQuery('#' + fieldName).val(user.uid);
        jQuery(this).typeahead('val', user.uname);
    });
}


/**
 * Resets the value of an upload / file input field.
 */
function rKBulletinResetUploadField(fieldName)
{
    jQuery('#' + fieldName).attr('type', 'input');
    jQuery('#' + fieldName).attr('type', 'file');
}

/**
 * Initialises the reset button for a certain upload input.
 */
function rKBulletinInitUploadField(fieldName)
{
    jQuery('#' + fieldName + 'ResetVal').click( function (event) {
        event.preventDefault();
        rKBulletinResetUploadField(fieldName);
    }).removeClass('hidden');
}

/**
 * Resets the value of a date or datetime input field.
 */
function rKBulletinResetDateField(fieldName)
{
    jQuery('#' + fieldName).val('');
    jQuery('#' + fieldName + 'cal').html(Translator.__('No date set.'));
}

/**
 * Initialises the reset button for a certain date input.
 */
function rKBulletinInitDateField(fieldName)
{
    jQuery('#' + fieldName + 'ResetVal').click( function (event) {
        event.preventDefault();
        rKBulletinResetDateField(fieldName);
    }).removeClass('hidden');
}

var editedObjectType;
var editedEntityId;
var editForm;
var formButtons;
var triggerValidation = true;

function rKBulletinTriggerFormValidation()
{
    rKBulletinExecuteCustomValidationConstraints(editedObjectType, editedEntityId);

    if (!editForm.get(0).checkValidity()) {
        // This does not really submit the form,
        // but causes the browser to display the error message
        editForm.find(':submit').first().click();
    }
}

function rKBulletinHandleFormSubmit (event) {
    if (triggerValidation) {
        rKBulletinTriggerFormValidation();
        if (!editForm.get(0).checkValidity()) {
            event.preventDefault();
            return false;
        }
    }

    // hide form buttons to prevent double submits by accident
    formButtons.each(function (index) {
        jQuery(this).addClass('hidden');
    });

    return true;
}

/**
 * Initialises an entity edit form.
 */
function rKBulletinInitEditForm(mode, entityId)
{
    if (jQuery('.rkbulletin-edit-form').length < 1) {
        return;
    }

    editForm = jQuery('.rkbulletin-edit-form').first();
    editedObjectType = editForm.attr('id').replace('EditForm', '');
    editedEntityId = entityId;

    if (jQuery('#moderationFieldsSection').length > 0) {
        jQuery('#moderationFieldsContent').addClass('hidden');
        jQuery('#moderationFieldsSection legend').addClass('pointer').click(function (event) {
            if (jQuery('#moderationFieldsContent').hasClass('hidden')) {
                jQuery('#moderationFieldsContent').removeClass('hidden');
                jQuery(this).find('i').removeClass('fa-expand').addClass('fa-compress');
            } else {
                jQuery('#moderationFieldsContent').addClass('hidden');
                jQuery(this).find('i').removeClass('fa-compress').addClass('fa-expand');
            }
        });
    }

    var allFormFields = editForm.find('input, select, textarea');
    allFormFields.change(function (event) {
        rKBulletinExecuteCustomValidationConstraints(editedObjectType, editedEntityId);
    });

    formButtons = editForm.find('.form-buttons input');
    editForm.find('.btn-danger').first().bind('click keypress', function (event) {
        if (!window.confirm(Translator.__('Do you really want to delete this entry?'))) {
            event.preventDefault();
        }
    });
    editForm.find('button[type=submit]').bind('click keypress', function (event) {
        triggerValidation = !jQuery(this).attr('formnovalidate');
    });
    editForm.submit(rKBulletinHandleFormSubmit);

    if (mode != 'create') {
        rKBulletinTriggerFormValidation();
    }
}

/**
 * Toggles the fields of an auto completion field.
 */
function rKBulletinToggleRelatedItemForm(idPrefix)
{
    // if we don't have a toggle link do nothing
    if (jQuery('#' + idPrefix + 'AddLink').length < 1) {
        return;
    }

    // show/hide the toggle link
    jQuery('#' + idPrefix + 'AddLink').toggleClass('hidden');

    // hide/show the fields
    jQuery('#' + idPrefix + 'AddFields').toggleClass('hidden');
}

/**
 * Resets an auto completion field.
 */
function rKBulletinResetRelatedItemForm(idPrefix)
{
    // hide the sub form
    rKBulletinToggleRelatedItemForm(idPrefix);

    // reset value of the auto completion field
    jQuery('#' + idPrefix + 'Selector').val('');
}

/**
 * Helper function to create new modal form dialog instances.
 */
function rKBulletinCreateRelationWindowInstance(containerElem, useIframe)
{
    var newWindowId;

    // define the new window instance
    newWindowId = containerElem.attr('id') + 'Dialog';
    jQuery('<div id="' + newWindowId + '"></div>')
        .append(
            jQuery('<iframe />')
                .attr('src', containerElem.attr('href'))
                .css({ width: '100%', height: '440px' })
        )
        .dialog({
            autoOpen: false,
            show: {
                effect: 'blind',
                duration: 1000
            },
            hide: {
                effect: 'explode',
                duration: 1000
            },
            //title: containerElem.title,
            width: 600,
            height: 500,
            modal: false
        })
        .dialog('open');

    // return the instance
    return newWindowId;
}

/**
 * Observe a link for opening an inline window
 */
function rKBulletinInitInlineRelationWindow(objectType, containerID)
{
    var found, newItem;

    // whether the handler has been found
    found = false;

    // search for the handler
    jQuery.each(relationHandler, function (key, singleRelationHandler) {
        // is this the right one
        if (singleRelationHandler.prefix === containerID) {
            // yes, it is
            found = true;
            // look whether there is already a window instance
            if (null !== singleRelationHandler.windowInstanceId) {
                // unset it
                jQuery(containerID + 'Dialog').dialog('destroy');
            }
            // create and assign the new window instance
            singleRelationHandler.windowInstanceId = rKBulletinCreateRelationWindowInstance(jQuery('#' + containerID), true);
        }
    });

    if (false !== found) {
        return;
    }

    // if no handler was found create a new one
    newItem = {
        ot: objectType,
        prefix: containerID,
        moduleName: 'RKBulletinModule',
        acInstance: null,
        windowInstanceId: rKBulletinCreateRelationWindowInstance(jQuery('#' + containerID), true)
    };

    // add it to the list of handlers
    relationHandler.push(newItem);
}

/**
 * Removes a related item from the list of selected ones.
 */
function rKBulletinRemoveRelatedItem(idPrefix, removeId)
{
    var itemIds, itemIdsArr;

    itemIds = jQuery('#' + idPrefix).val();
    itemIdsArr = itemIds.split(',');

    itemIdsArr = jQuery.grep(itemIdsArr, function(value) {
        return value != removeId;
    });

    itemIds = itemIdsArr.join(',');

    jQuery('#' + idPrefix).val(itemIds);
    jQuery('#' + idPrefix + 'Reference_' + removeId).remove();
}

/**
 * Adds a related item to selection which has been chosen by auto completion.
 */
function rKBulletinSelectRelatedItem(objectType, idPrefix, selectedListItem)
{
    var newItemId, newTitle, includeEditing, editLink, removeLink, elemPrefix, itemPreview, li, editHref, fldPreview, itemIds, itemIdsArr;

    itemIds = jQuery('#' + idPrefix).val();
    if (itemIds !== '') {
        if (jQuery('#' + idPrefix + 'Scope').val() === '0') {
            jQuery('#' + idPrefix + 'ReferenceList').text('');
            itemIds = '';
        } else {
            itemIds += ',';
        }
    }

    newItemId = selectedListItem.id;
    newTitle = selectedListItem.title;
    includeEditing = !!((jQuery('#' + idPrefix + 'Mode').val() == '1'));
    elemPrefix = idPrefix + 'Reference_' + newItemId;
    itemPreview = '';

    if (selectedListItem.image != '') {
        itemPreview = selectedListItem.image;
    }

    var li = jQuery('<li />', { id: elemPrefix, text: newTitle });
    if (true === includeEditing) {
        var editHref = jQuery('#' + idPrefix + 'SelectorDoNew').attr('href') + '?id=' + newItemId;
        editLink = jQuery('<a />', { id: elemPrefix + 'Edit', href: editHref, text: 'edit' });
        li.append(editLink);
    }
    removeLink = jQuery('<a />', { id: elemPrefix + 'Remove', href: 'javascript:rKBulletinRemoveRelatedItem(\'' + idPrefix + '\', ' + newItemId + ');', text: 'remove' });
    li.append(removeLink);
    if (itemPreview !== '') {
        fldPreview = jQuery('<div>', { id: elemPrefix + 'preview', name: idPrefix + 'preview' });
        fldPreview.html(itemPreview);
        li.append(fldPreview);
        itemPreview = '';
    }
    jQuery('#' + idPrefix + 'ReferenceList').append(li);

    if (true === includeEditing) {
        editLink.html(' ' + editImage);

        jQuery('#' + elemPrefix + 'Edit').click( function (event) {
            event.preventDefault();
            rKBulletinInitInlineRelationWindow(objectType, idPrefix + 'Reference_' + newItemId + 'Edit');
        });
    }
    removeLink.html(' ' + removeImage);

    itemIds += newItemId;
    jQuery('#' + idPrefix).val(itemIds);

    rKBulletinResetRelatedItemForm(idPrefix);
}

/**
 * Initialises a relation field section with autocompletion and optional edit capabilities.
 */
function rKBulletinInitRelationItemsForm(objectType, idPrefix, includeEditing)
{
    var acOptions, acDataSet, itemIds, itemIdsArr, acUrl;

    // add handling for the toggle link if existing
    jQuery('#' + idPrefix + 'AddLink').click( function (e) {
        rKBulletinToggleRelatedItemForm(idPrefix);
    });

    // add handling for the cancel button
    jQuery('#' + idPrefix + 'SelectorDoCancel').click( function (e) {
        rKBulletinResetRelatedItemForm(idPrefix);
    });

    // clear values and ensure starting state
    rKBulletinResetRelatedItemForm(idPrefix);

    acOptions = {
        highlight: true,
        hint: true,
        minLength: 2,
    };
    acDataSet = {
        limit: 15,
        templates: {
            empty: '<div class="empty-message">' + jQuery('#' + idPrefix + 'NoResultsHint').text() + '</div>',
            suggestion: function(item) {
                var html;

                html = '<div class="typeahead">';
                html += '<div class="media"><a class="pull-left" href="javascript:void(0)">' + item.image + '</a>';
                html += '<div class="media-body">';
                html += '<p class="media-heading">' + item.title + '</p>';
                html += item.description;
                html += '</div>';
                html += '</div>';

                return html;
            }
        }
    };

    jQuery.each(relationHandler, function (key, singleRelationHandler) {
        if (singleRelationHandler.prefix !== (idPrefix + 'SelectorDoNew') || null !== singleRelationHandler.acInstance) {
            return;
        }

        singleRelationHandler.acInstance = 'yes';

        // The data source to query against. Receives the query value in the input field and the process callbacks.
        acDataSet.source = function (query, syncResults, asyncResults) {
            var acUrlArgs;

            acUrlArgs = {
                ot: objectType
            };
            if (jQuery('#' + idPrefix).length > 0) {
                acUrlArgs.exclude = jQuery('#' + idPrefix).val();
            }
            acUrl = Routing.generate(singleRelationHandler.moduleName.toLowerCase() + '_ajax_getitemlistautocompletion', acUrlArgs);

            // Retrieve data from server using "query" parameter as it contains the search string entered by the user
            jQuery('#' + idPrefix + 'Indicator').removeClass('hidden');
            jQuery.getJSON(acUrl, { fragment: query }, function( data ) {
                jQuery('#' + idPrefix + 'Indicator').addClass('hidden');
                asyncResults(data);
            });
        };

        jQuery('#' + idPrefix + 'Selector')
            .typeahead(acOptions, acDataSet)
            .bind('typeahead:select', function(ev, item) {
                // Called after the user selects an item. Here we can do something with the selection.
                rKBulletinSelectRelatedItem(objectType, idPrefix, item);
                jQuery(this).typeahead('val', item.title);
            });

        // Ensure that clearing out the selector is properly reflected into the hidden field
        jQuery('#' + idPrefix + 'Selector').blur(function() {
            if (jQuery(this).val().length == 0) {
                jQuery('#' + idPrefix).val('');
            }
        });
    });

    if (!includeEditing || jQuery('#' + idPrefix + 'SelectorDoNew').length < 1) {
        return;
    }

    // from here inline editing will be handled
    jQuery('#' + idPrefix + 'SelectorDoNew').attr('href', jQuery('#' + idPrefix + 'SelectorDoNew').attr('href') + '?raw=1&idp=' + idPrefix + 'SelectorDoNew');
    jQuery('#' + idPrefix + 'SelectorDoNew').click( function(event) {
        event.preventDefault();
        rKBulletinInitInlineRelationWindow(objectType, idPrefix + 'SelectorDoNew');
    });

    itemIds = jQuery('#' + idPrefix).val();
    itemIdsArr = itemIds.split(',');
    jQuery.each(itemIdsArr, function (key, existingId) {
        var elemPrefix;

        if (existingId) {
            elemPrefix = idPrefix + 'Reference_' + existingId + 'Edit';
            jQuery('#' + elemPrefix).attr('href', jQuery('#' + elemPrefix).attr('href') + '?raw=1&idp=' + elemPrefix);
            jQuery('#' + elemPrefix).click( function (event) {
                event.preventDefault();
                rKBulletinInitInlineRelationWindow(objectType, elemPrefix);
            });
        }
    });
}

/**
 * Closes an iframe from the document displayed in it.
 */
function rKBulletinCloseWindowFromInside(idPrefix, itemId)
{
    // if there is no parent window do nothing
    if (window.parent === '') {
        return;
    }

    // search for the handler of the current window
    jQuery.each(window.parent.relationHandler, function (key, singleRelationHandler) {
        // look if this handler is the right one
        if (singleRelationHandler.prefix === idPrefix) {
            // do we have an item created
            if (itemId > 0) {
                // look whether there is an auto completion instance
                if (null !== singleRelationHandler.acInstance) {
                    // activate it
                    jQuery('#' + idPrefix + 'Selector').lookup();
                    // show a message
                    rKBulletinSimpleAlert(jQuery('.rkbulletinmodule-form'), Translator.__('Information'), Translator.__('Action has been completed.'), 'actionDoneAlert', 'success');
                }
            }
            // look whether there is a windows instance
            if (null !== singleRelationHandler.windowInstanceId) {
                // close it
                window.parent.jQuery('#' + singleRelationHandler.windowInstanceId).dialog('close');
            }
        }
    });
}
