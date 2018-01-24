@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Schedules</h2>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Schedule</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </thead>
            <tbody>
            @foreach ($schedules as $schedule)
                <tr>
                    <td>
                        {{ $schedule->id }}
                    </td>
                    <td>
                        {{ $schedule->schedule_text }}
                    </td>
                    <td>
                        <form action="{{ url('/ga_reports/schedule/'. $schedule->id . '/edit/') }}">
                            <button type="submit" class="btn btn-primary">Edit</button>
                        </form>
                    </td>
                    <td>
                        <form action="{{ url('/ga_reports/schedule/'. $schedule->id) }}" method="post">
                            {{ csrf_field() }}
                            {{ method_field('DELETE') }}
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <form action="{{ url('/ga_reports/schedule') }}" method="get">
            <button type="submit" class="btn btn-default">Add</button>
        </form>
    </div>
@endsection
