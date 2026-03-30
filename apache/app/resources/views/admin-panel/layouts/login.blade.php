<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content=""> 
    <title>{{ config('app.name', 'ACGCommunity.com') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" type="text/css" href="{{ route('home') }}/assets/lib/perfect-scrollbar/css/perfect-scrollbar.css"/>
    <link rel="stylesheet" type="text/css" href="{{ route('home') }}/assets/lib/material-design-icons/css/material-design-iconic-font.min.css"/>
    <link rel="stylesheet" href="{{ route('home') }}/assets/css/app.css" type="text/css"/>
    <link href="{{ route('home') }}/assets/img/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
 
  </head>
  <body class="be-splash-screen">
    <div class="be-wrapper be-login">
      <div class="be-content">
        	@yield('content')
      </div>
    </div>
    <script src="{{ route('home') }}/assets/lib/jquery/jquery.min.js" type="text/javascript"></script>
    <script src="{{ route('home') }}/assets/lib/perfect-scrollbar/js/perfect-scrollbar.min.js" type="text/javascript"></script>
    <script src="{{ route('home') }}/assets/lib/bootstrap/dist/js/bootstrap.bundle.min.js" type="text/javascript"></script>
    <script src="{{ route('home') }}/assets/js/app.js" type="text/javascript"></script>
    <script type="text/javascript">
      $(document).ready(function(){
       
      	App.init();
      });
      
    </script>
  </body>
</html>