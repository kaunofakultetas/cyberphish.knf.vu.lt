<?php

// -----------------------------------------------------------
//  [*] Controller — the base of every controller
//
//  Laravel's routing controller with the three traits its
//  scaffolding adds: authorization helpers, job dispatch
//  and request validation — the validate method the
//  sign-in and sign-up forms rely on. The member side and
//  the admin panel build on it through a BaseController
//  each.
//
//  Used by:
//    - BaseController and Admin\BaseController — the bases
//      of the member side and the admin panel
//    - SettingsController, Auth\AdminLoginController and the
//      four unused Laravel stubs in Auth/
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;








// -----------------------------------------------------------
// Controller
// -----------------------------------------------------------
//
// The BaseController it extends is Laravel's own
// Illuminate\Routing\Controller under an alias — not the
// app's BaseController, which extends this class.
//
// Used by:
//   - every controller of the app, directly or through a
//     BaseController
// -----------------------------------------------------------

class Controller extends BaseController
{

    // authorize, dispatch and validate, for every controller
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
