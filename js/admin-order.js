jQuery(function ($) {
    $('#shipping-note').on('input', function() {
        let length = $(this).val().length ? $(this).val().length : 0;
        let counter = $('#shipping-note-counter').children('span').first();

        counter.html(length);
        if (length <= 30)
            counter.css('color', 'green');
        else
            counter.css('color', 'red');
    });

    /**
     * Select all boxes.
     */
    $('.lar_boxes_table thead input[type="checkbox"]').on('change', function (e) {
        if ($(this).is(':checked'))
            $('.lar_boxes_table').find('tbody').find('.check-column input[type="checkbox"]').prop('checked', true);
        else
            $('.lar_boxes_table').find('tbody').find('.check-column input[type="checkbox"]').prop('checked', false);
    });

    /**
     * Remove all checked boxes from the table.
     */
    $('.lar_boxes_table .lar_boxes_remove').on('click', function (e) {
        e.preventDefault();
        $('.lar_boxes_table thead input[type="checkbox"]').prop('checked', false);

        $('.lar_boxes_table').find('tbody').find('.check-column input:checked').each(function () {
            $(this).closest('tr').remove();
        });
    });

    /**
     * Add a new row in the boxes table.
     */
    $('.lar_boxes_table .lar_boxes_insert').on('click', function (e) {
        $('.lar_boxes_table thead input[type="checkbox"]').prop('checked', false);

        let tbody = $('.lar_boxes_table').find('tbody');
        let size = tbody.find('tr').size();
        let code = '<tr class="new">\
									<td class="check-column"><span><input type="checkbox" /></span></td>\
									<td><span><input type="text" name="packages_length[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="packages_width[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="packages_height[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="packages_weight[' + size + ']" />lbs</span></td>\
								</tr>';
        tbody.append(code);
    });

    /**
     * Get the carriers' quotes.
     */
    $('#btn-lar-get-quotes').on('click', function (e) {
        e.preventDefault();
        $('.carriers-list-carriers div').not('.default-carrier').remove();
        $('.carriers-list-carriers input[type="radio"]').prop('checked', true);

        if (!wc_var.sku) {
            let html = '<div class="carrier-loader">' + wc_var.sku_error + '</div>';
            $('.carriers-list-carriers').append(html);
            return;
        }

        $('.carriers-list-carriers').append("<div class='carrier-loader'><img src='" + imgLoader.src + "'/></div>");

        let signature = $('#signature').is(':checked') ? 1 : 0;
        let ncv = $('#ncv').is(':checked') ? 1 : 0;
        let packages = getPackages();

        $.ajax({
            type: 'POST',
            url: ajax_var.url,
            data: {
                'action': 'sd_lar_get_carriers_quotes',
                'nonce': ajax_var.nonce,
                'order': wc_var.wc_order_id,
                'signature': signature,
                'ncv': ncv,
                'packages': packages,
            },
            success: function (result) {
                $('.carriers-list-carriers div').not('.default-carrier').remove();
                let data = $.parseJSON(result);
                let html = '';

                if (data === false) {
                    html = '<div class="carrier-loader">' + wc_var.carriers_error + '</div>';
                } else if (data === "POSTALCODE") {
                    html = '<div class="carrier-loader">' + wc_var.postal_error + '</div>';
                } else if (data === "DIMENSIONS") {
                    html = '<div class="carrier-loader">' + wc_var.dimensions_error + '</div>';
                } else {
                    // Carriers
                    $.each(data, function (c_code, carrier) {
                        $.each(carrier['services'], function (code, service) {
                            let displayCost = "displayCost" in service ? service['displayCost'] : service['cost'];
                            html += '<div class="carriers-list-carrier">\
                                                <input type="radio" id="' + c_code + '%' + code + '" name="carrier" value="' + c_code + '%' + code + '">\
                                                    <label for="' + c_code + '%' + code + '">\
                                                        <span>' + c_code + '</span>\
                                                        <span>' + code + '</span>\
                                                        <span>' + service['cost'] + '</span>\
                                                        <span>' + displayCost + '</span>\
                                                    </label>\
                                            </div>';
                        });
                    });
                }

                $('.carriers-list-carriers').append(html);
            },
            error: function (error) {
                $('.carriers-list-carriers div').not('.default-carrier').remove();
                let html = '<div class="carrier-loader">' + wc_var.carriers_error + '</div>';
                $('.carriers-list-carriers').append(html);
            }
        });
    });

    /**
     * Send to Ship Discounts.
     */
    $('#btn-lar-send-order').on('click', function (e) {
        e.preventDefault();
        $(document.body).css({'cursor': 'progress'});
        $('#lar-error-msg').hide();

        let signature = $('#signature').is(':checked') ? 1 : 0;
        let ncv = $('#ncv').is(':checked') ? 1 : 0;
        let cost_boxes = $('#cost-boxes').val();
        let shipping_note = $('#shipping-note').val();
        let packages = getPackages();

        let carrier_code = 'default';
        let service_code = 'default';
        let cost = 'default';
        let display_cost = 'default';

        let val = $('.carriers-list-carrier input[name=carrier]:checked').val();
        if (val != null && val !== 'default') {
            let codes = val.split('%');
            if (codes.length > 0) {
                carrier_code = codes[0];
                service_code = codes[1];

                let count = 0;
                $('label[for="' + val + '"] span').each(function () {
                    if (count === 3)
                        cost = $(this).text().replace(/[^0-9.,]/g, '');
                    if (count === 5)
                        display_cost = $(this).text().replace(/[^0-9.,]/g, '');
                    count++;
                });
            }
        } else if (val == null) {
            carrier_code = '';
            service_code = '';
            cost = '';
            display_cost = '';
        }

        $.ajax({
            type: 'POST',
            url: ajax_var.url,
            data: {
                'action': 'sd_lar_resend_order',
                'nonce': ajax_var.nonce,
                'order': wc_var.wc_order_id,
                'signature': signature,
                'ncv': ncv,
                'packages': packages,
                'cost_boxes': cost_boxes,
                'carrier_code': carrier_code,
                'service_code': service_code,
                'cost': cost,
                'display_cost': display_cost,
                'shipping_note': shipping_note,
            },
            success: function (result) {
                $(document.body).css({'cursor': 'default'});
                let data = $.parseJSON(result);

                if (data === true)
                    location.reload();
                else {
                    $('#lar-error-msg').show();
                    if (data === 'DELETE') {
                        $('#lar-error-msg').html("<strong>"+wc_var.delete_error+"</strong>");
                    }
                    else if (data === 'PHONE-deliverTo')
                        $('#lar-error-msg').html("<strong>"+wc_var.phone_deliverTo_error+"</strong>");
                    else if (data === 'PHONE-soldTo')
                        $('#lar-error-msg').html("<strong>"+wc_var.phone_soldTo_error+"</strong>");
                    else if (data === 'PHONE-shipfrom')
                        $('#lar-error-msg').html("<strong>"+wc_var.phone_shipfrom_error+"</strong>");
                    else if (data === 'MISSING')
                        $('#lar-error-msg').html("<strong>"+wc_var.missing_error+"</strong>");
                    else if (data === 'NO_PACKAGES')
                        $('#lar-error-msg').html("<strong>"+wc_var.no_packages_error+"</strong>");
                    else if (data === 'PACKAGES')
                        $('#lar-error-msg').html("<strong>"+wc_var.packages_error+"</strong>");
                    else
                        $('#lar-error-msg').html("<strong>"+wc_var.order_error+"</strong>");
                }
            },
            error: function (error) {
                $(document.body).css({'cursor': 'default'});
                $('#lar-error-msg').show();
                $('#lar-error-msg').html("<strong>"+wc_var.order_error+"</strong>");
            }
        });
    });

    /**
     * Check if a value is numeric.
     * @param n Value.
     * @returns {boolean} If the value is numeric.
     */
    function isNumeric(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

    /**
     * Get the packages from the table.
     * @returns {*[]} Packages.
     */
    function getPackages() {
        let packages = [];
        let i = 0;

        $('.lar_boxes_table tbody tr').each(function () {
            let length = $(this).find('input[name^=packages_length]').val();
            let width = $(this).find('input[name^=packages_width]').val();
            let height = $(this).find('input[name^=packages_height]').val();
            let weight = $(this).find('input[name^=packages_weight]').val();

            if (!isNumeric(length)) length = 0;
            if (!isNumeric(width)) width = 0;
            if (!isNumeric(height)) height = 0;
            if (!isNumeric(weight)) weight = 0;

            packages[i] = {
                'length': length,
                'width': width,
                'height': height,
                'weight': weight,
            };
            i++;
        });

        return packages;
    }

    /**
     * Cancel an order.
     */
    $("#btn-lar-cancel-orders").on("click", function (e) {
        e.preventDefault();
        $(document.body).css({'cursor': 'progress'});

        $.ajax({
            type: 'POST',
            url: ajax_var.url,
            data: {
                'action': 'sd_lar_cancel_orders',
                'nonce': ajax_var.nonce,
                'order': wc_var.wc_order_id,
            },
            success: function (result) {
                $(document.body).css({'cursor': 'default'});
                let data = $.parseJSON(result);
                let button = $('#btn-lar-cancel-orders');
                let text = button.prev('.lar_danger_message');

                if (data !== false) {
                    if (data.length === 0) {
                        text.remove();
                        button.remove();
                    } else {
                        let html = "<strong>"+wc_var.cancel_error+"</strong><br>";
                        $.each(data, function (i, v) {
                            html += '#' + v + '<br>';
                        });
                        text.html(html);
                    }
                } else {
                    text.find('strong').html(wc_var.cancel_error);
                }
            },
            error: function (error) {
                $(document.body).css({'cursor': 'default'});
                $('#btn-lar-cancel-orders').prev('.lar_danger_message').find('strong').html(wc_var.cancel_error);
            }
        });
    });
});