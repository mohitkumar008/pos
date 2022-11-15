@extends('layouts.app')
@section('title', __('product.add_hsn_or_barcode'))
@section('content')
<br />
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('product.add_hsn')</h1>
</section>

<!-- Main content -->
<section class="content">

    @if (session('notification') || !empty($notification))
    <div class="row">
        <div class="col-sm-12">
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                @if(!empty($notification['msg']) || !empty($notification['hsnErrArray']))
                {{$notification['msg']}}
                @endif

                @if(session('notification.msg'))
                {{ session('notification.msg') }}
                @endif

                @if(session('notification.skuErrArray'))
                <ul>
                    @foreach (session('notification.skuErrArray') as $list)
                        <li>{{$list}}</li>
                    @endforeach
                </ul>
                @endif
                @if(session('notification.hsnErrArray'))
                <ul>
                    @foreach (session('notification.hsnErrArray') as $list)
                        <li>{{$list}}</li>
                    @endforeach
                </ul>
                @endif
                
            </div>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action('ProductController@storeHSN'), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
            <div class="row">
                <div class="col-sm-6">
                    <div class="col-sm-8">
                        <div class="form-group">
                            {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                            {!! Form::file('import_hsn_csv', ['accept'=> '.xls', 'required' => 'required']); !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <br>
                        <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                    </div>
                </div>
            </div>

            {!! Form::close() !!}
            <br><br>
            <div class="row">
                <div class="col-sm-4">
                    <a href="{{ asset('files/import_add_hsn_csv_template.xls') }}" class="btn btn-success" download><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->


<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('product.add_barcode')</h1>
</section>
{{-- @php
    dd('sdfsf');
@endphp --}}
<!-- Main content -->
<section class="content">
    
    @if (session('barcode_notification') || !empty($barcode_notification))
    <div class="row">
        <div class="col-sm-12">
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                @if(!empty($barcode_notification['msg']))
                {{$barcode_notification['msg']}}
                @elseif(session('barcode_notification.msg'))
                {{ session('barcode_notification.msg') }}
                @endif
                @if(session('barcode_notification.skuErrArray'))
                <ul>
                    @foreach (session('barcode_notification.skuErrArray') as $list)
                        <li>{{$list}}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action('ProductController@storeBarcode'), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%',]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="col-sm-8">
                        <div class="form-group">
                            {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                            {!! Form::file('import_barcode_csv', ['accept'=> '.xls', 'required' => 'required']); !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <br>
                        <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                    </div>
                </div>
            </div>

            {!! Form::close() !!}
            <br><br>
            <div class="row">
                
                <div class="col-md-6 col-sm-4">
                    <a href="{{ asset('files/import_add_barcode_csv_template.xls') }}" class="btn btn-success" download><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                </div>
            </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection
