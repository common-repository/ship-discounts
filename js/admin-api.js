jQuery(function ($) {
    $('#sd_lar_api_dev').on('change', function () {
        if ($(this).is(':checked')) {
            $('#dev-key').show();
            $('#prod-key').hide();
        } else {
            $('#dev-key').hide();
            $('#prod-key').show();
        }
    });
});