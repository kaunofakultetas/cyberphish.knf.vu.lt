<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>{{ config('app.name', 'CYBERPHISH') }}</title>

    <!-- Custom fonts for this template-->
    <link href="{{ env('APP_URL') }}/m_assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="/static/css/fonts.css" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ env('APP_URL') }}/m_assets/css/sb-admin-2.css?{{ time() }}" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

         @yield('content')

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ env('APP_URL') }}/m_assets/vendor/jquery/jquery.min.js"></script>
    <script src="{{ env('APP_URL') }}/m_assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ env('APP_URL') }}/m_assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ env('APP_URL') }}/m_assets/js/sb-admin-2.min.js"></script>
    {!! NoCaptcha::renderJs(Session::get('lang')) !!}

</body>

</html>