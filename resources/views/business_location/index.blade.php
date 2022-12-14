@extends('layouts.app')
@section('title', __('business.business_locations'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('business.business_locations')
            <small>@lang('business.manage_your_business_locations')</small>
        </h1>
        <!-- <ol class="breadcrumb">
                <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                <li class="active">Here</li>
            </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('business_running_status', __('messages.please_select') . ':') !!}
                            {!! Form::select(
                                'business_running_status',
                                ['active' => __('business.is_active'), 'inactive' => __('business.is_deactive')],
                                null,
                                ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')],
                            ) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('location_type', __('business.location_type') . ':') !!}
                            {!! Form::select('location_type', ['mart' => __('business.mart'), 'warehouse' => __('business.warehouse')], null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('business.all_your_business_locations')])
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="btn btn-block btn-primary btn-modal"
                                data-href="{{ action('BusinessLocationController@create') }}" data-container=".location_add_modal">
                                <i class="fa fa-plus"></i> @lang('messages.add')</button>
                        </div>
                    @endslot
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="business_location_table">
                            <thead>
                                <tr>
                                    <th>@lang('invoice.name')</th>
                                    <th>@lang('lang_v1.location_id')</th>
                                    <th>@lang('business.landmark')</th>
                                    <th>@lang('business.city')</th>
                                    <th>@lang('business.zip_code')</th>
                                    <th>@lang('business.state')</th>
                                    <th>@lang('business.country')</th>
                                    <th>@lang('lang_v1.price_group')</th>
                                    <th>@lang('invoice.invoice_scheme')</th>
                                    <th>@lang('lang_v1.invoice_layout_for_pos')</th>
                                    <th>@lang('lang_v1.invoice_layout_for_sale')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent

                <div class="modal fade location_add_modal" tabindex="-1" role="dialog"
                    aria-labelledby="gridSystemModalLabel">
                </div>
                <div class="modal fade location_edit_modal" tabindex="-1" role="dialog"
                    aria-labelledby="gridSystemModalLabel">
                </div>
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection
