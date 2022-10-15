<?php $row_count = $rowCount; ?>
@foreach ($products as $list)
    <tr @if (!empty($purchase_order_line)) data-purchase_order_id="{{ $purchase_order_line->transaction_id }}" @endif>
        <td><span class="sr_number"></span></td>
        <td>
            {{ $list['product']->name }} ({{ $list['variation'][0]->sub_sku }})
            @if ($list['product']->type == 'variable')
                <br />
                (<b>{{ $list['variation'][0]->product_variation->name }}</b> : {{ $list['variation'][0]->name }})
            @endif
            @if ($list['product']->enable_stock == 1)
                <br>
                <small class="text-muted" style="white-space: nowrap;">@lang('report.current_stock'): @if (!empty($list['variation'][0]->variation_location_details->first()))
                        {{ @num_format($list['variation'][0]->variation_location_details->first()->qty_available) }}
                    @else
                        0
                    @endif {{ $list['product']->unit->short_name }}</small>
            @endif

        </td>
        <td>
            @if (!empty($purchase_order_line))
                {!! Form::hidden('purchases[' . $row_count . '][purchase_order_line_id]', $purchase_order_line->id) !!}
            @endif

            {!! Form::hidden('purchases[' . $row_count . '][product_id]', $list['product']->id) !!}
            {!! Form::hidden('purchases[' . $row_count . '][variation_id]', $list['variation'][0]->id, [
                'class' => 'hidden_variation_id',
            ]) !!}

            @php
                $check_decimal = 'false';
                if ($list['product']->unit->allow_decimal == 0) {
                    $check_decimal = 'true';
                }
                $currency_precision = config('constants.currency_precision', 2);
                $quantity_precision = config('constants.quantity_precision', 2);
                
                $quantity_value = !empty($purchase_order_line) ? $purchase_order_line->quantity : $list['quantity'];
                $max_quantity = !empty($purchase_order_line) ? $purchase_order_line->quantity - $purchase_order_line->po_quantity_purchased : 0;
            @endphp

            <input type="text" name="purchases[{{ $row_count }}][quantity]"
                value="{{ @format_quantity($quantity_value) }}"
                class="form-control input-sm purchase_quantity input_number mousetrap" required
                data-rule-abs_digit={{ $check_decimal }}
                data-msg-abs_digit="{{ __('lang_v1.decimal_value_not_allowed') }}"
                @if (!empty($max_quantity)) data-rule-max-value="{{ $max_quantity }}"
                    data-msg-max-value="{{ __('lang_v1.max_quantity_quantity_allowed', ['quantity' => $max_quantity]) }}" @endif>


            <input type="hidden" class="base_unit_cost" value="{{ $list['variation'][0]->default_purchase_price }}">
            <input type="hidden" class="base_unit_selling_price"
                value="{{ $list['variation'][0]->sell_price_inc_tax }}">

            <input type="hidden" name="purchases[{{ $row_count }}][product_unit_id]"
                value="{{ $list['product']->unit->id }}">
            @if (!empty($list['sub_units']))
                <br>
                <select name="purchases[{{ $row_count }}][sub_unit_id]" class="form-control input-sm sub_unit">
                    @foreach ($list['sub_units'] as $key => $value)
                        <option value="{{ $key }}" data-multiplier="{{ $value['multiplier'] }}">
                            {{ $value['name'] }}
                        </option>
                    @endforeach
                </select>
            @else
                {{ $list['product']->unit->short_name }}
            @endif
        </td>
        <td>
            @php
                $pp_without_discount = !empty($purchase_order_line) ? $purchase_order_line->pp_without_discount / $purchase_order->exchange_rate : $list['pp_without_discount'];
                
                $discount_percent = !empty($purchase_order_line) ? $purchase_order_line->discount_percent : $list['discount_percent'];
                
                $default_purchase_price = $pp_without_discount;
                if ($discount_percent > 0) {
                    $default_purchase_price = $pp_without_discount - ($pp_without_discount * $discount_percent) / 100;
                }
                
                $purchase_price = !empty($purchase_order_line) ? $purchase_order_line->purchase_price / $purchase_order->exchange_rate : $default_purchase_price;
                
                $tax_id = !empty($purchase_order_line) ? $purchase_order_line->tax_id : $list['product']->tax;
                
            @endphp
            {!! Form::text(
                'purchases[' . $row_count . '][pp_without_discount]',
                number_format(
                    floatval($pp_without_discount),
                    $currency_precision,
                    $list['currency_details']->decimal_separator,
                    $list['currency_details']->thousand_separator,
                ),
                ['class' => 'form-control input-sm purchase_unit_cost_without_discount input_number', 'required'],
            ) !!}
        </td>
        <td>
            {!! Form::text(
                'purchases[' . $row_count . '][discount_percent]',
                number_format(
                    floatval($discount_percent),
                    $currency_precision,
                    $list['currency_details']->decimal_separator,
                    $list['currency_details']->thousand_separator,
                ),
                ['class' => 'form-control input-sm inline_discounts input_number', 'required'],
            ) !!}
        </td>
        <td>
            {!! Form::text(
                'purchases[' . $row_count . '][purchase_price]',
                number_format(
                    floatval($purchase_price),
                    $currency_precision,
                    $list['currency_details']->decimal_separator,
                    $list['currency_details']->thousand_separator,
                ),
                ['class' => 'form-control input-sm purchase_unit_cost input_number', 'required'],
            ) !!}
        </td>
        <td class="{{ $list['hide_tax'] }}">
            <span class="row_subtotal_before_tax display_currency">0</span>
            <input type="hidden" class="row_subtotal_before_tax_hidden" value=0>
        </td>
        <td class="{{ $list['hide_tax'] }}">
            <div class="input-group">
                <select name="purchases[{{ $row_count }}][purchase_line_tax_id]"
                    class="form-control select2 input-sm purchase_line_tax_id" placeholder="'Please Select'">
                    <option value="" data-tax_amount="0" @if ($list['hide_tax'] == 'hide') selected @endif>
                        @lang('lang_v1.none')</option>
                    @foreach ($list['taxes'] as $tax)
                        <option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}"
                            @if ($tax_id == $tax->id && $list['hide_tax'] != 'hide') selected @endif>{{ $tax->name }} -
                            {{ $tax->amount }}%</option>
                    @endforeach
                </select>
                {!! Form::hidden('purchases[' . $row_count . '][item_tax]', 0, ['class' => 'purchase_product_unit_tax']) !!}
                <span class="input-group-addon purchase_product_unit_tax_text">
                    0.00</span>
            </div>
        </td>


        <td class="{{ $list['hide_tax'] }}">
            @php
                
                $dpp_inc_tax = $default_purchase_price;
                if ($list['product']->product_tax != null) {
                    $dpp_inc_tax = $default_purchase_price + ($default_purchase_price * $list['product']->product_tax->amount) / 100;
                }
                
                $dpp_inc_tax = number_format($dpp_inc_tax, $currency_precision, $list['currency_details']->decimal_separator, $list['currency_details']->thousand_separator);
                if ($list['hide_tax'] == 'hide') {
                    $dpp_inc_tax = number_format($default_purchase_price, $currency_precision, $list['currency_details']->decimal_separator, $list['currency_details']->thousand_separator);
                }
                
                $dpp_inc_tax = !empty($purchase_order_line) ? number_format($purchase_order_line->purchase_price_inc_tax / $purchase_order->exchange_rate, $currency_precision, $list['currency_details']->decimal_separator, $list['currency_details']->thousand_separator) : $dpp_inc_tax;
                
            @endphp
            {!! Form::text('purchases[' . $row_count . '][purchase_price_inc_tax]', $dpp_inc_tax, [
                'class' => 'form-control input-sm purchase_unit_cost_after_tax input_number',
                'required',
            ]) !!}
        </td>
        <td>
            <span class="row_subtotal_after_tax display_currency">0</span>
            <input type="hidden" class="row_subtotal_after_tax_hidden" value=0>
        </td>

        {{-- done till here --}}
        @php
            $profit_percent = (($list['default_sell_price'] - $dpp_inc_tax) / $dpp_inc_tax) * 100;
        @endphp
        <td class="@if (!session('business.enable_editing_product_from_purchase') || !empty($is_purchase_order)) hide @endif">
            {!! Form::text(
                'purchases[' . $row_count . '][profit_percent]',
                number_format(
                    $profit_percent,
                    $currency_precision,
                    $list['currency_details']->decimal_separator,
                    $list['currency_details']->thousand_separator,
                ),
                ['class' => 'form-control input-sm input_number profit_percent', 'required'],
            ) !!}
        </td>
        @if (empty($is_purchase_order))
            <td>
                @if (session('business.enable_editing_product_from_purchase'))
                    {!! Form::text(
                        'purchases[' . $row_count . '][default_sell_price]',
                        number_format(
                            $list['default_sell_price'],
                            $currency_precision,
                            $list['currency_details']->decimal_separator,
                            $list['currency_details']->thousand_separator,
                        ),
                        ['class' => 'form-control input-sm input_number default_sell_price', 'required'],
                    ) !!}
                @else
                    {{ number_format($list['default_sell_price'], $currency_precision, $list['currency_details']->decimal_separator, $list['currency_details']->thousand_separator) }}
                @endif
            </td>
            @if (session('business.enable_lot_number'))
                <td>
                    {!! Form::text('purchases[' . $row_count . '][lot_number]', null, ['class' => 'form-control input-sm']) !!}
                </td>
            @endif
            @if (session('business.enable_product_expiry'))
                <td style="text-align: left;">

                    @php
                        // Maybe this condition for checkin expiry date need to be removed
                        $expiry_period_type = !empty($list['product']->expiry_period_type) ? $list['product']->expiry_period_type : 'month';
                    @endphp
                    @if (!empty($expiry_period_type))
                        <input type="hidden" class="row_product_expiry" value="{{ $list['product']->expiry_period }}">
                        <input type="hidden" class="row_product_expiry_type" value="{{ $expiry_period_type }}">

                        @if (session('business.expiry_type') == 'add_manufacturing')
                            @php
                                $hide_mfg = false;
                            @endphp
                        @else
                            @php
                                $hide_mfg = true;
                            @endphp
                        @endif

                        <b class="@if ($hide_mfg) hide @endif"><small>@lang('product.mfg_date'):</small></b>
                        <div class="input-group @if ($hide_mfg) hide @endif">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('purchases[' . $row_count . '][mfg_date]', null, [
                                'class' => 'form-control input-sm expiry_datepicker mfg_date',
                                'readonly',
                            ]) !!}
                        </div>
                        <b><small>@lang('product.exp_date'):</small></b>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('purchases[' . $row_count . '][exp_date]', null, [
                                'class' => 'form-control input-sm expiry_datepicker exp_date',
                                'readonly',
                            ]) !!}
                        </div>
                    @else
                        <div class="text-center">
                            @lang('product.not_applicable')
                        </div>
                    @endif
                </td>
            @endif
        @endif

        <?php $row_count++ ?>

        <td><i class="fa fa-times remove_purchase_entry_row text-danger" title="Remove" style="cursor:pointer;"></i>
        </td>
    </tr>
@endforeach

<input type="hidden" id="row_count" value="{{ $row_count }}">
