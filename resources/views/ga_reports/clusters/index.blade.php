@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Clusters</h2>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </thead>
            <tbody>
            @foreach ($clusters as $cluster)
                <tr>
                    <td>
                        {{ $cluster->id }}
                    </td>
                    <td>
                        {{ $cluster->cluster_name }}
                    </td>
                    <td>
                        {{ $cluster->cluster_description }}
                    </td>
                    <td>
                        <form action="{{ url('ga_reports_clusters/'. $cluster->id . '/edit/') }}">
                            <button type="submit" class="btn btn-primary">Edit</button>
                        </form>
                    </td>
                    <td>
                        <form action="{{ url('ga_reports_clusters/'. $cluster->id) }}" method="post">
                            {{ csrf_field() }}
                            {{ method_field('DELETE') }}
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <form action="{{ url('ga_reports_cluster') }}" method="get">
            <button type="submit" class="btn btn-default">Add</button>
        </form>
    </div>
@endsection
