'use strict';

function bullToggleShrinkSettings(fieldName) {
    var idSuffix = fieldName.replace('rkbulletinmodule_appsettings_', '');
    jQuery('#shrinkDetails' + idSuffix).toggleClass('hidden', !jQuery('#rkbulletinmodule_appsettings_enableShrinkingFor' + idSuffix).prop('checked'));
}

jQuery(document).ready(function() {
    jQuery('.shrink-enabler').each(function (index) {
        jQuery(this).bind('click keyup', function (event) {
            bullToggleShrinkSettings(jQuery(this).attr('id').replace('enableShrinkingFor', ''));
        });
        bullToggleShrinkSettings(jQuery(this).attr('id').replace('enableShrinkingFor', ''));
    });
});
