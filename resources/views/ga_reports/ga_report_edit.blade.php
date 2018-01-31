@extends('layouts.app')

@section('content')
    <div class="panel-body">
        @include('common.errors')
        <form action="{{ url('/ga_report/'. $report->id) }}" method="POST" class="form-horizontal">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="report_name" class="col-sm-3 control-label">Report Name</label>
                <div class="col-sm-6">
                    <input type="text" name="report_name" id="report_name" class="form-control" value="{{
                    $report->report_name }}">
                </div>
            </div>
            <div class="form-group">
                <label for="report_start_date_range" class="col-sm-3 control-label">Report Start Date Range</label>
                <div class="col-sm-2">
                    {!! Form::input('date', 'report_start_date_range', Carbon::createFromFormat('Y-m-d',
                $report->report_start_date_range)->toDateString(), ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group">
                <label for="report_end_date_range" class="col-sm-3 control-label">Report End Date Range</label>
                <div class="col-sm-2">
                    {!! Form::input('date', 'report_end_date_range', Carbon::createFromFormat('Y-m-d',
                $report->report_end_date_range)->toDateString(), ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="form-group">
                <label for="report_schedule" class="col-sm-3 control-label">Report Schedule</label>
                <div class="col-sm-6">
                    <select id="report_schedule" name="report_schedule" class="form-control">
                        @foreach($schedules as $schedule)
                            <option @if($schedule->id == $report->report_schedule_id)
                                    selected="selected"
                                    @endif
                                    value="{{$schedule->id}}">{{$schedule->schedule_text}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="report_active" class="col-sm-3 control-label">Report Active</label>
                <div class="col-sm-1">
                    <input @if($report->report_active) checked="checked" @endif type="checkbox" name="report_active"
                           id="report_active"
                           class="form-check-input"
                           value="1">
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-default">
                        <i class="fa fa-plus"></i> Save
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
