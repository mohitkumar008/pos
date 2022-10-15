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
    @component('components.widget', ['title' => $product->name])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
        @if($product->type == 'variable')
            <div class="col-md-3">
                <div class="form-group">
                    <label for="variation_id">@lang('product.variations'):</label>
                    <select class="select2 form-control" name="variation_id" id="variation_id">
                        @foreach($product->variations as $variation)
                            <option value="{{$variation->id}}">{{$variation->product_variation->name}} - {{$variation->name}} ({{$variation->sub_sku}})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <input type="hidden" id="variation_id" name="variation_id" value="{{$product->variations->first()->id}}">
        @endif
    @endcomponent
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
            load_stock_history($('#variation_id').val(), $('#location_id').val());
        });

       function load_stock_history(variation_id, location_id) {
            $('#product_stock_history').fadeOut();
            $.ajax({
                url: '/products/stock-history/' + variation_id + "?location_id=" + location_id,
                dataType: 'html',
                success: function(result) {
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

       $(document).on('change', '#variation_id, #location_id', function(){
            load_stock_history($('#variation_id').val(), $('#location_id').val());
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