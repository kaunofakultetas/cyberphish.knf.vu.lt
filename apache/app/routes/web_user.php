<?php

// -----------------------------------------------------------
//  [*] web_user — the routes of the course site
//
//  Every page of the site a visitor or a member opens. The
//  home page, signing up and in, the links the mails carry,
//  the news and information pages, the lessons and the rank
//  tables are open to visitors; the rest of the course sits
//  behind auth:mem, which sends a visitor to the login
//  form. A {lang} segment that names one of the five site
//  languages switches the site to it (BaseController); any
//  other value keeps the session's language, so {lang}
//  matches every segment. Five routes hand over an action
//  array with a before csrf entry, a Laravel 4 filter that
//  Laravel 13 ignores, as Laravel 7 did: the web group's
//  VerifyCsrfToken checks every POST anyway.
//
//  Open to visitors — the home page, signing up and in, the
//  links the mails carry, the language switch:
//
//    GET       /
//    GET|POST  /login
//    GET|POST  /register
//    GET|POST  /forgot-password
//    GET       /verify/{verify_token}
//    GET       /reset/{reset_token}
//    GET       /cp/logout
//    GET       /lang/{lang}
//
//  Open to visitors — the news and information pages, the
//  lessons, the rank tables:
//
//    GET       /{lang}/news[/…]
//    GET       /{lang}/page/{slug}/{uid}
//    GET       /{lang}/learn/{slug}/{uid}
//    GET       /{lang}/ranks/self-evaluation
//    GET       /{lang}/ranks/simulations
//
//  Members — the dashboard and its account forms, the
//  badges, the course outline and its lessons' completion:
//
//    GET       /cp/dashboard
//    POST      /cp/change-pass
//    POST      /cp/change-username
//    POST      /cp/change-fullname
//    GET       /cp/badges
//    GET       /{lang}/cp/learning-material
//    POST      /cp/mark_completed
//
//  Members — the self-evaluations, the simulations, the
//  knowledge test and its certificate:
//
//    GET       /{lang}/self-evaluation-test/{cat_id}
//    GET|POST  /cp/se/{public_id}/{cat_id}
//    GET       /cp/se_r/{public_id}
//    GET       /cp/self-evaluation-history
//    GET       /{lang}/simulations[/…]
//    GET       /{lang}/simulation/{uid}
//    POST      /{lang}/simulation_progress/{uid}
//    GET|POST  /{lang}/simulation/progress/{public_id}
//    GET       /cp/simulations-history
//    GET       /{lang}/knowledge_test
//    POST      /{lang}/knowledge_start
//    GET|POST  /{lang}/knowledge_test/progress/{public_id}
//    GET       /cp/{lang}/certificate
//
//  Used by:
//    - RouteServiceProvider::mapWebUserRoutes — loads it
//      under the web middleware group, with no prefix,
//      before web_admin.php
// -----------------------------------------------------------


use Illuminate\Support\Facades\Route;

// The home page
Route::get('/', 'HomeController@index')->name('home');

// Signing in, signing up, asking for a reset link and
// signing out; each form posts back to its own path
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');

Route::post('login', 'Auth\LoginController@login');

Route::get('register', 'Auth\RegisterController@register_form');

Route::post('register', array('before' => 'csrf', 'uses' => 'Auth\RegisterController@register'))->name('register');

Route::get('forgot-password', 'Auth\LoginController@ForgotPasswordForm');

Route::post('forgot-password', 'Auth\LoginController@forgotpassword')->name('forgot-password');

Route::get('cp/logout', 'Auth\LoginController@logout')->name('logout');

// The member's dashboard and its password form, which sits
// on the dashboard and has no page of its own
Route::get('cp/dashboard', 'ManagerMainController@dashboard')->name('dashboard')->middleware('auth:mem');

Route::post('cp/change-pass', array('before' => 'csrf', 'uses' => 'ManagerMainController@change_pass'))->middleware('auth:mem');

// The links the mails carry: the sign-up mail's
// verification link, whose action checks its token itself —
// only a token one member holds switches anything on — and
// the reset mail's link, which mails a new password —
// validate.reset answers a token that no member, or more
// than one, holds with a 404.
Route::get('verify/{verify_token}', 'Auth\RegisterController@verify');

Route::get('reset/{reset_token}', 'Auth\LoginController@reset_pass')->middleware('validate.reset');

// The user name form of the dashboard, and the full name
// form of the knowledge test pages — the name the
// certificate prints
Route::post('cp/change-username', array('before' => 'csrf', 'uses' => 'ManagerMainController@change_username'))->middleware('auth:mem');

Route::post('cp/change-fullname', array('before' => 'csrf', 'uses' => 'ManagerMainController@change_fullname'))->middleware('auth:mem');

// The public news and information pages; news.exists and
// page.exists answer an unknown id with a 404. These pages,
// the lessons and the course outline lost their paths
// without a language when {lang} came in, but
// NewsController::index still builds the news list's page
// links on /news, which nothing answers since.
//
// Known bug (medium): {lang} takes any value and this file
// loads before web_admin.php, so {lang}/news
// (web_user.php:141) answers admin-panel/news, and
// {lang}/news/{slug}/{uid} (web_user.php:143) answers the
// panel's news edit, delete and delete_picture links: the
// panel shows the public list and articles, and no admin
// can edit or delete an article there; pinned as
// admin-news-shadowed. Fix: constrain {lang} on these two
// routes to two letters, which keeps the fallback for an
// unknown language.
// The public list has no route name: news names the
// panel's list (web_admin.php), as it did under Laravel 7,
// where a name's later route won — Laravel 13 keeps the
// first, which would turn Admin\NewsController's redirect
// after a new article into a 500.
Route::get('{lang}/news', 'NewsController@index');

Route::get('{lang}/news/{slug}/{uid}', 'NewsController@content')->middleware(['news.exists']);

Route::get('{lang}/page/{slug}/{uid}', 'InfoController@content')->middleware(['page.exists']);

// The learning material: the course outline (members only),
// a lesson (open to visitors; lm.exists answers an unknown
// lesson with a 404), and the lesson page's form that marks
// it completed
Route::get('{lang}/cp/learning-material', 'LMController@index')->name('learning-material')->middleware('auth:mem');

Route::get('{lang}/learn/{slug}/{uid}', 'LMController@content')->middleware(['lm.exists']);

Route::post('cp/mark_completed', array('before' => 'csrf', 'uses' => 'LMController@mark_completed'))->middleware('auth:mem');

// A self-evaluation test: the lesson page's button starts an
// attempt and sends the member to its questions; finished.se
// closes an attempt whose every question is answered and
// sends the member to the result. r_exists and finished.se
// answer an unknown category or another member's attempt
// with a 404.
Route::get('{lang}/self-evaluation-test/{cat_id}', 'LMController@self_evaluation_test')->middleware(['auth:mem', 'r_exists:lm_cat']);

Route::get('cp/se/{public_id}/{cat_id}', 'LMController@self_eval_test')->middleware(['auth:mem', 'finished.se', 'r_exists:lm_cat']);

Route::post('cp/se/{public_id}/{cat_id}', 'LMController@self_eval_test_save')->middleware(['auth:mem', 'finished.se']);

Route::get('cp/se_r/{public_id}', 'LMController@self_eval_test_results')->middleware(['auth:mem', 'r_exists:se_public_unfinished']);

// The member's past self-evaluations and simulation runs
Route::get('cp/self-evaluation-history', 'LMController@self_evaluation_history')->middleware(['auth:mem']);

Route::get('cp/simulations-history', 'SimulationsController@history')->middleware(['auth:mem']);

// The self-evaluation rank table, open to visitors
Route::get('{lang}/ranks/self-evaluation', 'RanksController@self_evaluation');

// The language switch of the menu; anything but the five
// site languages is a 400
Route::get('lang/{lang}', 'SettingsController@lang_switch');

// The simulations: the categories, a category's
// simulations, a simulation's start page, where the member
// picks feedback after each step or at the end, the start
// of a run, and the run step by step. r_exists answers an
// unknown category, an unknown simulation or another
// member's run with a 404.
Route::get('{lang}/simulations', 'SimulationsController@categories')->middleware(['auth:mem']);

Route::get('{lang}/simulations/{name}/{uid}', 'SimulationsController@simulations_list')->middleware(['auth:mem', 'r_exists:sim_cat']);

Route::get('{lang}/simulation/{uid}', 'SimulationsController@simulation_start')->middleware(['auth:mem', 'r_exists:sim_id']);

Route::post('{lang}/simulation_progress/{uid}', 'SimulationsController@simulation_progress')->middleware(['auth:mem', 'r_exists:sim_id']);

Route::get('{lang}/simulation/progress/{public_id}', 'SimulationsController@simulation_do_stuff')->middleware(['auth:mem', 'r_exists:sim_public_id']);

Route::post('{lang}/simulation/progress/{public_id}', 'SimulationsController@simulation_do_stuff_save')->middleware(['auth:mem', 'r_exists:sim_public_id']);

// The simulation rank table, open to visitors
Route::get('{lang}/ranks/simulations', 'RanksController@simulations');

// The member's badges
Route::get('cp/badges', 'BadgesController@index')->middleware(['auth:mem']);

// The knowledge test: its page, the start of an attempt,
// and the attempt's questions and answers — the controller
// answers an attempt that is not the member's own with a
// 404.
Route::get('{lang}/knowledge_test', 'KnowledgeTestController@index')->middleware(['auth:mem']);

Route::post('{lang}/knowledge_start', 'KnowledgeTestController@start')->middleware(['auth:mem']);

Route::get('{lang}/knowledge_test/progress/{public_id}', 'KnowledgeTestController@progress')->middleware(['auth:mem']);

Route::post('{lang}/knowledge_test/progress/{public_id}', 'KnowledgeTestController@progress_save')->middleware(['auth:mem']);

// The PDF certificate, for a member whose knowledge test in
// that language scored above 74 and who has set a full
// name; anyone else gets a 404
Route::get('cp/{lang}/certificate', 'CertController@download')->middleware(['auth:mem']);
