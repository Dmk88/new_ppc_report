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
        <div class="Rtable Rtable--5cols" id="report">

            {{--<div class="Rtable-cell">--}}
                {{--<div class="summary-value"><h3><strong>Clusters</strong></h3></div>--}}
            {{--</div>--}}
            {{--<div class="Rtable-cell"><strong>Page Views</strong></div>--}}
            {{--<div class="Rtable-cell"><strong>Unique Page Views</strong></div>--}}
            {{--<div class="Rtable-cell"><strong>Bounce Rate</strong></div>--}}
            {{--<div class="Rtable-cell"><strong>Avg Session Duration</strong></div>--}}

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
                action: 'get_report'
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