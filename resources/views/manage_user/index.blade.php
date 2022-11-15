@extends('layouts.app')
@section('title', __('user.users'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('user.users')
            <small>@lang('user.manage_users')</small>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('role', __('user.roles') . ':') !!}
                            {!! Form::select('role', $roles, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'role_filter',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3" id="location_filter">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('user.all_users')])
                    @can('user.create')
                        @slot('tool')
                            <div class="box-tools">
                                <a class="btn btn-block btn-primary" href="{{ action('ManageUserController@create') }}">
                                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                            </div>
                        @endslot
                    @endcan
                    @can('user.view')
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="users_table">
                                <thead>
                                    <tr>
                                        <th>@lang('business.username')</th>
                                        <th>@lang('user.name')</th>
                                        <th>@lang('user.role')</th>
                                        <th>@lang('business.email')</th>
                                        <th>@lang('user.location_access')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    @endcan
                @endcomponent

                <div class="modal fade user_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
                </div>
            </div>
        </div>


    </section>
    <!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">
    //Roles table
    $(document).ready( function(){
        var users_table = $('#users_table').DataTable({
                    processing: true,
                    serverSide: true,
                    // ajax: '/users',
                    ajax: {
                        url: '/users',
                        data: function (d) {
                            if ($('#role_filter').length) {
                                d.role = $('#role_filter').val();
                            }
                            if ($('#location_id').length) {
                                d.location_id = $('#location_id').val();
                            }

                            d = __datatable_ajax_callback(d);
                        },
                    },
                    columnDefs: [ {
                        "targets": [4],
                        "orderable": false,
                        "searchable": false
                    } ],
                    "columns":[
                        {"data":"username"},
                        {"data":"full_name"},
                        {"data":"role"},
                        {"data":"email"},
                        {"data":"location_access"},
                        {"data":"action"}
                    ]
                });
        $(document).on('click', 'button.delete_user_button', function(){
            swal({
              title: LANG.sure,
              text: LANG.confirm_delete_user,
              icon: "warning",
              buttons: true,
              dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        data: data,
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                users_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
             });
        });
        
        $(document).on(
            'change',
            '#role_filter,#location_id',
            function () {
                users_table.ajax.reload();
            }
        );
    });
    
</script>
@endsection
