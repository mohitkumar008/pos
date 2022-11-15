@extends('layouts.app')
@section('title', __('lang_v1.product_stock_history'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.product_stock_history')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
                   </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'location_id',]); !!}
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <div class="form-group">
                                {!! Form::label('search_product',  __('lang_v1.search_product') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </span>
                                    {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_stock_history', 'placeholder' => __('report.search_product')]); !!}
                                </div>
                            </div>
                        </div>
                        <input type="hidden" value="" id="product_id">
                        <input type="hidden" value="" id="variation_id">
                    </div>
                </div>
            </div>
            @component('components.widget')
            <div id="product_stock_history" style="display: none;"></div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
@endsection

@section('javascript')
   <script type="text/javascript">
        $(document).ready( function(){
            //Add products
            if ($('#search_product_for_stock_history').length > 0) {
                //Add Product
                $('#search_product_for_stock_history')
                    .autocomplete({
                        source: function(request, response) {
                            $.getJSON(
                                '/products/list',
                                { location_id: $('#location_id').val(), term: request.term },
                                response
                            );
                        },
                        minLength: 2,
                        response: function(event, ui) {
                            if (ui.content.length == 1) {
                                $(this)
                                    .data('ui-autocomplete')
                                    ._trigger('select', 'autocompleteselect', ui);
                                $(this).autocomplete('close');
                            } else if (ui.content.length == 0) {
                                swal(LANG.no_products_found);
                            }
                        },
                        select: function(event, ui) {
                            $('#product_id').val(ui.item.product_id);
                            $('#variation_id').val(ui.item.variation_id);
                            load_stock_history($('#product_id').val(),$('#variation_id').val(), $('#location_id').val());
                        },
                    })
                    .autocomplete('instance')._renderItem = function(ul, item) {
                        var string = '<div>' + item.name;
                        if (item.type == 'variable') {
                            string += '-' + item.variation;
                        }
                        string += ' (' + item.sub_sku + ') </div>';
                        return $('<li>')
                            .append(string)
                            .appendTo(ul);
                };
            }
        });

       function load_stock_history(product_id, variation_id, location_id) {
            $('#product_stock_history').fadeOut();
            if(product_id != "" && variation_id != "" && location_id != ""){
                $.ajax({
                    url: `/products/stock-history-details?location_id=${location_id}&product_id=${product_id}&variation_id=${variation_id}`,
                    dataType: 'html',
                    success: function(result) {
                        console.log(result)
                        $('#product_stock_history')
                            .html(result)
                            .fadeIn();
    
                        __currency_convert_recursively($('#product_stock_history'));
    
                        $('#stock_history_table').DataTable({
                            searching: false,
                            ordering: false
                        });
                    },
                });
            }
       }

       $(document).on('change', '#variation_id, #filter_variation_id, #location_id', function(){
            $('#variation_id').val($('#filter_variation_id').val());
            load_stock_history($('#product_id').val(), $('#variation_id').val(), $('#location_id').val());
       });
       
      var current_stock_html = ''
      $(document).on('dblclick', '#current_stock', function(){
            current_stock_html = $(this).html()
            $(this).html('')
            var new_quantity = $("#new_quantity_0").text()
            $(this).html('<input type="text" name="current_qty" id="current_qty" disabled value="'+new_quantity+'">')
            $("#current_stock_input").html('<button class="btn btn-sm btn-primary update_current_stock">Save</button><button class="btn btn-sm btn-secondary cancel_current_stock">Cancel</button>')

      });
      
      $(document).on('click', '.cancel_current_stock', function(){
            $("#current_stock").html(current_stock_html)
            $("#current_stock_input").html('')
      });
      
      $(document).on('click', '.update_current_stock', function(){
          
          var variation_id =  $('#variation_id').val();
          var location_id = $('#location_id').val();
          var current_qty = $('#current_qty').val();

          $.ajax({
            url: '/products/update-location-quantity/' + variation_id + "?location_id=" + location_id,
            type: 'POST',
            data:{
                "current_qty":current_qty
            },
            success: function(result) {
                if(result.success){
                    current_stock_html = current_qty
                    $("#current_stock_input").html('')
                    $("#current_stock").html(current_stock_html)
                    toastr.success("Updated Successfuly");
                    
                }else {
                    toastr.error(data.msg);
                }
            },
            error:function(result){
                console.log(result)
                toastr.error(result.responseJSON.message);
            }
        });
          
      });
   </script>
@endsection