@extends('layouts.app')

@push('stylesheet')
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">
@endpush

@section('content')
    <div class="container">
        <div class="panel-body">
            @include('common.errors')
            <div class="form-horizontal">
                {{ csrf_field() }}
            </div>
        </div>
        <a href="javascript:void(0);" class="btn btn-primary" id="get_report">Get Report</a>
    </div>
@stop

@push('scripts')
<script>
    $(function () {
        $('#app').on('click', '#get_report', function (e) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var dataObj = {
                action: 'get_report'
            };
            $.ajax({
                url: '/ga_reports',
                type: 'POST',
                contentType: "json",
                processData: false,
                data: JSON.stringify(dataObj)
            }).always(function (data) {
                var report = JSON.parse(data);
                console.log(report);
            });
        });
    });
</script>
@endpush