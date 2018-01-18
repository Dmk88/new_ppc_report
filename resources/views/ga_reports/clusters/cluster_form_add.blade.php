@extends('layouts.app')

@section('content')
    <div class="panel-body">
        <div class="container">
            <h2>New Cluster</h2>
        </div>
        @include('common.errors')
        <form action="{{ url('ga_reports_clusters/') }}" method="POST" class="form-horizontal">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="form_name" class="col-sm-3 control-label">Name</label>
                <div class="col-sm-6">
                    <input type="text" name="cluster_name" id="form_name" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label for="portal_id" class="col-sm-3 control-label">Description</label>
                <div class="col-sm-6">
                    <input type="text" name="cluster_description" id="portal_id" class="form-control">
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
