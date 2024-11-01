jQuery(function ($) {
    $('input#lar_signature').change({event}, lar_signature);

    $('input[name=lar_carrier]').change({event}, lar_on_change);
    $('input[name=lar_carrier]:checked').trigger('change');

    let count = {c: wc_var.cart_count};
    let prev = wc_var.trigger_check ? $('ul#shipping_method input.shipping_method:checked').val() : null;

    lar_check_qty(count);

    $(document.body).on('updated_cart_totals updated_checkout', function () {
        $('input#lar_signature').change({event}, lar_signature);
        $('input[name=lar_carrier]').change({event}, lar_on_change);

        if (prev !== $('ul#shipping_method input.shipping_method:checked').val()) {
            prev = $('ul#shipping_method input.shipping_method:checked').val();
            $('input[name=lar_carrier]:checked').trigger('change');
        } else {
            lar_check_qty(count);
        }
    });

    function lar_signature(e) {
        let checked = $(this).is(':checked');
        $.ajax({
            type: 'POST',
            url: ajax_var.url,
            data: {
                'action': 'sd_lar_signature',
                'nonce': ajax_var.nonce,
                'checked': checked
            },
            success: function (result) {
                $('body').trigger('update_checkout');
                $("[name='update_cart']").removeAttr('disabled');
                $("[name='update_cart']").trigger('click');

                $(document).ajaxStop(function () {
                    setTimeout(() => {
                        $('input[name=lar_carrier]:checked').trigger('change');
                        $(this).unbind('ajaxStop');
                    }, 2000);
                });
            },
            error: function (error) {
            }
        });
    }

    function lar_on_change(e) {
        e.preventDefault();
        let option = $(this).val();
        $.ajax({
            type: 'POST',
            url: ajax_var.url,
            data: {
                'action': 'sd_lar_carrier',
                'nonce': ajax_var.nonce,
                'sd_lar_carrier': option,
            },
            success: function (result) {
                $('body').trigger('update_checkout');
                $("[name='update_cart']").removeAttr('disabled');
                $("[name='update_cart']").trigger('click');
            },
            error: function (error) {
            }
        });
    }

    function lar_check_qty() {
        $.ajax({
            type: 'POST',
            url: ajax_var.url,
            data: {
                'action': 'sd_lar_cart_qty',
                'nonce': ajax_var.nonce,
            },
            success: function (result) {
                if (count.c != parseInt(result)) {
                    count.c = result;
                    $('input[name=lar_carrier]:checked').trigger('change');
                }
            },
            error: function (error) {
            }
        });
    }
});