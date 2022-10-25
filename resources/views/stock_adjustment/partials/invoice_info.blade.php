<div class="row">
  <div class="col-sm-12">
    <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($stock_adjustment->transaction_date) }}</p>
  </div>
</div>
<div class="row invoice-info">
    <div class="col-sm-4 invoice-col">
        @lang('business.business'):
          <address>
        <strong>{{ $stock_adjustment->business->name }}</strong>
        {{ $stock_adjustment->location->name }}
        @if(!empty($stock_adjustment->location->landmark))
          <br>{{$stock_adjustment->location->landmark}}
        @endif
        @if(!empty($stock_adjustment->location->city) || !empty($stock_adjustment->location->state) || !empty($stock_adjustment->location->country))
          <br>{{implode(',', array_filter([$stock_adjustment->location->city, $stock_adjustment->location->state, $stock_adjustment->location->country]))}}
        @endif
        @if(!empty($stock_adjustment->location->mobile))
          <br>@lang('contact.mobile'): {{$stock_adjustment->location->mobile}}
        @endif
        @if(!empty($stock_adjustment->location->email))
          <br>@lang('business.email'): {{$stock_adjustment->location->email}}
        @endif
      </address>
    </div>

    <div class="col-sm-4 invoice-col">
          <b>@lang('purchase.ref_no'):</b> #{{ $stock_adjustment->ref_no }}<br/>
          <b>@lang('messages.date'):</b> {{ @format_date($stock_adjustment->transaction_date) }}<br/>
          <b>@lang('stock_adjustment.adjustment_type'):</b> {{ __('stock_adjustment.' . $stock_adjustment->adjustment_type) }}<br>
          <b>@lang('stock_adjustment.reason_for_stock_adjustment'):</b> {{ $stock_adjustment->additional_notes }}<br>
    </div>
</div>