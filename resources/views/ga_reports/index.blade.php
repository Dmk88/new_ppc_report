@extends('layouts.app')

@push('stylesheet')
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">
@endpush

@section('content')
    <div class="container">
        <h2>Reports</h2>
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Start Date Range</th>
                    <th>End Date Range</th>
                    <th>Schedule</th>
                    <th>Acticve</th>
                    <th></th>
                    <th></th>
                    <th></th>
                </thead>
                <tbody>
                @foreach ($reports as $report)
                    <tr>
                        <td>
                            {{ $report->report_name }}
                        </td>
                        <td>
                            {{ $report->report_start_date_range }}
                        </td>
                        <td>
                            {{ $report->report_end_date_range }}
                        </td>
                        <td>@if($report->report_schedule)
                                {{ $report->report_schedule->schedule_text }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            {{ $report->report_active }}
                        </td>
                        <td>
                            <form action="{{ url('ga_report/'. $report->id) }}">
                                <button type="submit" class="btn btn-success">View</button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ url('ga_report/'. $report->id . '/edit/') }}">
                                <button type="submit" class="btn btn-primary">Edit</button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ url('ga_report/'. $report->id) }}" method="post">
                                {{ csrf_field() }}
                                {{ method_field('DELETE') }}
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <form action="{{ url('ga_report') }}" method="get">
                <button type="submit" class="btn btn-default">Add</button>
            </form>
        </div>
    </div>
    <div class="container">
        <h2>Custom real-time Report</h2>
        <div class="panel-body">
            @include('common.errors')
            <div class="form-horizontal">
                {{ csrf_field() }}
            </div>
        </div>
        <div class="form-inline">
            <div class="input-group" id="datepicker">
                <span class="input-group-addon">Date Range:</span>
                {!! Form::input('date', 'start_date', Carbon::now()->format('Y-m-d'), ['class' => 'form-control']) !!}
                <span class="input-group-addon">to</span>
                {!! Form::input('date', 'end_date', Carbon::now()->addDays(1)->format('Y-m-d'), ['class' => 'form-control']) !!}
            </div>
            <a href="javascript:void(0);" class="btn btn-primary" id="get_report">Get Report</a>
        </div>
        <div class="Rtable Rtable--5cols" id="report">
        </div>
    </div>
    <img src="/images/loading.gif" id="loading-indicator" style="display:none"/>
@stop

@push('scripts')
<script>
    $(function () {
        $('#app').on('click', '#get_report', function (e) {
            $('#loading-indicator').show();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var dataObj = {
                action: 'get_report',
                start_date: $('input[name="start_date"]').val(),
                end_date: $('input[name="end_date"]').val()
            };
            $.ajax({
                url: '/ga_reports',
                type: 'POST',
                contentType: "json",
                processData: false,
                data: JSON.stringify(dataObj)
            }).always(function (data) {
                $('#loading-indicator').hide();
                var report = JSON.parse(data);
                if (report.message == 'success') {
                    var cluster_html_1 = '',
                            cluster_html_2 = '',
                            cluster_html_3 = '',
                            cluster_html_4 = '',
                            cluster_html_5 = '';
                    $('#report').html(' <div class="Rtable-cell"> <div ' +
                            'class="summary-value"><h3><strong>Clusters</strong></h3></div></div><div class="Rtable-cell"><strong>Page Views</strong></div><div class="Rtable-cell"><strong>Unique Page Views</strong></div><div class="Rtable-cell"><strong>Bounce Rate</strong></div><div class="Rtable-cell"><strong>Avg ' +
                            'Session Duration</strong></div>');
                    $.each(report.clusters, function (index, cluster) {
                        cluster_html_1 += '<div class="Rtable-cell"><div class="summary-value"><h3>' + cluster
                                        .cluster + '</h3></div>';
                        cluster_html_2 += '<div class="Rtable-cell"><div class="summary-value">' + cluster.summary.pageviews + '</div>';
                        cluster_html_3 += '<div class="Rtable-cell"><div class="summary-value">' + cluster.summary.uniquePageviews + '</div>';
                        cluster_html_4 += '<div class="Rtable-cell"><div class="summary-value">' + cluster.summary.bounceRate + '</div>';
                        cluster_html_5 += '<div class="Rtable-cell"><div class="summary-value">' + cluster.summary.avgSessionDuration + '</div>';
                        $.each(cluster.source, function (index, source) {
                            cluster_html_1 += '<div>' + index + '</div>';
                            cluster_html_2 += '<div>' + source.pageviews + '</div>';
                            cluster_html_3 += '<div>' + source.uniquePageviews + '</div>';
                            cluster_html_4 += '<div>' + source.bounceRate + '</div>';
                            cluster_html_5 += '<div>' + source.avgSessionDuration + '</div>';
                        });
                        cluster_html_1 += '</div>';
                        cluster_html_2 += '</div>';
                        cluster_html_3 += '</div>';
                        cluster_html_4 += '</div>';
                        cluster_html_5 += '</div>';
                        $('#report').append(cluster_html_1 + cluster_html_2 + cluster_html_3 +
                                cluster_html_4 +
                                cluster_html_5);
                        cluster_html_1 = '';
                        cluster_html_2 = '';
                        cluster_html_3 = '';
                        cluster_html_4 = '';
                        cluster_html_5 = '';
                    });

                    console.log(report);
                } else {
                    alert('Error on server side');
                }
            });
        });
    });
</script>
@endpush