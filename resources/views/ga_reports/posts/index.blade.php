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
            ]
        });
        formTable.on('change', '.post-cluster-block input', function (e) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var dataObj = {
                cluster: $(this).prop('value'),
                value: $(this).prop('checked')
            };
            $.ajax({
                url: '/ga_reports_posts/change_post_cluster/' + $(this).attr('data-post'),
                type: 'POST',
                contentType: "json",
                processData: false,
                data: JSON.stringify(dataObj)
            }).always(function (data) {
                formTable.draw();
            });
        });
    });
</script>
@endpush
