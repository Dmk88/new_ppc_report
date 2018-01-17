@extends('layouts.app')

@section('content')
    <div class="panel-body">
        @include('common.errors')
        <form action="{{ url('ga_reports_clusters/'. $cluster->id) }}" method="POST" class="form-horizontal">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="cluster_name" class="col-sm-3 control-label">Name</label>
                <div class="col-sm-6">
                    <input type="text" name="cluster_name" id="cluster_name" class="form-control" value="{{
                    $cluster->cluster_name }}">
                </div>
            </div>
            <div class="form-group">
                <label for="cluster_description" class="col-sm-3 control-label">Description</label>
                <div class="col-sm-6">
                    <input type="text" name="cluster_description" id="cluster_description" class="form-control"
                           value="{{ $cluster->cluster_description }}">
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
