'use strict';

function rKBulletinToday(format)
{
    var timestamp, todayDate, month, day, hours, minutes, seconds;

    timestamp = new Date();
    todayDate = '';
    if (format !== 'time') {
        month = new String((parseInt(timestamp.getMonth()) + 1));
        if (month.length === 1) {
            month = '0' + month;
        }
        day = new String(timestamp.getDate());
        if (day.length === 1) {
            day = '0' + day;
        }
        todayDate += timestamp.getFullYear() + '-' + month + '-' + day;
    }
    if (format === 'datetime') {
        todayDate += ' ';
    }
    if (format != 'date') {
        hours = new String(timestamp.getHours());
        if (hours.length === 1) {
            hours = '0' + hours;
        }
        minutes = new String(timestamp.getMinutes());
        if (minutes.length === 1) {
            minutes = '0' + minutes;
        }
        seconds = new String(timestamp.getSeconds());
        if (seconds.length === 1) {
            seconds = '0' + seconds;
        }
        todayDate += hours + ':' + minutes;// + ':' + seconds;
    }

    return todayDate;
}

// returns YYYY-MM-DD even if date is in DD.MM.YYYY
function rKBulletinReadDate(val, includeTime)
{
    // look if we have YYYY-MM-DD
    if (val.substr(4, 1) === '-' && val.substr(7, 1) === '-') {
        return val;
    }

    // look if we have DD.MM.YYYY
    if (val.substr(2, 1) === '.' && val.substr(5, 1) === '.') {
        var newVal = val.substr(6, 4) + '-' + val.substr(3, 2) + '-' + val.substr(0, 2);
        if (true === includeTime) {
            newVal += ' ' + val.substr(11, 7);
        }

        return newVal;
    }
}

function rKBulletinValidateNoSpace(val)
{
    var valStr;
    valStr = new String(val);

    return (valStr.indexOf(' ') === -1);
}

function rKBulletinValidateUploadExtension(val, elem)
{
    var fileExtension, allowedExtensions;
    if (val === '') {
        return true;
    }

    fileExtension = '.' + val.substr(val.lastIndexOf('.') + 1);
    allowedExtensions = jQuery('#' + elem.attr('id') + 'FileExtensions').text();
    allowedExtensions = '(.' + allowedExtensions.replace(/, /g, '|.').replace(/,/g, '|.') + ')$';
    allowedExtensions = new RegExp(allowedExtensions, 'i');

    return allowedExtensions.test(val);
}

function rKBulletinValidateDateRangeNotice(val)
{
    var cmpVal, cmpVal2, result;
    cmpVal = rKBulletinReadDate(jQuery("[id$='startDate']").val(), true);
    cmpVal2 = rKBulletinReadDate(jQuery("[id$='endDate']").val(), true);

    if (typeof cmpVal == 'undefined' && typeof cmpVal2 == 'undefined') {
        result = true;
    } else {
        result = (cmpVal <= cmpVal2);
    }

    return result;
}

function rKBulletinValidateDateRangeEvent(val)
{
    var cmpVal, cmpVal2, result;
    cmpVal = rKBulletinReadDate(jQuery("[id$='startDate']").val(), true);
    cmpVal2 = rKBulletinReadDate(jQuery("[id$='endDate']").val(), true);

    if (typeof cmpVal == 'undefined' && typeof cmpVal2 == 'undefined') {
        result = true;
    } else {
        result = (cmpVal <= cmpVal2);
    }

    return result;
}

/**
 * Runs special validation rules.
 */
function rKBulletinExecuteCustomValidationConstraints(objectType, currentEntityId)
{
    jQuery('.validate-nospace').each( function() {
        if (!rKBulletinValidateNoSpace(jQuery(this).val())) {
            document.getElementById(jQuery(this).attr('id')).setCustomValidity(Translator.__('This value must not contain spaces.'));
        } else {
            document.getElementById(jQuery(this).attr('id')).setCustomValidity('');
        }
    });
    jQuery('.validate-upload').each( function() {
        if (!rKBulletinValidateUploadExtension(jQuery(this).val(), jQuery(this))) {
            document.getElementById(jQuery(this).attr('id')).setCustomValidity(Translator.__('Please select a valid file extension.'));
        } else {
            document.getElementById(jQuery(this).attr('id')).setCustomValidity('');
        }
    });
    jQuery('.validate-daterange-notice').each( function() {
        if (typeof jQuery(this).attr('id') != 'undefined') {
            if (jQuery(this).prop('tagName') == 'DIV') {
                if (!rKBulletinValidateDateRangeNotice()) {
                    document.getElementById(jQuery(this).attr('id') + '_date').setCustomValidity(Translator.__('The start must be before the end.'));
                    document.getElementById(jQuery(this).attr('id') + '_time').setCustomValidity(Translator.__('The start must be before the end.'));
                } else {
                    document.getElementById(jQuery(this).attr('id') + '_date').setCustomValidity('');
                    document.getElementById(jQuery(this).attr('id') + '_time').setCustomValidity('');
                }
        	} else {
                if (!rKBulletinValidateDateRangeNotice()) {
                    document.getElementById(jQuery(this).attr('id')).setCustomValidity(Translator.__('The start must be before the end.'));
                } else {
                    document.getElementById(jQuery(this).attr('id')).setCustomValidity('');
                }
    		}
        }
    });
    jQuery('.validate-daterange-event').each( function() {
        if (typeof jQuery(this).attr('id') != 'undefined') {
            if (jQuery(this).prop('tagName') == 'DIV') {
                if (!rKBulletinValidateDateRangeEvent()) {
                    document.getElementById(jQuery(this).attr('id') + '_date').setCustomValidity(Translator.__('The start must be before the end.'));
                    document.getElementById(jQuery(this).attr('id') + '_time').setCustomValidity(Translator.__('The start must be before the end.'));
                } else {
                    document.getElementById(jQuery(this).attr('id') + '_date').setCustomValidity('');
                    document.getElementById(jQuery(this).attr('id') + '_time').setCustomValidity('');
                }
        	} else {
                if (!rKBulletinValidateDateRangeEvent()) {
                    document.getElementById(jQuery(this).attr('id')).setCustomValidity(Translator.__('The start must be before the end.'));
                } else {
                    document.getElementById(jQuery(this).attr('id')).setCustomValidity('');
                }
    		}
        }
    });
}
