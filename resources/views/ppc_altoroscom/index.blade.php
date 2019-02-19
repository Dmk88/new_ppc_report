<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    @stack('stylesheet')
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body>
<div id="app">
    <span class="center">
        This web application is designed to collect statistics from Google Adwords for an arbitrary date range.
        Select date range, then click Processing.
    </span>
    <nav class="navbar navbar-default navbar-static-top">
        <div class="container center">
            <a class="navbar-brand" href="https://docs.google.com/spreadsheets/d/1jynNGLB8thxGrmWgqyK3_w8XFY9CdBpjeKdWx_feYLM/edit#gid=0" target="_blank">
                Report ( altoros.com )
            </a>

            <div id="form-data">
            <form id="Adword-params" action="" method="POST">
                {{ csrf_field() }}
                <div class="navbar-brand">From: <input type="date" name="date-from" required></div>
                <div class="navbar-brand">To: <input type="date" name="date-to" required></div>
                <div class="navbar-brand"><button type="submit" name="action" value="save">Processing</button></div>
            </form>
            </div>
            </div>

    </nav>
    <div class="content-message center">
        {!! $message  !!}
    </div>
    <div class="overlay-loader" style="display: none">
        Please wait...
        <div class="loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    @yield('content')
</div>

<!-- Scripts -->
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/custom.js') }}"></script>
@stack('scripts')

</body>
</html>