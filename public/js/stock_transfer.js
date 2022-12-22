 Dropzone.autoDiscover = false;
$(document).ready(function () {
    //Add products
    if ($('#search_product_for_srock_adjustment').length > 0 && !$('#stock_transfer_csv').val()) {
        //Add Product
        $('#search_product_for_srock_adjustment')
            .autocomplete({
                source: function (request, response) {
                    $.getJSON(
                        '/products/list',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function (event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function (event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function (event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        stock_transfer_product_row(ui.item.variation_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function (ul, item) {
                if (item.qty_available <= 0) {
                    var string = '<li class="ui-state-disabled">' + item.name;
                    if (item.type == 'variable') {
                        string += '-' + item.variation;
                    }
                    string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                    return $(string).appendTo(ul);
                } else if (item.enable_stock != 1) {
                    return ul;
                } else {
                    var string = '<div>' + item.name;
                    if (item.type == 'variable') {
                        string += '-' + item.variation;
                    }
                    string += ' (' + item.sub_sku + ') </div>';
                    return $('<li>')
                        .append(string)
                        .appendTo(ul);
                }
            };
    }

    $('select#location_id').change(function () {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
            $('#stock_transfer_csv').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
            $('#stock_transfer_csv').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    $(document).on('change', 'input.product_quantity', function () {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function () {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function () {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    jQuery.validator.addMethod(
        'notEqual',
        function (value, element, param) {
            return this.optional(element) || value != param;
        },
        'Please select different location'
    );

    $('form#stock_transfer_form').validate(/*{
        rules: {
            transfer_location_id: {
                notEqual: function () {
                    return $('select#location_id').val();
                },
            },
        },
    }*/);

    // $('#save_stock_transfer').click(function (e) {
    //     e.preventDefault();

    //     if ($('table#stock_adjustment_product_table tbody').find('.product_row').length <= 0 && !$('#stock_transfer_csv').val()) {
    //         toastr.warning(LANG.no_products_added);
    //         console.log('Please select a stock adjustments product');
    //         return false;
    //     }
    //     if ($('form#stock_transfer_form').valid()) {
    //         $('form#stock_transfer_form').submit();
    //     } else {
    //         return false;
    //     }
    // });

    //Date range as a button
    $('#stock_transfer_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#stock_transfer_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            stock_transfer_table.ajax.reload();
        }
    );
    $('#stock_transfer_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#stock_transfer_list_filter_date_range').val('');
        stock_transfer_table.ajax.reload();
    });

    stock_transfer_table = $('#stock_transfer_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        // ajax: '/stock-transfers',
        "ajax": {
            "url": "/stock-transfers",
            "data": function ( d ) {
                if($('#stock_transfer_list_filter_date_range').val()) {
                    var start = $('#stock_transfer_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#stock_transfer_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.location_from = $('#location_from').val();
                d.location_to = $('#location_to').val();
                d.filter_status = $('#filter_status').val();

                d = __datatable_ajax_callback(d);
            }
        },
        columnDefs: [
            {
                targets: 8,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_from', name: 'l1.name' },
            { data: 'location_to', name: 'l2.name' },
            { data: 'status', name: 'status' },
            { data: 'shipping_charges', name: 'shipping_charges' },
            { data: 'final_total', name: 'final_total' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function (oSettings) {
            __currency_convert_recursively($('#stock_transfer_table'));
        },
    });

    $(document).on('change', '#location_from, #location_to, #filter_status',  function() {
        stock_transfer_table.ajax.reload();
    });

    var detailRows = [];

    $('#stock_transfer_table tbody').on('click', '.view_stock_transfer', function () {
        var tr = $(this).closest('tr');
        var row = stock_transfer_table.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);

        if (row.child.isShown()) {
            $(this)
                .find('i')
                .removeClass('fa-eye')
                .addClass('fa-eye-slash');
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice(idx, 1);
        } else {
            $(this)
                .find('i')
                .removeClass('fa-eye-slash')
                .addClass('fa-eye');

            row.child(get_stock_transfer_details(row.data())).show();

            // Add to the 'open' array
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `detailRows` array and show any child rows
    stock_transfer_table.on('draw', function () {
        $.each(detailRows, function (i, id) {
            $('#' + id + ' .view_stock_transfer').trigger('click');
        });
    });

  // Import stock transfer products
    if ($("div#import_transfer_product_dz").length) {
        $("div#import_transfer_product_dz").dropzone({
            url: base_path + '/import-stock-transfer-products',
            paramName: 'stock_transfer_csv',
            autoProcessQueue: false,
            addRemoveLinks: true,
            uploadMultiple: false,
            maxFiles:1,
            init: function() {
                this.on("addedfile", function(file) {
                    $('#file_import_error').html('');
                    if ($('#location_id').val() === '') {
                        this.removeFile(file);
                        toastr.error('select location first');
                    }
                });
                this.on("maxfilesexceeded", function(file) {
                    this.removeAllFiles();
                    this.addFile(file);
                });
                this.on("sending", function(file, xhr, formData){
                    formData.append("location_id", $('#location_id').val());
                    formData.append("row_count", $('#product_row_index').val());
                    toastr.info('Please wait while processing');
                });
            },   
            acceptedFiles: '.csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(file, response) {
                if(response.success == 0) {
                    var err = `
                    <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <strong><span>Error :- </span></strong>
                    <ul>`
                    $.each(response.errorArr, function( index, value ) {
                        err += `<li>${value}</li>`;
                    });
                    err += `</ul></div>`;
                    this.removeAllFiles();
                    $('#file_import_error').html(err);
                }else{
                    $('table#stock_adjustment_product_table tbody').append(response.html);
                    $('#product_row_index').val($('table#stock_adjustment_product_table tbody').find('tr').length);
                    $('#import_stock_transfer_products_modal').modal('hide');
                    update_table_total();
                    this.removeAllFiles();

                }
            },
        });
    }
   
    $(document).on('click', '#import_stock_transfer_products', function(){
        var productDz = Dropzone.forElement("#import_transfer_product_dz");
        productDz.processQueue();
    })
    
    //Delete Stock Transfer
    $(document).on('click', 'button.delete_stock_transfer', function () {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function (result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_transfer_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

function addSerialNumber(table) {
    $(table).each(function(index) {
        $(this).find('td:nth-child(1)').html(index+1);
    });
};

function stock_transfer_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id, type: 'stock_transfer' },
        dataType: 'html',
        success: function (result) {
            $('table#stock_adjustment_product_table tbody').append(result);
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
    });
}

function update_table_total() {
    var table_total = 0;
    var total_total_qty = 0;

    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        var this_total_qty = parseFloat(__read_number($(this).find('input.product_quantity')));
        if (this_total) {
            table_total += this_total;
        }
        if (this_total_qty) {
            total_total_qty += this_total_qty;
        }
    });
    addSerialNumber('table#stock_adjustment_product_table tbody tr');

    $('span#total_adjustment').text(__number_f(table_total));
    $('span#total_qty').text(__number_f(total_total_qty));

    if ($('input#shipping_charges').length) {
        var shipping_charges = __read_number($('input#shipping_charges'));
        table_total += shipping_charges;
    }

    $('span#final_total_text').text(__number_f(table_total));
    $('input#total_amount').val(table_total);
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

function get_stock_transfer_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    $.ajax({
        url: '/stock-transfers/' + rowData.DT_RowId,
        dataType: 'html',
        success: function (data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}

$(document).on('click', 'a.stock_transfer_status', function (e) {
    e.preventDefault();
    var href = $(this).data('href');
    var status = $(this).data('status');
    $('#update_stock_transfer_status_modal').modal('show');
    $('#update_stock_transfer_status_form').attr('action', href);
    $('#update_stock_transfer_status_form #update_status').val(status);
    $('#update_stock_transfer_status_form #update_status').trigger('change');
});

$(document).on('submit', '#update_stock_transfer_status_form', function (e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'post',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function (xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function (result) {
            if (result.success == true) {
                $('div#update_stock_transfer_status_modal').modal('hide');
                toastr.success(result.msg);
                stock_transfer_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
            $('#update_stock_transfer_status_form')
                .find('button[type="submit"]')
                .attr('disabled', false);
        },
    });
});
$(document).on('shown.bs.modal', '.view_modal', function () {
    __currency_convert_recursively($('.view_modal'));
});
