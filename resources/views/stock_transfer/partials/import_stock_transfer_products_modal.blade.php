<div class="modal fade" tabindex="-1" role="dialog" id="import_stock_transfer_products_modal">
	<div class="modal-dialog modal-lg" role="document">
  		<div class="modal-content">
  			<div class="modal-header">
			    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			    <h4 class="modal-title">@lang('product.import_products')</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<strong>@lang( 'product.file_to_import' ):</strong>
					</div>
					<div class="col-md-12">
						<div id="import_transfer_product_dz" class="dropzone"></div>
					</div>
					<div class="col-md-12 mt-10">
						<a href="{{ asset('files/import_stock_transfer_csv_template.xls') }}" class="btn btn-success" download><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-sm-12" id="file_import_error">
						
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<h4>{{__('lang_v1.instructions')}}:</h4>
						<strong>@lang('lang_v1.instruction_line1')</strong><br>
		                    @lang('lang_v1.instruction_line2')
		                    <br><br>
						<table class="table table-striped">
		                    <tr>
		                        <th>@lang('lang_v1.col_no')</th>
		                        <th>@lang('lang_v1.col_name')</th>
		                        <th>@lang('lang_v1.instruction')</th>
		                    </tr>
		                    <tr>
		                    	<td>1</td>
		                        <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
		                        <td></td>
		                    </tr>
		                    <tr>
		                    	<td>2</td>
		                        <td>@lang('purchase.purchase_quantity') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
		                        <td></td>
		                    </tr>
		                    
		                </table>
		            </div>
				</div>
			</div>
			<div class="modal-footer">
      			<button type="button" class="btn btn-primary" id="import_stock_transfer_products"> @lang( 'lang_v1.import' )</button>
      			<button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    		</div>
  		</div>
  	</div>
</div>