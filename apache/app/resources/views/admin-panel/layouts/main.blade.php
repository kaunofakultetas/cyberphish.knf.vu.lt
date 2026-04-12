<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>{{ config('app.name', 'CYBERPHISH') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ env('APP_URL') }}/assets/img/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
    <link href="/static/css/fonts.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ env('APP_URL') }}/assets/lib/perfect-scrollbar/css/perfect-scrollbar.css"/>
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/lib/material-design-icons/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/static/css/materialdesignicons.min.css">
    <link rel="stylesheet" type="text/css" href="{{ env('APP_URL') }}/assets/lib/datetimepicker/css/bootstrap-datetimepicker.min.css"/>
    <link href="/static/css/summernote.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/app.css" type="text/css"/>
    <link rel="stylesheet" href="{{ env('APP_URL') }}/assets/css/custom.css" type="text/css"/>

  </head>
  <body>
<div class="be-wrapper be-fixed-sidebar">
      <nav class="navbar navbar-expand fixed-top be-top-header">
        <div class="container-fluid">
          <div class="be-navbar-header"><a class="navbar-brand" href="{{ env('APP_URL_ADMIN') }}/dashboard"></a>
          </div>
          <div class="page-title"><span></span></div>
          <div class="be-right-navbar">
            <ul class="nav navbar-nav float-right be-user-nav">
              <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" role="button" aria-expanded="false"><img src="{{ env('APP_URL') }}/assets/img/menu-img.png" style="border-radius:0!important;"><span class="user-name">{{ Auth::user()->email }}</span></a>
                <div class="dropdown-menu" role="menu">
                  <div class="user-info">
                    <div class="user-name"></div>
                    <div class="user-position online">{{ __('Online') }}</div>
                  </div>

                  <a class="dropdown-item" href="#" onClick="change_pass()"><span class="icon mdi mdi-account-key"></span>{{ __('Change password') }}</a>

                  <a class="dropdown-item" href="{{ env('APP_URL') }}/admin-panel/logout"><span class="icon mdi mdi-power"></span>{{ __('Logout') }}</a>
                </div>
              </li>
            </ul>

          </div>
        </div>
      </nav>

        @include('admin-panel.sidebar')

      	@yield('content')

     <div class="modal fade" id="mod" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button class="close" type="button" data-dismiss="modal" aria-hidden="true"><span class="mdi mdi-close"></span></button>
          </div>
          <div class="modal-body">
   				&nbsp;
          </div>
          <div class="modal-footer"></div>
        </div>
      </div>
    </div>
    <script src="{{ env('APP_URL') }}/assets/lib/jquery/jquery.min.js" type="text/javascript"></script>
    <script src="{{ env('APP_URL') }}/assets/lib/perfect-scrollbar/js/perfect-scrollbar.min.js" type="text/javascript"></script>
    <script src="{{ env('APP_URL') }}/assets/lib/bootstrap/dist/js/bootstrap.bundle.min.js" type="text/javascript"></script>
    <script src="{{ env('APP_URL') }}/assets/js/app.js" type="text/javascript"></script>
    <script src="{{ env('APP_URL') }}/assets/lib/jquery.sparkline/jquery.sparkline.min.js" type="text/javascript"></script>
    <script src="{{ env('APP_URL') }}/assets/lib/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
    <script src="/static/js/summernote.min.js"></script>
    <script src="{{ env('APP_URL') }}/js/window.js" type="text/javascript"></script>
    <script src="{{ env('APP_URL') }}/js/admn.js" type="text/javascript"></script>

    <script type="text/javascript">
      $(document).ready(function(){

      	App.init();


      });
    </script>

  </body>
</html>