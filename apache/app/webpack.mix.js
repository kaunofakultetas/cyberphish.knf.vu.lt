// -----------------------------------------------------------
//  [*] webpack.mix.js — Laravel Mix's asset build, unused
//
//  Laravel's stock Mix file: it would bundle
//  resources/js/app.js into public/js and compile
//  resources/sass/app.scss into public/css. Nothing runs
//  it — the image has no Node and the Dockerfile no build
//  step — and the public/js/app.js and public/css/app.css
//  that public/mix-manifest.json lists are loaded only by
//  admin-panel/layouts/app.blade.php, a layout no view
//  extends.
//
//  Used by:
//    - nothing runs it at the moment (the dev and
//      production scripts of package.json would)
// -----------------------------------------------------------

const mix = require('laravel-mix');


mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css');
