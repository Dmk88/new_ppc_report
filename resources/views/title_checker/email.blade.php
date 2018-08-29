<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        .urls {
            padding-top: 15px;
            border-spacing: 0;
            border-collapse: collapse;
            border: 1px solid black;
        }
        .urls td {
            padding: 5px;
            border: 1px solid black;
        }
        .urls th {
            background-color: #2a46b2;
            color: white;
            padding: 5px;
            border: 1px solid black;
        }
        .urls tr{
            background-color: #fff;
        }
        .urls tr:nth-child(odd) {
            background-color: #eaebff;
        }

    </style>
</head>
<body>
<h2>Titles checked on {{ date('F d, Y') }}</h2>
@if ($error)
    <p class="error">Following error occurred: {{ $error }}</p>
@else
    <span class="info">{{ count($urlsToCheck) }} pages were checked, found {{ count($urlsWithErrors) }} errors</span>
@endif
@if (count($urlsWithErrors))

    <table class="urls">
        <tr>
            <th>#</th>
            <th>URL</th>
            <th>Expected title</th>
            <th>Actual title</th>
        </tr>
        @foreach($urlsWithErrors as $url => $titles)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $url }}</td>
                <td>{{ $titles[1] }}</td>
                <td>{{ $titles[0] ? $titles[0] : 'Error loading page' }}</td>
            </tr>
        @endforeach
    </table>
@endif
</body>
</html>