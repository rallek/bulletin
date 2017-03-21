'use strict';

function bulletinToggleShrinkSettings(fieldName) {
    var idSuffix = fieldName.replace('rkbulletinmodule_appsettings_', '');
    jQuery('#shrinkDetails' + idSuffix).toggleClass('hidden', !jQuery('#rkbulletinmodule_appsettings_enableShrinkingFor' + idSuffix).prop('checked'));
}

jQuery(document).ready(function() {
    jQuery('.shrink-enabler').each(function (index) {
        jQuery(this).bind('click keyup', function (event) {
            bulletinToggleShrinkSettings(jQuery(this).attr('id').replace('enableShrinkingFor', ''));
        });
        bulletinToggleShrinkSettings(jQuery(this).attr('id').replace('enableShrinkingFor', ''));
    });
});
