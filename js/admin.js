jQuery(function ($) {
    // Element to observe
    let lar_elem_id = 'woocommerce_sd_lar_method_predefined_package';

    // Show/hide elements
    lar_toggle_dimensions($('input.lar_predefined_package_checkbox').is(':checked'));
    lar_toggle_boxes($('input.lar_settings_use_boxes_checkbox').is(':checked'));

    // Set event handlers
    lar_event_handlers();

    // When the element is added
    let observer_add = new MutationObserver(function (mutations) {
        if (document.getElementById(lar_elem_id)) {
            // Show/hide elements
            lar_toggle_dimensions($('input.lar_predefined_package_checkbox').is(':checked'));

            // Set event handlers
            lar_event_handlers();

            // Switch observation
            observer_add.disconnect();
            observer_remove.observe(document, {
                attributes: false,
                childList: true,
                characterData: false,
                subtree: true
            });
        }
    });

    // When the element is removed
    let observer_remove = new MutationObserver(function (mutations) {
        if (!document.getElementById(lar_elem_id)) {
            // Switch observation
            observer_remove.disconnect();
            observer_add.observe(document, {attributes: false, childList: true, characterData: false, subtree: true});
        }
    });

    // Which event to observe
    if (!document.getElementById(lar_elem_id))
        observer_add.observe(document, {attributes: false, childList: true, characterData: false, subtree: true});
    else
        observer_remove.observe(document, {attributes: false, childList: true, characterData: false, subtree: true});

    /**
     * Set event handlers.
     */
    function lar_event_handlers() {
        // Event handler for displaying or hiding the dimensions
        $('input.lar_predefined_package_checkbox').on('change', function () {
            lar_toggle_dimensions($(this).is(':checked'));
        });

        // Event handler for displaying or hiding the boxes
        $('input.lar_settings_use_boxes_checkbox').on('change', function () {
            lar_toggle_boxes($(this).is(':checked'));
        });

        // Event handler for displaying or hiding the lists
        let lar_classes_list_type = $('select.lar_classes_list_type');
        lar_toggle_lists(lar_classes_list_type.find("option:selected").val());

        lar_classes_list_type.on('change', function () {
            lar_toggle_lists($(this).find("option:selected").val());
        });

        // Add checkboxes next to classes
        lar_add_checkboxes($('select.lar_classes_list_allow'));
        lar_add_checkboxes($('select.lar_classes_list_deny'));

        // Event handler for selecting classes
        $('select.lar_classes_list option').on('mousedown', function (e) {
            e.preventDefault();
            lar_multiple_select($(this).closest('select.lar_classes_list'), $(this));
        });
    }

    /**
     * Show or hide the class lists.
     * @param type Type of list to show
     */
    function lar_toggle_lists(type) {
        // Lists in the modal
        let lists_modal = $('select.lar_classes_list_hide_modal').closest('fieldset');
        let allowlist_modal = $('select.lar_classes_list_allow_hide_modal').closest('fieldset');
        let denylist_modal = $('select.lar_classes_list_deny_hide_modal').closest('fieldset');

        // Lists in the settings page
        let lists_page = $('select.lar_classes_list_hide_page').closest('tr');
        let allowlist_page = $('select.lar_classes_list_allow_hide_page').closest('tr');
        let denylist_page = $('select.lar_classes_list_deny_hide_page').closest('tr');

        if (type === "1" || type === 1) {
            allowlist_modal.show()
            allowlist_page.show()

            denylist_modal.hide()
            denylist_page.hide()
        } else if (type === "2" || type === 2) {
            allowlist_modal.hide()
            allowlist_page.hide()

            denylist_modal.show()
            denylist_page.show()
        } else {
            lists_modal.hide();
            lists_page.hide();
        }
    }

    /**
     * Show or hide the dimensions.
     */
    function lar_toggle_dimensions(checked) {
        let modal = $('input.lar_predefined_package_dimensions_modal').closest('fieldset');
        let page = $('input.lar_predefined_package_dimensions_page').closest('tr');

        if (checked) {
            modal.show();
            lar_show_dimensions_labels(true);
            page.show();
        } else {
            modal.hide();
            lar_show_dimensions_labels(false);
            page.hide();
        }
    }

    /**
     * Show or hide the dimensions' labels in the modal.
     * @param show If the labels need to be shown.
     */
    function lar_show_dimensions_labels(show) {
        $('input.lar_predefined_package_dimensions_modal').each(function () {
            let label = $('label[for="' + $(this).attr('id') + '"]');
            if (show)
                label.show();
            else
                label.hide();
        });
    }

    /**
     * Allowing multiselect with mouse click.
     * @param select Class list
     * @param option Selected class
     */
    function lar_multiple_select(select, option) {
        let choices = select.val();

        if (choices.includes(option.val())) {
            option.find('input[type=checkbox]').prop('checked', false);
            choices = choices.filter(function (e) {
                return e !== option.val()
            });
        } else {
            option.find('input[type=checkbox]').prop('checked', true);
            choices.push(option.val());
        }

        select.val(choices);
    }

    /**
     * Add checkboxes next to classes.
     * @param select Class list
     */
    function lar_add_checkboxes(select) {
        let choices = select.val();

        select.find('option').each(function () {
            let checked = choices.includes($(this).val()) ? ' checked' : '';
            $(this).html('<input type="checkbox"' + checked + '> ' + $(this).text());
        });
    }

    /**
     * Get shipping classes before adding a row.
     */
    $('.lar_boxes_table .lar_boxes_insert').on('click', function (e) {
        e.preventDefault();
        $(document.body).css({'cursor': 'progress'});
        $.ajax({
            url: ajax_var.url,
            type: 'post',
            data: {
                action: 'sd_lar_get_shipping_classes',
                nonce: ajax_var.nonce,
            },
            success(data) {
                lar_add_new_row(data.data);
                $(document.body).css({'cursor': 'default'});
            },
            error() {
                lar_add_new_row();
                $(document.body).css({'cursor': 'default'});
            },
        });
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

        // Rearrange indexes
        $('table.lar_boxes_table input').each(function () {
            if ($(this).attr('name'))
                $(this).attr('name', $(this).attr('name').replace(/\[\d+\]/g, "\[" + $(this).closest('tr').index() + "\]"));
        });
        $('table.lar_boxes_table select').each(function () {
            if ($(this).attr('name'))
                $(this).attr('name', $(this).attr('name').replace(/\[\d+\]/g, "\[" + $(this).closest('tr').index() + "\]"));
        });
    });

    /**
     * Add a new row in the boxes table.
     * @param options Shipping classes.
     */
    function lar_add_new_row(options = '') {
        $('.lar_boxes_table thead input[type="checkbox"]').prop('checked', false);

        options = '<option value="">N/A</option>' + options;
        let tbody = $('.lar_boxes_table').find('tbody');
        let size = tbody.find('tr').size();
        let code = '<tr class="new">\
                                    <td class="sort">&#9776;</td>\
									<td class="check-column"><span><input type="checkbox" /></span></td>\
									<td><span><input type="text" name="sd_lar_boxes_name[' + size + ']" /></span></td>\
									<td><span><input type="text" name="sd_lar_boxes_outer_length[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_outer_width[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_outer_height[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_inner_length[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_inner_width[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_inner_height[' + size + ']" />in</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_box_weight[' + size + ']" />lbs</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_max_weight[' + size + ']" />lbs</span></td>\
									<td><span><input type="text" name="sd_lar_boxes_price[' + size + ']" /></span></td>\
									<td><span><select name="sd_lar_boxes_class[' + size + ']">' + options + '</select></span></td>\
								</tr>';
        tbody.append(code);
    }

    /**
     * Show or hide the boxes.
     */
    function lar_toggle_boxes(checked) {
        if (checked)
            $('table.lar_boxes_table').show();
        else
            $('table.lar_boxes_table').hide();
    }

    // Sort the boxes table with drag and drop.
    $("table.lar_boxes_table tbody").sortable({
        handle: '.sort',
        cursor: 'grabbing',
        stop: function () {
            $('table.lar_boxes_table input').each(function () {
                if ($(this).attr('name'))
                    $(this).attr('name', $(this).attr('name').replace(/\[\d+\]/g, "\[" + $(this).closest('tr').index() + "\]"));
            });
            $('table.lar_boxes_table select').each(function () {
                if ($(this).attr('name'))
                    $(this).attr('name', $(this).attr('name').replace(/\[\d+\]/g, "\[" + $(this).closest('tr').index() + "\]"));
            });
        }
    }).disableSelection();
});


