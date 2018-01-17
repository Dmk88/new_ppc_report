{{--@extends('layouts.app')--}}

{{--@section('content')--}}
{{--<div class="container">--}}
{{--<h2>WP Posts</h2>--}}
{{--@foreach ($posts as $post)--}}
{{--<p>Form data from google sheet {{ $post->id }}:</p>--}}
{{--<table class="table table-striped">--}}
{{--<thead>--}}
{{--<tr>--}}
{{--<th>ID</th>--}}
{{--<th>Post Nname</th>--}}
{{--<th>Post URL</th>--}}
{{--<th>WP ID</th>--}}
{{--<th>Clusters</th>--}}
{{--</tr>--}}
{{--</thead>--}}
{{--<tbody>--}}
{{--<tr class="">--}}
{{--<td>--}}
{{--{{ $post->id }}--}}
{{--</td>--}}
{{--<td>--}}
{{--{{ $post->post_name }}--}}
{{--</td>--}}
{{--<td>--}}
{{--{{ $post->post_name }}--}}
{{--</td>--}}
{{--<td>--}}
{{--{{ $post->post_url }}--}}
{{--</td>--}}
{{--<td>--}}
{{--{{ $post->post_wp_id }}--}}
{{--</td>--}}
{{--<td>--}}
{{--@foreach ($post->clusters as $cluster)--}}
{{--{{ $cluster->id }}--}}
{{--@endforeach--}}
{{--</td>--}}
{{--</tr>--}}
{{--</tbody>--}}
{{--</table>--}}
{{--@endforeach--}}
{{--</div>--}}
{{--@endsection--}}
@extends('layouts.app')

@push('stylesheet')
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">
@endpush

@section('content')
    <div class="container-fluid">
        <div class="panel-body">
            @include('common.errors')
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
//                {data: 'clusters'}
//                {
//                    "orderable": false,
//                    "searchable": false,
//                    "data": null,
//                    "id": 'id',
//                    "defaultContent": '<button class="delete_form_data_row btn btn-danger">Del</button>'
//                }
            ]
        });
//        $('#dateSearch').on('click', function () {
//            formTable.draw();
//        });
//        formTable.on('click', '.delete_form_data_row', function (e) {
//            e.preventDefault();
//            $.ajaxSetup({
//                headers: {
//                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//                }
//            });
//            $.ajax({
//                url: '/form_data/' + $(this).closest('tr').find('.id-elem').text(),
//                type: 'DELETE',
//                dataType: 'json',
//                data: {method: '_DELETE', submit: true}
//            }).always(function (data) {
//                formTable
//                        .row($(this).parents('tr'))
//                        .remove()
//                        .draw();
//            });
//        });
    });
</script>
@endpush
