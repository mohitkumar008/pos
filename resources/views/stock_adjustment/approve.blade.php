@extends('layouts.app')
@section('title', __('stock_adjustment.stock_adjustments'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('stock_adjustment.stock_adjustments')</h1>
</section>
<!-- Main content -->
<section class="content no-print">
	@if (session('notification') || !empty($notification))
		<div class="row">
			<div class="col-sm-12">
				<div class="alert alert-danger alert-dismissible">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
					@if(!empty($notification['msg']))
						{{$notification['msg']}}
					@elseif(session('notification.msg'))
						{{ session('notification.msg') }}
					@endif
				</div>
			</div>  
		</div>     
	@endif
	<div class="box box-solid">
		<div class="box-body">
			@include('stock_adjustment.partials.invoice_info')
		</div>
	</div>
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-12">
					<form action="{{ route('stock_adjustment.update_status', ['type'=>'approve']) }}" method="post">
						@csrf
					<table class="table bg-gray">
			          	<thead>
				            <tr class="bg-green">
				              	<th>S. No.</th>
				              	<th>@lang('product.product_name')</th>
				              	<th>{{ __('lang_v1.requested_qty') }}</th>
								<th>{{ __('lang_v1.approved_qty') }}</th>
				            </tr>
				        </thead>
						
				        <tbody>
							@foreach($stock_adjustment->stock_adjustment_lines as $stock_adjustment_line)
							<tr>
								<td>{{$loop->iteration}}</td>
								<td>{{ $stock_adjustment_line->variation->full_name }}</td>
								<td>{{@format_quantity($stock_adjustment_line->request_qty)}}</td>
								<td>
									<input type="text" name="stock_adjustment[{{$loop->index}}][approve_qty]" id="approve_qty" required>
									<input type="hidden" name="stock_adjustment[{{$loop->index}}][request_qty]" id ="request_qty" value="{{round($stock_adjustment_line->request_qty)}}">
									<input type="hidden" name="stock_adjustment[{{$loop->index}}][id]" value="{{$stock_adjustment_line->id}}">
								</td>
							</tr>
							@endforeach
							<input type="hidden" name="transaction_id" value="{{$stock_adjustment->id}}">
			          	</tbody>
			        </table>
					<button type="submit" id="button1" class="btn btn-primary pull-right">@lang('messages.approve')</button>
				</form>
					
				</div>
			</div>
			<br>
			
		</div>
	</div>

</section>
@stop
@section('javascript')
<script>
$(document).on('keyup', '#approve_qty', function(e){
	$(e.target).parent().find('.error').html('')
	var approve_qty = $(this).val();
	var request_qty = Math.round($('#request_qty').val());
	if(approve_qty > request_qty){
		document.getElementById("button1").disabled = true;
		$(this).popover({ trigger: 'focus', content: "Quantity should not be greater than requested qty" })
		$(this).popover('show');
	}else{
		$(this).popover('hide');
		document.getElementById("button1").disabled = false;
	}
});


</script>
@endsection
