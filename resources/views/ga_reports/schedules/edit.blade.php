@extends('layouts.app')

@section('content')
    <div class="panel-body">
        @include('common.errors')
        <form action="{{ url('/ga_reports/schedule/'. $schedule->id) }}" method="POST" class="form-horizontal">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="schedule_text" class="col-sm-3 control-label">Schedule</label>
                <div class="col-sm-6">
                    <input type="text" name="schedule_text" id="schedule_text" class="form-control" value="{{
                    $schedule->schedule_text }}">
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
