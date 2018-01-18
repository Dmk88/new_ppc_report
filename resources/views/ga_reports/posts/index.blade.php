{{--@extends('layouts.app')--}}

{{--@push('stylesheet')--}}
{{--<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">--}}
{{--@endpush--}}

{{--@section('content')--}}
{{--<table class="table table-bordered" id="posts-data-table">--}}
{{--<thead>--}}
{{--<tr>--}}
{{--<th>ID</th>--}}
{{--<th>Post Name</th>--}}
{{--<th>Post URL</th>--}}
{{--<th>WP ID</th>--}}
{{--<th>Clusters</th>--}}
{{--<th>GA Report</th>--}}
{{--</tr>--}}
{{--</thead>--}}
{{--</table>--}}
{{--@stop--}}

{{--@push('scripts')--}}
{{--<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.15/js/jquery.dataTables.js"></script>--}}
{{--<script>--}}
{{--$(function() {--}}
{{--$('#posts-data-table').DataTable({--}}
{{--processing: true,--}}
{{--serverSide: true,--}}
{{--ajax: '{!! route('datatables') !!}',--}}
{{--columns: [--}}
{{--{ data: 'id', name: 'id' },--}}
{{--{ data: 'name', name: 'name' },--}}
{{--{ data: 'email', name: 'email' },--}}
{{--{ data: 'created_at', name: 'created_at' },--}}
{{--{ data: 'updated_at', name: 'updated_at' }--}}
{{--]--}}
{{--});--}}
{{--});--}}
{{--</script>--}}
{{--@endpush--}}


@extends('layouts.app')

@push('stylesheet')
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">
@endpush

@section('content')
    <div class="container-fluid">
        <div class="panel-body">
            @include('common.errors')
            <div class="form-horizontal">
                {{ csrf_field() }}
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped" id="posts-data-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Post Name</th>
                    <th>Post URL</th>
                    <th>WP ID</th>
                    <th>Clusters</th>
                </tr>
                </thead>
            </table>
        </div>
        {{--@if(!$flags['grab'])--}}
        {{--<div class="panel-body">--}}
        {{--{{ Form::open(array('url' => route( 'form_data.pushToHS', [ 'id' => $google_doc->id ]) )) }}--}}
        {{--{{ Form::submit('Push To HS', array('class' => 'btn btn-block btn-success')) }}--}}
        {{--{{ Form::close() }}--}}
        {{--</div>--}}
        {{--@endif--}}
    </div>
@endsection

@push('scripts')
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.15/js/jquery.dataTables.js"></script>
<script>
    $(function () {
        var formTable = $('#posts-data-table').DataTable({
            autoWidth: false,
            responsive: true,
            processing: true,
            serverSide: true,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
            ajax: {
                url: '{!! route( 'ga_reports.posts.form_data') !!}'
            },
            columns: [
                {data: 'id', class: 'id-elem'},
                {data: 'post_name'},
                {data: 'post_url'},
                {data: 'post_wp_id'},
                {data: 'clusters', name: 'clusters.id'}
//                {
//                    "orderable": false,
//                    "searchable": false,
//                    "data": null,
//                    "id": 'id',
//                    "defaultContent": '<button class="delete_form_data_row btn btn-danger">Del</button>'
//                }
            ]
        });
        formTable.on('change', '.post-cluster-block input', function (e) {
            console.log('push');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var dataObj = {
                cluster: $(this).prop('value'),
                value: $(this).prop('checked')
            };
            console.log(JSON.stringify(dataObj));
            $.ajax({
                url: '/ga_reports_posts/change_post_cluster/' + $(this).attr('data-post'),
                type: 'POST',
                contentType: "json",
                processData: false,
                data: JSON.stringify(dataObj)
            }).always(function (data) {
//                formTable.draw();
            });
        });
    });
</script>
@endpush
