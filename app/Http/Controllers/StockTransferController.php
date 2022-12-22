<?php

namespace App\Http\Controllers;

use DB;

use Excel;
use Datatables;
use App\Variation;
use App\Transaction;

use App\PurchaseLine;
use App\Product;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Spatie\Activitylog\Models\Activity;
use App\TransactionSellLinesPurchaseLines;

class StockTransferController extends Controller
{

    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->status_colors = [
            'in_transit' => 'bg-yellow',
            'completed' => 'bg-green',
            'pending' => 'bg-red',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        $permitted_locations = auth()->user()->permitted_locations();

        $statuses = $this->stockTransferStatuses();
        $business_id = request()->session()->get('user.business_id');

        
        $business_locations = BusinessLocation::forDropdown($business_id, false, false, true, false);
        $business_locations_2 = BusinessLocation::forDropdown($business_id, false, false, true, false);

        if (request()->ajax()) {
            $edit_days = request()->session()->get('business.transaction_edit_days');
            // $stock_transfers
            $query = Transaction::join(
                        'business_locations AS l1',
                        'transactions.location_id',
                        '=',
                        'l1.id'
                    );
                    $query->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id');
                    $query->join(
                                'business_locations AS l2',
                                't2.location_id',
                                '=',
                                'l2.id'
                    );
                    $query->where('transactions.business_id', $business_id);
                    $query->whereIn('transactions.type', ['sell_transfer', 'purchase_transfer']);

                    // if($permitted_locations != 'all'){
                    //     $query->whereIn('transactions.location_id', $permitted_locations);
                    // }

                    if (request()->has('location_from')) {
                        $location_from = request()->get('location_from');
                        if (!empty($location_from)) {
                            $query->where('transactions.location_id', $location_from);
                        }
                    }

                    if (request()->has('location_to')) {
                        $location_to = request()->get('location_to');
                        if (!empty($location_to)) {
                            $query->where('t2.location_id', $location_to);
                        }
                    }

                    if (request()->has('filter_status')) {
                        $filter_status = request()->get('filter_status');
                        if (!empty($filter_status)) {
                            $filter_status = $filter_status == 'completed' ? 'final' : $filter_status;
                            $query->where('transactions.status', $filter_status);
                        }
                    }

                    if (!empty(request()->start_date) && !empty(request()->end_date)) {
                        $start = request()->start_date;
                        $end =  request()->end_date;
                        $query->whereDate('transactions.transaction_date', '>=', $start)
                                    ->whereDate('transactions.transaction_date', '<=', $end);
                    }

                    $stock_transfers = $query->select(
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.ref_no',
                        'l1.name as location_from',
                        'l2.name as location_to',
                        'transactions.final_total',
                        'transactions.shipping_charges',
                        'transactions.additional_notes',
                        'transactions.id as DT_RowId',
                        'transactions.status',
                        'transactions.created_by'
                    );
            
            return Datatables::of($stock_transfers)
                ->addColumn('action', function ($row) use ($edit_days) {
                    $html = '<button type="button" title="' . __("stock_adjustment.view_details") . '" class="btn btn-primary btn-xs btn-modal" data-container=".view_modal" data-href="' . action('StockTransferController@show', [$row->id]) . '"><i class="fa fa-eye" aria-hidden="true"></i> ' . __('messages.view') . '</button>';

                    $html .= ' <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-print" aria-hidden="true"></i>'. __("messages.print") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                        <li>
                                        <a href="#" class="print-invoice" data-href="' . action('StockTransferController@printInvoice', [$row->id, 'type'=>'delivery-challan']) . '">'. __("messages.delivery_challan") .'</a>
                                        </li>
                                        <li>
                                        <a href="#" class="print-invoice" data-href="' . action('StockTransferController@printInvoice', [$row->id, 'type'=>'tax-invoice']) . '">'. __("messages.tax_invoice") .'</a>
                                        </li>
                                    </ul>
                                </div>';

                    $date = \Carbon::parse($row->transaction_date)
                        ->addDays($edit_days);
                    $today = today();

                    if (($date->gte($today) && auth()->user()->id == $row->created_by) || auth()->user()->getRoleNameAttribute() == 'Admin') {
                        $html .= '&nbsp;
                        <button type="button" data-href="' . action("StockTransferController@destroy", [$row->id]) . '" class="btn btn-danger btn-xs delete_stock_transfer"><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</button>';
                    }

                    if (auth()->user()->getRoleNameAttribute() == 'Admin' || ($row->status != 'final' && auth()->user()->id == $row->created_by)) {
                        $html .= '&nbsp;
                        <a href="' . action("StockTransferController@edit", [$row->id]) . '" class="btn btn-primary btn-xs"><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a>';
                    }

                    return $html;
                })
                ->editColumn(
                    'final_total',
                    '<span class="display_currency" data-currency_symbol="true">{{$final_total}}</span>'
                )
                ->editColumn(
                    'shipping_charges',
                    '<span class="display_currency" data-currency_symbol="true">{{$shipping_charges}}</span>'
                )
                ->editColumn('status', function($row) use($statuses) {
                    $row->status = $row->status == 'final' ? 'completed' : $row->status;
                    $status =  $statuses[$row->status];
                    $status_color = !empty($this->status_colors[$row->status]) ? $this->status_colors[$row->status] : 'bg-gray';
                    $status = $row->status != 'completed' ? '<a href="#" class="stock_transfer_status" data-status="' . $row->status . '" data-href="' . action("StockTransferController@updateStatus", [$row->id]) . '"><span class="label ' . $status_color .'">' . $statuses[$row->status] . '</span></a>' : '<span class="label ' . $status_color .'">' . $statuses[$row->status] . '</span>';
                     
                    return $status;
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['final_total', 'action', 'shipping_charges', 'status'])
                ->setRowAttr([
                'data-href' => function ($row) {
                    return  action('StockTransferController@show', [$row->id]);
                }])
                ->make(true);
        }

        return view('stock_transfer.index')->with(compact('statuses','business_locations', 'business_locations_2'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $business_locations_2 = BusinessLocation::forDropdown($business_id, false, false, true, false);

        $statuses = $this->stockTransferStatuses();

        return view('stock_transfer.create')
                ->with(compact('business_locations', 'statuses', 'business_locations_2'));
    }

    private function stockTransferStatuses()
    {
        return [
            'pending' => __('lang_v1.pending'),
            'in_transit' => __('lang_v1.in_transit'),
            'completed' => 'Received'
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
            }

            DB::beginTransaction();

            $input_data = $request->only([ 'location_id', 'ref_no', 'transaction_date', 'additional_notes', 'shipping_charges', 'final_total']);
            $status = $request->input('status');

            $input_data['type'] = 'sell_transfer';
            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges']);
            $input_data['payment_status'] = 'paid';
            $input_data['status'] = $status == 'completed' ? 'final' : $status;

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);
            }

            $sell_lines = [];
            $purchase_lines = [];
            
            $fromTransferLocation = BusinessLocation::findOrFail($request->input('location_id'));
            $toTransferLocation = BusinessLocation::findOrFail($request->input('transfer_location_id'));

            $is_valid = true;
            $error_msg = '';

            if ($request->hasFile('stock_transfer_csv')) {

                $notAllowed = $this->productUtil->notAllowedInDemo();
                if (!empty($notAllowed)) {
                    return $notAllowed;
                }
                
                //Set maximum php execution time
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', -1);

                $file = $request->file('stock_transfer_csv');
                
                $parsed_array = Excel::toArray([], $file);
                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);
                
                $input_data['final_total'] = 0;
                
                $product_ids = 0;
                
                foreach ($imported_data as $key => $value) {
                    $row_no = $key + 1;

                    //Check for product SKU, get product id, variation id.
                    if (!empty($value[0])) {
                        $sku = $value[0];
                        $entered_qty = trim($value[1]);
                        $product_info = Variation::where('sub_sku', $sku)
                                ->join('products AS P', 'variations.product_id', '=', 'P.id')
                                ->leftjoin('variation_location_details AS VLD', 'variations.id', 'VLD.variation_id')
                                ->where('P.business_id', $business_id)
                                ->where('VLD.location_id', $fromTransferLocation->id)
                                ->select(['P.id', 'variations.id as variation_id', 'variations.dpp_inc_tax as unit_price', 'P.enable_stock', 'VLD.qty_available'])
                                ->first();

                        if (empty($product_info)) {
                            $is_valid =  false;
                            $error_msg = "Product with sku $sku not found in row no. $row_no";
                            break;
                        } elseif ($product_info->enable_stock == 0) {
                            $is_valid =  false;
                            $error_msg = "Manage Stock not enabled for the product with $sku in row no. $row_no";
                            break;
                        } elseif ($product_info->qty_available < $entered_qty) {
                            $is_valid =  false;
                            $avl_qty = floatval($product_info->qty_available);
                            $error_msg = "Only $avl_qty quantity available in $sku in row no. $row_no. Please enter quantity below  $avl_qty.";
                            break;
                        }
                    } else {
                        $is_valid =  false;
                        $error_msg = "PRODUCT SKU is required in row no. $row_no";
                        break;
                    }
                                        
                    if (!is_numeric(trim($value[1]))) {
                        $is_valid = false;
                        $error_msg = "Invalid quantity $value[1] in row no. $row_no";
                        break;
                    }

                    // Add product when all validations are completed
                    $tax_id = null;
                    $item_tax = 0;
                    
                    $product_detail = Product::findOrFail($product_info->id);
                    
                    $tax_id = $product_detail->product_tax?$product_detail->product_tax->id:null;
                
                    $tax_rate = $product_detail->product_tax?$product_detail->product_tax->amount:0;
                    
                    $item_tax = ($this->productUtil->num_uf($product_info->unit_price)*$tax_rate/100);
                    
                    $product_ids[] = $product_info->id;
                    
                    $sell_line_arr = [
                        'product_id' => $product_info->id,
                        'variation_id' => $product_info->variation_id,
                        'quantity' => $this->productUtil->num_uf($entered_qty),
                        'item_tax' => $item_tax,
                        'tax_id' => $tax_id
                    ];

                    $purchase_line_arr = $sell_line_arr;
                    $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product_info->unit_price);
                    $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];

                    $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                    $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];

                    if (!empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to sell line
                        $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];

                        //Copy lot number and expiry date to purchase line
                        $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                        $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                        $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                        $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                    }

                    $sell_lines[] = $sell_line_arr;
                    $purchase_lines[] = $purchase_line_arr;
                    $input_data['final_total'] += $sell_line_arr['unit_price'] * $entered_qty;
                }
                
            }else{
                $products = $request->input('products');
                $input_data['final_total'] = $this->productUtil->num_uf($input_data['final_total']);
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $tax_id = null;
                        $item_tax = 0;
                        
                        $product_detail = Product::findOrFail($product['product_id']);

                        $tax_id = $product_detail->product_tax?$product_detail->product_tax->id:null;

                        $tax_rate = $product_detail->product_tax?$product_detail->product_tax->amount:0;

                        $item_tax = ($this->productUtil->num_uf($product['unit_price'])*$tax_rate/100);


                        $sell_line_arr = [
                            'product_id' => $product['product_id'],
                            'variation_id' => $product['variation_id'],
                            'quantity' => $this->productUtil->num_uf($product['quantity']),
                            'item_tax' => $item_tax,
                            'tax_id' => $tax_id
                        ];
    
                        $purchase_line_arr = $sell_line_arr;
                        $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product['unit_price']);
                        $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];
    
                        $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                        $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];
    
                        if (!empty($product['lot_no_line_id'])) {
                            //Add lot_no_line_id to sell line
                            $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];
    
                            //Copy lot number and expiry date to purchase line
                            $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                            $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                            $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                            $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                        }
    
                        $sell_lines[] = $sell_line_arr;
                        $purchase_lines[] = $purchase_line_arr;
                    }
                }
            }
            $input_data['total_before_tax'] = $input_data['final_total'];

            //Create Sell Transfer transaction_date_range
            $sell_transfer = Transaction::create($input_data);


            //Create Purchase Transfer at transfer location
            $input_data['type'] = 'purchase_transfer';
            $input_data['location_id'] = $request->input('transfer_location_id');
            $input_data['transfer_parent_id'] = $sell_transfer->id;
            $input_data['status'] = $status == 'completed' ? 'received' : $status;

            $purchase_transfer = Transaction::create($input_data);

            //Sell Product from first location
            if (!empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $input_data['location_id']);
            }

            //Purchase product in second location
            if (!empty($purchase_lines)) {
                $purchase_transfer->purchase_lines()->createMany($purchase_lines);
            }

            //Decrease product stock from sell location
            //And increase product stock at purchase location
            if ($status == 'completed') {
                if($request->hasFile('stock_transfer_csv')){
                    if($is_valid){
                        foreach ($imported_data as $key => $value) {
                            $sku = $value[0];
                            $entered_qty = trim($value[1]);
                            $product_info = Variation::where('sub_sku', $sku)
                                    ->join('products AS P', 'variations.product_id', '=', 'P.id')
                                    ->leftjoin('variation_location_details AS VLD', 'variations.id', 'VLD.variation_id')
                                    ->where('P.business_id', $business_id)
                                    ->select(['P.id', 'variations.id as variation_id', 'variations.dpp_inc_tax as unit_price', 'P.enable_stock', 'VLD.qty_available'])
                                    ->first();
                                    
                            $product_ids[] = $product_info->id;
                            if ($product_info->enable_stock) {
                                $this->productUtil->decreaseProductQuantity(
                                    $product_info->id,
                                    $product_info->variation_id,
                                    $sell_transfer->location_id,
                                    $this->productUtil->num_uf($entered_qty)
                                );
        
                                $this->productUtil->updateProductQuantity(
                                    $purchase_transfer->location_id,
                                    $product_info->id,
                                    $product_info->variation_id,
                                    $entered_qty
                                );
                            }
                        }
                    }
                }else{
                    foreach ($products as $product) {
                        
                        $product_ids[] = $product['product_id'];
                        
                        if ($product['enable_stock']) {
                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $sell_transfer->location_id,
                                $this->productUtil->num_uf($product['quantity'])
                            );
    
                            $this->productUtil->updateProductQuantity(
                                $purchase_transfer->location_id,
                                $product['product_id'],
                                $product['variation_id'],
                                $product['quantity']
                            );
                        }
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                            'accounting_method' => $request->session()->get('business.accounting_method'),
                            'location_id' => $sell_transfer->location_id
                        ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
                
                $this->productUtil->updateProductLocations($business_id, $product_ids, [$toTransferLocation->id], 'add');

            }

            $this->transactionUtil->activityLog($sell_transfer, 'added');

            if (!$is_valid) {
                throw new \Exception($error_msg);
            }

            $output = ['success' => 1,
                            'msg' => __('lang_v1.stock_transfer_added_successfully')
                        ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => "Message:" . $e->getMessage()
                        ];
            return redirect('stock-transfers/create')->with('notification', $output);
        }

        return redirect('stock-transfers')->with('status', $output);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
            
        $sell_transfer = Transaction::where('business_id', $business_id)
                            ->where('id', $id)
                            ->where('type', 'sell_transfer')
                            ->with(
                                'contact',
                                'sell_lines',
                                'sell_lines.product',
                                'sell_lines.variations',
                                'sell_lines.variations.product_variation',
                                'sell_lines.lot_details',
                                'location',
                                'sell_lines.product.unit'
                            )
                            ->first();

        $purchase_transfer = Transaction::where('business_id', $business_id)
                    ->where('transfer_parent_id', $sell_transfer->id)
                    ->where('type', 'purchase_transfer')
                    ->first();

        $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        $statuses = $this->stockTransferStatuses();

        $statuses['final'] = __('restaurant.completed');
        
        $activities = Activity::forSubject($sell_transfer)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        return view('stock_transfer.show')
                ->with(compact('sell_transfer', 'location_details', 'lot_n_exp_enabled', 'statuses', 'activities'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                $edit_days = request()->session()->get('business.transaction_edit_days');
                if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
                    return ['success' => 0,
                        'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])];
                }

                //Get sell transfer transaction
                $sell_transfer = Transaction::where('id', $id)
                                    ->where('type', 'sell_transfer')
                                    ->with(['sell_lines'])
                                    ->first();

                //Get purchase transfer transaction
                $purchase_transfer = Transaction::where('transfer_parent_id', $sell_transfer->id)
                                    ->where('type', 'purchase_transfer')
                                    ->with(['purchase_lines'])
                                    ->first();

                //Check if any transfer stock is deleted and delete purchase lines
                $purchase_lines = $purchase_transfer->purchase_lines;
                foreach ($purchase_lines as $purchase_line) {
                    if ($purchase_line->quantity_sold > 0) {
                        return [ 'success' => 0,
                                        'msg' => __('lang_v1.stock_transfer_cannot_be_deleted')
                            ];
                    }
                }

                DB::beginTransaction();
                //Get purchase lines from transaction_sell_lines_purchase_lines and decrease quantity_sold
                $sell_lines = $sell_transfer->sell_lines;
                $deleted_sell_purchase_ids = [];
                $products = []; //variation_id as array

                foreach ($sell_lines as $sell_line) {
                    $purchase_sell_line = TransactionSellLinesPurchaseLines::where('sell_line_id', $sell_line->id)->first();

                    if (!empty($purchase_sell_line)) {
                        //Decrease quntity sold from purchase line
                        PurchaseLine::where('id', $purchase_sell_line->purchase_line_id)
                                ->decrement('quantity_sold', $sell_line->quantity);

                        $deleted_sell_purchase_ids[] = $purchase_sell_line->id;

                        //variation details
                        if (isset($products[$sell_line->variation_id])) {
                            $products[$sell_line->variation_id]['quantity'] += $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        } else {
                            $products[$sell_line->variation_id]['quantity'] = $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        }
                    }
                }

                //Update quantity available in both location
                if (!empty($products)) {
                    foreach ($products as $key => $value) {
                        //Decrease from location 2
                        $this->productUtil->decreaseProductQuantity(
                            $products[$key]['product_id'],
                            $key,
                            $purchase_transfer->location_id,
                            $products[$key]['quantity']
                        );

                        //Increase in location 1
                        $this->productUtil->updateProductQuantity(
                            $sell_transfer->location_id,
                            $products[$key]['product_id'],
                            $key,
                            $products[$key]['quantity']
                        );
                    }
                }

                //Delete sale line purchase line
                if (!empty($deleted_sell_purchase_ids)) {
                    TransactionSellLinesPurchaseLines::whereIn('id', $deleted_sell_purchase_ids)
                        ->delete();
                }

                //Delete both transactions
                $sell_transfer->delete();
                $purchase_transfer->delete();

                $output = ['success' => 1,
                        'msg' => __('lang_v1.stock_transfer_delete_success')
                    ];
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }
        return $output;
    }

    /**
     * Checks if ref_number and supplier combination already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $print_format = $request->type;
            $sell_transfer = Transaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->where('type', 'sell_transfer')
                                ->with(
                                    'contact',
                                    'sell_lines',
                                    'sell_lines.product',
                                    'sell_lines.variations',
                                    'sell_lines.variations.product_variation',
                                    'sell_lines.lot_details',
                                    'location',
                                    'sell_lines.product.unit'
                                )
                                ->first();
                                


            $output_taxes = ['taxes' => []];
            

            foreach($sell_transfer->sell_lines as $sell_lines){
                
                $tax = $sell_lines->product->product_tax;
                
                if (!empty($tax)) {
                    $tax_name = $tax->name;
                    if (!isset($output_taxes['taxes'][$tax_name]['taxable_amount'])) {
                        $output_taxes['taxes'][$tax_name]['taxable_amount'] = 0;
                    }
                    $output_taxes['taxes'][$tax_name]['taxable_amount'] += $sell_lines->quantity * $sell_lines->unit_price;
                    
                    if (!isset($output_taxes['taxes'][$tax_name]['total_tax'])) {
                        $output_taxes['taxes'][$tax_name]['total_tax'] = 0;
                    }
                    $output_taxes['taxes'][$tax_name]['total_tax'] += ($sell_lines->quantity * $sell_lines->item_tax);
                    
                    if ($tax) {
                        $group_tax_details = $this->transactionUtil->groupTaxDetails($tax, $sell_lines->quantity * $sell_lines->item_tax);
                        
                        foreach ($group_tax_details as $key => $value) {
                            if (!isset($output_taxes['taxes'][$tax_name][$value['name']])) {
                                $output_taxes['taxes'][$tax_name][$value['name']] = 0;
                            }
                            $output_taxes['taxes'][$tax_name][$value['name']] = $value;
                        }
                    }
                    else {
                        
                        if (!isset($output_taxes['taxes'][$tax_name])) {
                            $output_taxes['taxes'][$tax_name]['total_tax']= 0;
                        }
                        $output_taxes['taxes'][$tax_name]['total_tax']+= ($sell_lines->quantity * $sell_lines->item_tax);
                    }
                }
            }
            
            // dd($output_taxes['taxes']);

            $purchase_transfer = Transaction::where('business_id', $business_id)
                        ->where('transfer_parent_id', $sell_transfer->id)
                        ->where('type', 'purchase_transfer')
                        ->first();

            $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];

            $lot_n_exp_enabled = false;
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_n_exp_enabled = true;
            }


            $output = ['success' => 1, 'receipt' => [], 'print_title' => $sell_transfer->ref_no];
            $output['receipt']['html_content'] = view('stock_transfer.print', compact('sell_transfer', 'location_details', 'lot_n_exp_enabled','output_taxes','print_format'))->render();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);

        $statuses = $this->stockTransferStatuses();

        $sell_transfer = Transaction::where('business_id', $business_id)
                ->where('type', 'sell_transfer')
                // ->where('status', '!=', 'final')
                ->with(['sell_lines'])
                ->findOrFail($id);
        $purchase_transfer = Transaction::where('business_id', 
                $business_id)
                ->where('transfer_parent_id', $id)
                // ->where('status', '!=', 'received')
                ->where('type', 'purchase_transfer')
                ->first();
        $products = [];
        foreach ($sell_transfer->sell_lines as $sell_line) {
            $product = $this->productUtil->getDetailsFromVariation($sell_line->variation_id, $business_id, $sell_transfer->location_id);
            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
            $product->quantity_ordered = $sell_line->quantity;
            $product->transaction_sell_lines_id = $sell_line->id;
            $product->lot_no_line_id = $sell_line->lot_no_line_id;

            //Get lot number dropdown if enabled
            // $lot_numbers = [];
            // if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            //     $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($sell_line->variation_id, $business_id, $sell_transfer->location_id, true);
            //     foreach ($lot_number_obj as $lot_number) {
            //         $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
            //         $lot_numbers[] = $lot_number;
            //     }
            // }
            // $product->lot_numbers = $lot_numbers;

            $products[] = $product;
        }



        return view('stock_transfer.edit')
                ->with(compact('sell_transfer', 'purchase_transfer', 'business_locations', 'statuses', 'products'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
            }

            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell_transfer')
                    ->findOrFail($id);

            $sell_transfer_before = $sell_transfer->replicate();

            $purchase_transfer = Transaction::where('business_id', 
                    $business_id)
                    ->where('transfer_parent_id', $id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();

            $status = $request->input('status');

            DB::beginTransaction();
            
            $input_data = $request->only(['transaction_date', 'additional_notes', 'shipping_charges', 'final_total']);
            $status = $request->input('status');

            $input_data['final_total'] = $this->productUtil->num_uf($input_data['final_total']);
            $input_data['total_before_tax'] = $input_data['final_total'];

            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges']);
            $input_data['status'] = $status == 'completed' ? 'final' : $status;

            $products = $request->input('products');
            $sell_lines = [];
            $purchase_lines = [];
            $edited_purchase_lines = [];
            if (!empty($products)) {
                foreach ($products as $product) {
                    
                    $tax_id = null;
                    $item_tax = 0;
                    
                    $product_detail = Product::findOrFail($product['product_id']);

                    $tax_id = $product_detail->product_tax?$product_detail->product_tax->id:null;

                    $tax_rate = $product_detail->product_tax?$product_detail->product_tax->amount:0;

                    $item_tax = ($this->productUtil->num_uf($product['unit_price'])*$tax_rate/100);
                    
                    $sell_line_arr = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'item_tax' => $item_tax,
                        'tax_id' => $tax_id
                    ];

                    $purchase_line_arr = $sell_line_arr;
                    $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product['unit_price']);
                    $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];

                    $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                    $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];
                    if (isset($product['transaction_sell_lines_id'])) {
                        $sell_line_arr['transaction_sell_lines_id'] = $product['transaction_sell_lines_id'];
                    }

                    if (!empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to sell line
                        $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];

                        //Copy lot number and expiry date to purchase line
                        $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                        $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                        $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                        $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                    }

                    $sell_lines[] = $sell_line_arr;

                    $purchase_line = [];
                    //check if purchase_line for the variation exists else create new 
                    foreach ($purchase_transfer->purchase_lines as $pl) {
                        if ($pl->variation_id == $purchase_line_arr['variation_id']) {
                            $pl->update($purchase_line_arr);
                            $edited_purchase_lines[] = $pl->id;
                            $purchase_line = $pl;
                            break;
                        }
                    }
                    if (empty($purchase_line)) {
                        $purchase_line = new PurchaseLine($purchase_line_arr);
                    }

                    $purchase_lines[] = $purchase_line;
                }
            }

            //Create Sell Transfer transaction
            $sell_transfer->update($input_data);
            $sell_transfer->save();

            //Create Purchase Transfer at transfer location
            $input_data['status'] = $status == 'completed' ? 'received' : $status;

            $purchase_transfer->update($input_data);
            $purchase_transfer->save();

            //Sell Product from first location
            if (!empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $sell_transfer->location_id, false, 'draft');
            }

            //Purchase product in second location
            if (!empty($purchase_lines)) {
                if (!empty($edited_purchase_lines)) {
                    PurchaseLine::where('transaction_id', $purchase_transfer->id)
                    ->whereNotIn('id', $edited_purchase_lines)
                    ->delete();
                }
                $purchase_transfer->purchase_lines()->saveMany($purchase_lines);
            }

            //Decrease product stock from sell location
            //And increase product stock at purchase location
            
            $product_ids = [];
            if ($status == 'completed') {
                foreach ($products as $product) {
                    
                    $product_ids[] = $product['product_id'];
                    
                    if ($product['enable_stock']) {
                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $sell_transfer->location_id,
                            $this->productUtil->num_uf($product['quantity'])
                        );

                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $product['product_id'],
                            $product['variation_id'],
                            $product['quantity']
                        );
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);
                
                $this->productUtil->updateProductLocations($business_id, $product_ids, [$purchase_transfer->location_id], 'add');

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                            'accounting_method' => $request->session()->get('business.accounting_method'),
                            'location_id' => $sell_transfer->location_id
                        ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }

            $this->transactionUtil->activityLog($sell_transfer, 'edited', $sell_transfer_before);

            $output = ['success' => 1,
                            'msg' => __('lang_v1.updated_succesfully')
                        ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
        }

        return redirect('stock-transfers')->with('status', $output);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        if (!auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell_transfer')
                    ->with(['sell_lines', 'sell_lines.product'])
                    ->findOrFail($id);

            $purchase_transfer = Transaction::where('business_id', 
                    $business_id)
                    ->where('transfer_parent_id', $id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();

            $status = $request->input('status');

            DB::beginTransaction();
            
            $product_ids = [];
            if ($status == 'completed' && $sell_transfer->status != 'completed' ) {

                foreach ($sell_transfer->sell_lines as $sell_line) {
                    if ($sell_line->product->enable_stock) {
                        $this->productUtil->decreaseProductQuantity(
                            $sell_line->product_id,
                            $sell_line->variation_id,
                            $sell_transfer->location_id,
                            $sell_line->quantity
                        );
						$product_ids[] = $sell_line->product_id;
                        $this->productUtil->updateProductQuantity(
                            $purchase_transfer->location_id,
                            $sell_line->product_id,
                            $sell_line->variation_id,
                            $sell_line->quantity,
                            0,
                            null,
                            false
                        );
                    }
                }

                //Adjust stock over selling if found
                $this->productUtil->adjustStockOverSelling($purchase_transfer);

                //Map sell lines with purchase lines
                $business = ['id' => $business_id,
                            'accounting_method' => $request->session()->get('business.accounting_method'),
                            'location_id' => $sell_transfer->location_id
                        ];
                $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            }
            
            $this->productUtil->updateProductLocations($business_id, $product_ids, [$purchase_transfer->location_id], 'add');
            
            $purchase_transfer->status = $status == 'completed' ? 'received' : $status;
            $purchase_transfer->save();
            $sell_transfer->status = $status == 'completed' ? 'final' : $status;
            $sell_transfer->save();

            DB::commit();

            $output = ['success' => 1,
                        'msg' => __('lang_v1.updated_succesfully')
                    ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => "File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage()
                        ];
        }

        return $output;
    }
    
    public function uploadStockTransferProducts(Request $request)
    {
        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $file = $request->file('stock_transfer_csv');

            $parsed_array = Excel::toArray([], $file);
            //Remove header row
            $imported_data = array_splice($parsed_array[0], 1);

            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->input('location_id');
            $row_count = $request->input('row_count');
            $formatted_data = [];
            $row_index = 0;
            $error_msg = '';
            $errorArr = [];
            foreach ($imported_data as $key => $value) {
                $row_index = $key + 1;
                $temp_array = [];
                
                if (!empty($value[0])) {
                    $variation = Variation::where('sub_sku', trim($value[0]))
                                        ->with([
                                            'product_variation',
                                            'variation_location_details' => 
                                                function($q) use ($location_id) {
                                                    $q->where('location_id', $location_id);
                                                }
                                        ])->first();

                    if (empty($variation)) {
                        $errorArr[] = __('lang_v1.product_not_found_exception', ['row' => $row_index, 'sku' => $value[0]]);
                    }

                    if (!empty($variation)) {
                        $product = $this->productUtil->getDetailsFromVariation($variation->id, $business_id, $location_id);
                        $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
            
                        //Get lot number dropdown if enabled
                        $lot_numbers = [];
                        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                            $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation->id, $business_id, $location_id, true);
                            foreach ($lot_number_obj as $lot_number) {
                                $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                                $lot_numbers[] = $lot_number;
                            }
                        }
                        $product->lot_numbers = $lot_numbers;
            
                        $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->id);

                        if (empty($product)) {
                            $errorArr[] = __('lang_v1.product_not_found_exception', ['row' => $row_index, 'sku' => $value[0]]);
                            
                        }
    
                        $temp_array['product'] = $product;
        
                        $temp_array['sub_units'] = $sub_units;

                        if (!empty($value[1]) && !empty($product->formatted_qty_available)) {
                            if($value[1] > $product->formatted_qty_available){
                                $errorArr[] = $value[1]." quantity not available for ".$value[0];
                            }else{
                                $product->quantity_ordered = $value[1];
                            }
                        } else {
                            $errorArr[] = __('lang_v1.quantity_required', ['row' => $row_index]);
                            
                        }
                    }
                    
                } else {
                    $errorArr[] = __('lang_v1.product_not_found_exception', ['row' => $row_index, 'sku' => $value[0]]);
                    
                }
                $formatted_data[] = $temp_array;
                
            }
            if (count($errorArr) != 0) {
                $error_msg = 'Error';
                throw new \Exception($error_msg);
            }
            $output = ['success' => 1,
                            'msg' =>'File uploaded successfully',
                            'html' => view('stock_transfer.partials.imported_stock_transfer_product_rows')->with(compact('formatted_data', 'row_count'))->render()
                        ];
       } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => $e->getMessage(),
                            'errorArr' => $errorArr,
                        ];
        }
        return $output;
    }
}
