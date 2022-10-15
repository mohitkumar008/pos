{{-- @php dd($page_products) @endphp --}}
<table align="center" style="border-spacing: {{$barcode_details->col_distance * 1}}in {{$barcode_details->row_distance * 1}}in; overflow: hidden !important;">
    @foreach($page_products as $page_product)
    
        @if($loop->index % $barcode_details->stickers_in_one_row == 0)
            <!-- create a new row -->
            <tr>
            <!-- <columns column-count="{{$barcode_details->stickers_in_one_row}}" column-gap="{{$barcode_details->col_distance*1}}"> -->
        @endif
            <td align="center" valign="center" style="position: relative;">
                <img src="{{asset('img/green-dot-img.png')}}" alt="" class="img-responsive food-logo">
                <div style="overflow: hidden !important;display: flex; flex-wrap: wrap;align-content: center;text-align:start;margin-left:{{$barcode_details->left_margin * 1}}in;width: {{$barcode_details->width * 1}}in; height: {{$barcode_details->height}}in;">
    
                    <div style="width: {{($barcode_details->width * 1)/2}}in;display: flex;flex-direction: column;justify-content: space-between;">
    
                        {{-- Business Name --}}
                        @if(!empty($print['business_name']))
                            <span style="display: block !important; font-size: 10px;margin-bottom: 1px;">Name and address of Manufacturer:</span>
                            <span style="display: block !important; font-size: 10px;margin-bottom: 2px;">{{$business_name}}</span>
                            <address style="font-size: 8px;margin-bottom: 2px;">Company Address- KO-01, SECTOR-122, G.B NAGAR, NOIDA, , NOIDA CITY ZONE-4, Gautam Buddha Nagar, Uttar Pradesh-201301</address>
                            <div style="font-size: 10px;">License No: 1221027000317 </div>
                        @endif
    
                        {{-- Product Name --}}
                        @if(!empty($print['name']))
                            <span style="display: block !important; font-size: 10px;margin-bottom: 3px;">
                                Generic name of the commodity: 
                                {{$page_product->product_actual_name}}
                            </span>
                            @endif
                            <div style="display: flex;justify-content: space-between;">
                                <div class="">
                                    @if ($page_product->weight)
                                        <div style="font-size: 10px;">Net Quantity: 1(N)</div>
                                        <div style="font-size: 10px;">Weight: {{$page_product->weight}}{{$page_product->unit}}</div>
                                    @endif
                                </div>
                                <div class="">
                                    {{-- Barcode --}}
                                    <img style="max-width:90% !important;height: {{0.24}}in !important;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product->sub_sku, $page_product->barcode_type, 1,30, array(0, 0, 0), true)}}">
                                </div>
                            </div>
                        {{-- Variation --}}
                        @if(!empty($print['variations']) && $page_product->is_dummy != 1)
                            <span style="display: block !important; font-size: 10px">
                                {{$page_product->product_variation_name}}:<b>{{$page_product->variation_name}}</b>
                            </span>
                        @endif
    
                    </div>
                    <div style="width: {{($barcode_details->width * 1)/2}}in;display: flex;flex-direction: column;justify-content: space-evenly;">
    
                        {{-- Manufacturer date --}}
                        @if(!empty($print['packing_date']) && !empty($page_product->packing_date))
                            <span style="font-size: 10px">
                                Month and year of Manufacturer:
                                {{date_format(date_create($page_product->packing_date), "F Y")}}
                            </span>
                        @endif						
                            
                            {{-- Expiry date --}}
                        @if(!empty($print['exp_date']) && !empty($page_product->exp_date))
                            <span style="font-size: 10px;margin-bottom: 1px;">
                                Best Before:
                                {{date_format(new DateTime($page_product->exp_date), "F Y")}}
                            </span>
                        @endif

                        @if(!empty($print['lot_number']) && !empty($page_product->lot_number))
                            <span style="font-size: 10px">
                                Batch No: {{$page_product->lot_number}}
                            </span>
                        @endif
    
                        {{-- Price --}}
                        @if(!empty($print['price']))
                            <span style="font-size: 10px;">
                                MRP Rs. (incl. of all taxes):
                                {{session('currency')['symbol'] ?? ''}}
                                @if($print['price_type'] == 'inclusive')
                                    {{@num_format($page_product->sell_price_inc_tax)}}
                                @else
                                    {{@num_format($page_product->default_sell_price)}}
                                @endif
                            </span>
                        @endif
    
                        {{-- Compaints --}}
                        <div class="">
                            <div style="font-size: 12px;margin-bottom:1px;text-decoration: underline;"><b>For consumer complaints:</b></div>
                            <div style="font-size: 10px;">Armada Bazzar Pvt. Ltd. </div>
                            <div style="font-size: 10px;">Noida-201301, Uttar Pradesh, India</div>
                            <div style="font-size: 10px;">Tel:  8287636220</div>
                            <div style="font-size: 10px;">Email id:   info@armadabazzar.com</div>
                            <div style="font-size: 10px;">Website: www.armadabazzar.com</div>
                        </div>
    
                    </div>
                </div>
                
                {{-- @if($barcode_details->is_continuous)
                <br>
                @endif --}}
            
            </td>
    
        @if($loop->iteration % $barcode_details->stickers_in_one_row == 0)
            </tr>
        @endif
    @endforeach
    </table>
    
    <style type="text/css">

        .food-logo{
            position: absolute;
            right: 5px;
            width: 15px;
            top: 5px;
        }
    
        td{
            border: 1px dotted lightgray;
            background: aliceblue;
        }
        @media print{
            
            table{
                page-break-after: always;
            }
    
            
            @page {
            size: {{$paper_width}}in {{$paper_height}}in;
    
            width: {{$barcode_details->paper_width}}in !important;
            height:@if($barcode_details->paper_height != 0){{$barcode_details->paper_height}}in !important @else auto @endif;
            margin-top: {{$margin_top}}in !important;
            margin-bottom: {{$margin_top}}in !important;
            margin-left: {{$margin_left}}in !important;
            margin-right: {{$margin_left}}in !important;
            }
        }
    </style>