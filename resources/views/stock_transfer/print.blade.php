@php
$delivery_challan='';
if($print_format == 'delivery-challan'){
  $delivery_challan='Delivery Challan';
}
elseif($print_format == 'tax-invoice'){
  $delivery_challan='Tax Invoice';
}
// if(!empty($location_details['sell']->state) && !empty($location_details['purchase']->state) && $location_details['sell']->state == $location_details['purchase']->state){
//     $delivery_challan='Delivery Challan';
// }
@endphp

<div class="row">
  <div class="col-xs-12">
    <h2 class="page-header">
      {{$delivery_challan}}
      <small class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell_transfer->transaction_date) }}</small>
    </h2>
  </div>
</div>
<div class="row ">
  <div class="col-xs-8">
    <address>
      <strong>{{ $location_details['sell']->name }}</strong>
      
      @if(!empty($location_details['sell']->landmark))
        <br>{{$location_details['sell']->landmark}}
      @endif

      @if(!empty($location_details['sell']->city) || !empty($location_details['sell']->state) || !empty($location_details['sell']->country))
        <br>{{implode(',', array_filter([$location_details['sell']->city, $location_details['sell']->state, $location_details['sell']->country]))}}
      @endif

      @if(!empty($sell_transfer->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$sell_transfer->contact->tax_number}}
      @endif

      @if(!empty($location_details['sell']->mobile))
        <br>@lang('contact.mobile'): {{$location_details['sell']->mobile}}
      @endif
      @if(!empty($location_details['sell']->email))
        <br>Email: {{$location_details['sell']->email}}
      @endif
      
      @if(!empty($location_details['sell']->custom_field1))
        <br>{{$location_details['sell']->custom_field1}}
      @endif
    </address>
  </div>
  <div class="col-xs-4 text-right">
    <b>@lang('Bill No'):</b> #{{ $sell_transfer->ref_no }}<br/>
    <b>@lang('messages.date'):</b> {{ @format_date($sell_transfer->transaction_date) }}<br/>
  </div>
</div>
 <hr>
<div class="row invoice-info">
  <div class="col-xs-6 ">
    @lang('Bill To'):
    <address>
      <strong>{{ $location_details['purchase']->name }}</strong>
      
      @if(!empty($location_details['purchase']->landmark))
        <br>{{$location_details['purchase']->landmark}}
      @endif

      @if(!empty($location_details['purchase']->city) || !empty($location_details['purchase']->state) || !empty($location_details['purchase']->country))
        <br>{{implode(',', array_filter([$location_details['purchase']->city, $location_details['purchase']->state, $location_details['purchase']->country]))}}
      @endif

      @if(!empty($sell_transfer->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$sell_transfer->contact->tax_number}}
      @endif

      @if(!empty($location_details['purchase']->mobile))
        <br>@lang('contact.mobile'): {{$location_details['purchase']->mobile}}
      @endif
      @if(!empty($location_details['purchase']->email))
        <br>Email: {{$location_details['purchase']->email}}
      @endif
      
      @if(!empty($location_details['purchase']->custom_field1))
        <br>{{$location_details['purchase']->custom_field1}}
      @endif
    </address>
  </div>
 
  <div class="col-xs-6 text-right">
    @lang('Ship To'):
    <address>
      <strong>{{ $location_details['purchase']->name }}</strong>
      
      @if(!empty($location_details['purchase']->landmark))
        <br>{{$location_details['purchase']->landmark}}
      @endif

      @if(!empty($location_details['purchase']->city) || !empty($location_details['purchase']->state) || !empty($location_details['purchase']->country))
        <br>{{implode(',', array_filter([$location_details['purchase']->city, $location_details['purchase']->state, $location_details['purchase']->country]))}}
      @endif

      @if(!empty($sell_transfer->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$sell_transfer->contact->tax_number}}
      @endif

      @if(!empty($location_details['purchase']->mobile))
        <br>@lang('contact.mobile'): {{$location_details['purchase']->mobile}}
      @endif
      @if(!empty($location_details['purchase']->email))
        <br>Email: {{$location_details['purchase']->email}}
      @endif
      @if(!empty($location_details['purchase']->custom_field1))
        <br>{{$location_details['purchase']->custom_field1}}
      @endif
    </address>
  </div>
</div>
<hr>
<br>
<div class="row">
  <div class="col-xs-12">
    <div class="table-responsive">
      <table class="table bg-gray table-bordered">
        <tr class="bg-green">
          <th>#</th>
          <th>@lang('sale.product')</th>
          @if ($print_format == 'tax-invoice')
            <th>@lang('HSN/SAC')</th>
          @endif
          <th>@lang('sale.qty')</th>
          <th>@lang('sale.unit')</th>
          <th>@lang('sale.unit_price')</th>
          @if ($print_format == 'tax-invoice')
            <th>Tax Rate(%)</th>
            <th>@lang('GST')</th>
          @endif
          <th>@lang('sale.subtotal')</th>
        </tr>
        @php 
          $total = 0.00;
          $total_gst = 0.00;
        @endphp
        @foreach($sell_transfer->sell_lines as $sell_lines)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
              {{ $sell_lines->product->name }}
               @if( $sell_lines->product->type == 'variable')
                - {{ $sell_lines->variations->product_variation->name}}
                - {{ $sell_lines->variations->name}}
               @endif
               @if($lot_n_exp_enabled && !empty($sell_lines->lot_details))
                <br>
                <strong>@lang('lang_v1.lot_n_expiry'):</strong> 
                @if(!empty($sell_lines->lot_details->lot_number))
                  {{$sell_lines->lot_details->lot_number}}
                @endif
                @if(!empty($sell_lines->lot_details->exp_date))
                  - {{@format_date($sell_lines->lot_details->exp_date)}}
                @endif
               @endif
            </td>
            @if ($print_format == 'tax-invoice')
              <td>{{ $sell_lines->product->product_tax?$sell_lines->product->product_tax->name:"" }}</td>
            @endif
            <td>{{ @format_quantity($sell_lines->quantity) }}</td>
            <td>{{ $sell_lines->product->unit->short_name ?? ""}}</td>
            <td>{{ $sell_lines->unit_price_inc_tax}}</td>
            @if ($print_format == 'tax-invoice')
              <td>{{ $sell_lines->product->product_tax ? $sell_lines->product->product_tax->amount : ""}}</td>
              <td>{{ $sell_lines->item_tax * $sell_lines->quantity}}</td>
            @endif
            <td>
              <span class="display_currency" data-currency_symbol="true">{{ ($sell_lines->unit_price_inc_tax + $sell_lines->item_tax) * $sell_lines->quantity }}</span>
            </td>
          </tr>
          @php 
            $total += ($sell_lines->unit_price_inc_tax * $sell_lines->quantity);
            $total_gst += $sell_lines->item_tax * $sell_lines->quantity;
          @endphp
        @endforeach
      </table>
    </div>
  </div>
</div>
<br>
<div class="row">
  
  <div class="col-xs-6">
    <div class="table-responsive">
      <table class="table">
        <tr>
          <th>@lang('purchase.net_total_amount'): </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total }}</span></td>
        </tr>
        <tr>
          <th>Total GST: </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_gst }}</span></td>
        </tr>
        @if( !empty( $sell_transfer->shipping_charges ) )
          <tr>
            <th>@lang('purchase.additional_shipping_charges'):</th>
            <td><b>(+)</b></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell_transfer->shipping_charges }}</span></td>
          </tr>
        @endif
        <tr>
          <th>@lang('purchase.purchase_total'):</th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell_transfer->final_total + $total_gst }}</span></td>
        </tr>
      </table>
    </div>
  </div>
</div>
@if ($print_format == 'tax-invoice')
  <div class="row">
    <div class="col-xs-12">
      <div class="table-responsive">
        <table class="table bg-gray table-bordered">
          <tr class="bg-green">
              <th rowspan="2">@lang('HSN/SAC')</th>
              <th rowspan="2">@lang('Taxable amount')</th>
              
              @if(!empty($location_details['sell']->state) && !empty($location_details['purchase']->state) && $location_details['sell']->state == $location_details['purchase']->state)
              <th colspan="2">@lang('CGST')</th>
              <th colspan="2">@lang('SGST')</th>
              @else
              <th colspan="2">@lang('IGST')</th>
              @endif
              <th rowspan="2">@lang('Total Tax Amount')</th>
          </tr>
          
          <tr class="bg-green">
              @if(!empty($location_details['sell']->state) && !empty($location_details['purchase']->state) && $location_details['sell']->state == $location_details['purchase']->state)
              <th >@lang('Rate')</th>
              <th >@lang('Amount')</th>
              <th >@lang('Rate')</th>
              <th >@lang('Amount')</th>
              @else
              <th >@lang('Rate')</th>
              <th >@lang('Amount')</th>
              @endif
              
              
          </tr>
          @foreach($output_taxes['taxes'] as $key => $tax)
          
              <tr class="bg-green">
                  <th >{{$key}}</th>
                  <th >@if(isset($tax['taxable_amount'])) {{$tax['taxable_amount']}} @else - @endif</th>
                  @if(!empty($location_details['sell']->state) && !empty($location_details['purchase']->state) && $location_details['sell']->state == $location_details['purchase']->state)
                  <th >@if(isset($tax['CGST'])) {{$tax['CGST']['amount']}}% @else - @endif</th>
                  <th >@if(isset($tax['CGST'])) {{$tax['CGST']['calculated_tax']}} @else - @endif</th>
                  <th >@if(isset($tax['SGST'])) {{$tax['SGST']['amount']}}% @else - @endif</th>
                  <th >@if(isset($tax['SGST'])) {{$tax['SGST']['calculated_tax']}} @else - @endif</th>
                  @else
                  <th >@if(isset($tax['IGST'])) {{$tax['IGST']['amount']}}% @else - @endif</th>
                  <th >@if(isset($tax['IGST'])) {{$tax['IGST']['calculated_tax']}} @else - @endif</th>
                  @endif
                  <th >@if(isset($tax['total_tax'])) {{$tax['total_tax']}} @else - @endif</th>
              </tr>
          @endforeach
        </table>
      </div>
    </div>
  </div>
@endif
<div class="row">
  <div class="col-sm-6">
    <strong>@lang('purchase.additional_notes'):</strong><br>
    <p class="well well-sm no-shadow bg-gray">
      @if($sell_transfer->additional_notes)
        {{ $sell_transfer->additional_notes }}
      @else
        --
      @endif
    </p>
  </div>
</div>

{{-- Barcode --}}
<div class="row print_section">
  <div class="col-xs-12">
    <img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($sell_transfer->ref_no, 'C128', 2,30,array(39, 48, 54), true)}}">
  </div>
</div>