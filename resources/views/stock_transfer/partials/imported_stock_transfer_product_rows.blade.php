@foreach($formatted_data as $data)
	@include('stock_transfer.partials.product_table_row', [
		'product' => $data['product'],
		'sub_units' => $data['sub_units'],
		'row_index' => $row_count,
	])
	@php
		$row_count++
	@endphp
@endforeach