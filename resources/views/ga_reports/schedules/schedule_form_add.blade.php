@extends('layouts.app')

@section('content')
    <div class="panel-body">
        <div class="container">
            <h2>New Schedule</h2>
        </div>
        @include('common.errors')
        <form action="{{ url('/ga_reports/schedule') }}" method="POST" class="form-horizontal">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="form_name" class="col-sm-3 control-label">Schedule</label>
                <div class="col-sm-6">
                    <input type="text" name="schedule_text" id="form_name" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-default">
                        <i class="fa fa-plus"></i> Add
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
