<!DOCTYPE html>
<html lang="en-US">
  <head>
    <meta charset="utf-8" />
  </head>
  <body>
    <h2>{{ __('main.your_new_pass') }}</h2>
    <p>{{ $new_pass }}</p>
    <br>
    <p>{{ __('main.login_url') }}: <a href="{{ env('APP_URL') }}" target="_blank">{{ env('APP_URL') }}</a></p>

  </body>
</html>