<?php

// -----------------------------------------------------------
//  [*] web_admin — the routes of the admin panel
//
//  The panel under /admin-panel where managers run the
//  site. Signing in and out is open; every other route sits
//  behind auth.admin:man, which sends a visitor to the
//  panel's login form. Most also carry perms, for main
//  admins only: a country manager gets a 404 there and
//  keeps the dashboard and the member reports. The dialog
//  routes return the forms the panel's pages open in a
//  modal. One route hands over an action array with a
//  before csrf entry, a Laravel 4 filter that Laravel 13
//  ignores, as Laravel 7 did: the web group's
//  VerifyCsrfToken checks every POST anyway.
//
//  Open to visitors — signing in and out:
//
//    GET|POST  /admin-panel
//    GET       /admin-panel/logout
//
//  Every manager — the dashboard and the member reports:
//
//    GET       /admin-panel/dashboard
//    GET       /admin-panel/user/self_evaluation_history/…
//    GET       /admin-panel/user/simulations_history/…
//    GET       /admin-panel/user/simulation/report/{public_id}
//    GET       /admin-panel/user/knowledge_results/{public_id}
//
//  Main admins — their own password, the members, the site
//  languages, the managers and the other settings:
//
//    GET|POST  /admin-panel/change-pass
//    GET       /admin-panel/users
//    GET       /admin-panel/user/delete/{user_id}
//    GET       /admin-panel/user/status/{status_id}/{user_id}
//    GET       /admin-panel/settings/languages[/…]
//    GET|POST  /admin-panel/dialog/lang/…
//    GET       /admin-panel/settings/managers
//    GET       /admin-panel/settings/manager/…
//    GET       /admin-panel/dialog/manager/…
//    POST      /admin-panel/manager/…
//    GET       /admin-panel/settings/other
//    POST      /admin-panel/settings/…_save
//    GET       /admin-panel/settings/simulations_download_pdf
//
//  Main admins — news, information pages, the learning
//  material and its tests, the simulations:
//
//    GET|POST  /admin-panel/news[/…]
//    GET|POST  /admin-panel/information[/…]
//    GET|POST  /admin-panel/lm/…
//    GET|POST  /admin-panel/dialog/lm/…
//    GET|POST  /admin-panel/admin_simulations[/…]
//    GET|POST  /admin-panel/dialog/simulations/…
//    GET|POST  /admin-panel/dialog/simulation/…
//
//  Known bug (medium): every delete and status change of the
//  panel is a plain GET route — the member's delete and
//  status change (web_admin.php:96, 98) among them — which
//  the CSRF check never covers and the session cookie
//  follows on a top-level visit, so a link a signed-in admin
//  opens from a mail deletes; pinned as admin-get-deletes.
//  Fix: make them POST routes behind the CSRF check.
//
//  Used by:
//    - RouteServiceProvider::mapWebAdminRoutes — loads it
//      under the web middleware group, with no prefix, after
//      web_user.php
// -----------------------------------------------------------


use Illuminate\Support\Facades\Route;

// Signing in and out of the panel, open to visitors
Route::get('admin-panel', 'Auth\AdminLoginController@showLoginForm')->name('admin_login');

Route::post('admin-panel', 'Auth\AdminLoginController@login');

Route::get('admin-panel/logout', 'Auth\AdminLoginController@logout')->name('admin_logout');

// The dashboard, for every manager
Route::get('admin-panel/dashboard', 'Admin\ManagerMainController@dashboard')->name('admin_dashboard')->middleware(['auth.admin:man']);

// The signed-in admin's own password: the closure returns
// the dialog, whose form posts back to the same path
Route::get('admin-panel/change-pass', function () { return view('admin-panel.dialog.changepass'); })->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/change-pass', array('before' => 'csrf', 'uses' => 'Admin\ManagerMainController@change_pass'))->middleware(['auth.admin:man', 'perms']);

// The members: the list, deleting one, switching one on or
// off
Route::get('admin-panel/users', 'Admin\UsersController@index')->name('users')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/user/delete/{user_id}', 'Admin\UsersController@delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/user/status/{status_id}/{user_id}', 'Admin\UsersController@change_status')->middleware(['auth.admin:man', 'perms']);

// The site languages' list
Route::get('admin-panel/settings/languages', 'Admin\LanguagesController@index')->name('langs')->middleware(['auth.admin:man', 'perms']);

// Every language's simulations with their options, on one
// printable page — an HTML page, despite the path
Route::get('admin-panel/settings/simulations_download_pdf', 'Admin\SiteSettingsController@SimulationsDownloadPdf')->middleware(['auth.admin:man', 'perms']);

// The site languages, continued: deleting one, and the
// dialog that adds or edits one with its two saves
Route::get('admin-panel/settings/languages/delete/{uid}', 'Admin\LanguagesController@delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lang/new', 'Admin\LanguagesController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lang/edit/{uid?}', 'Admin\LanguagesController@form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lang/create', 'Admin\LanguagesController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lang/update', 'Admin\LanguagesController@update')->middleware(['auth.admin:man', 'perms']);

// News articles: the list, the form for a new or an existing
// article, deleting one, the form's two saves, and removing
// an article's picture. The list's name, news, is the
// public list's name too; registered later, this route
// takes it, so Admin\NewsController::create redirects here
// by it. The public {lang}/news routes of web_user.php
// answer the list and the per-article links first — the
// admin-news-shadowed bug documented there.
Route::get('admin-panel/news', 'Admin\NewsController@index')->name('news')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/new', 'Admin\NewsController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/edit/{uid?}', 'Admin\NewsController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/delete/{uid}', 'Admin\NewsController@delete')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/news/create', 'Admin\NewsController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/news/update', 'Admin\NewsController@update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/delete_picture/{uid}', 'Admin\NewsController@delete_picture')->middleware(['auth.admin:man', 'perms']);

// The managers: the list, the dialog for a new or an
// existing manager, deleting one, switching one on or off
Route::get('admin-panel/settings/managers', 'Admin\ManagersController@managers')->name('managers')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/manager/new', 'Admin\ManagersController@manager_form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/manager/edit/{uid?}', 'Admin\ManagersController@manager_form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/manager/delete/{uid}', 'Admin\ManagersController@manager_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/manager/status/{status_id}/{user_id}', 'Admin\ManagersController@manager_status')->middleware(['auth.admin:man', 'perms']);

// The other settings: the page, and its forms for the
// self-evaluation length, the about text and the knowledge
// test limits
Route::get('admin-panel/settings/other', 'Admin\SiteSettingsController@other')->name('other_settings')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/settings/self_eval_save', 'Admin\SiteSettingsController@self_eval_save')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/settings/about_us_save', 'Admin\SiteSettingsController@about_us_save')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/settings/knowledge_test_settings_save', 'Admin\SiteSettingsController@knowledge_test_settings_save')->middleware(['auth.admin:man', 'perms']);

// The two saves of the manager dialog
Route::post('admin-panel/manager/create', 'Admin\ManagersController@manager_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/manager/update', 'Admin\ManagersController@manager_update')->middleware(['auth.admin:man', 'perms']);

// Information pages: the list, the form for a new or an
// existing page, deleting one, and the form's two saves
Route::get('admin-panel/information', 'Admin\InfoController@index')->name('information')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/information/new', 'Admin\InfoController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/information/edit/{uid?}', 'Admin\InfoController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/information/delete/{uid}', 'Admin\InfoController@delete')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/information/create', 'Admin\InfoController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/information/update', 'Admin\InfoController@update')->middleware(['auth.admin:man', 'perms']);

// The learning material's categories: the list, the dialog
// for a new or an existing category with its two saves, and
// deleting one — categores is the spelling the list's
// delete link uses
Route::get('admin-panel/lm/categories', 'Admin\LMController@categories')->name('lm_categories')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/categories/{type}/{uid}', 'Admin\LMController@categories_form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lm/categories/create', 'Admin\LMController@categories_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lm/categories/update', 'Admin\LMController@categories_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/categores/delete/{uid}', 'Admin\LMController@categories_delete')->middleware(['auth.admin:man', 'perms']);

// The lessons: the list, the form for a new or an existing
// lesson with its two saves, and deleting one
Route::get('admin-panel/lm/content', 'Admin\LMController@index')->name('lm')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/content/{type}/{uid}', 'Admin\LMController@form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/content/create', 'Admin\LMController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/content/update', 'Admin\LMController@update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/content_delete/{uid}', 'Admin\LMController@delete')->middleware(['auth.admin:man', 'perms']);

// A lesson's additional files: the list, the dialog for a
// new or an existing file with its two saves, and deleting
// one
Route::get('admin-panel/lm/files/{uid}', 'Admin\LMController@additional_files')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/file/{type}/{uid}', 'Admin\LMController@files_form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/file/create', 'Admin\LMController@files_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/file/update', 'Admin\LMController@files_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/files/delete/{uid}', 'Admin\LMController@files_delete')->middleware(['auth.admin:man', 'perms']);

// A category's self-evaluation questions: the list, the
// import dialog and the import, and deleting, editing and
// saving one.
//
// Known bug (low): Admin\SelfevalController has no form
// method, so the question_{type} route (web_admin.php:233)
// answers a main admin with a 500; nothing links to it.
// Fix: remove the route.
// Pinned as selfeval-form-route.
Route::get('admin-panel/lm/self_eval/{uid}', 'Admin\SelfevalController@index')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/self_eval/question_{type}/{uid}', 'Admin\SelfevalController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/self_eval/import/{uid}', 'Admin\SelfevalController@import_question_dialog')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/self_eval/import', 'Admin\SelfevalController@import_questions')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/self_eval/delete/{uid}', 'Admin\SelfevalController@question_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/self_eval/edit/{uid}', 'Admin\SelfevalController@question_edit')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/self_eval/update/{uid}', 'Admin\SelfevalController@question_update')->middleware(['auth.admin:man', 'perms']);

// A category's knowledge test questions: the list, the
// import dialog and the import, and deleting, editing and
// saving one
Route::get('admin-panel/lm/knowledge_test/{uid}', 'Admin\KnowledgeController@index')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/knowledge_test/import/{uid}', 'Admin\KnowledgeController@import_question_dialog')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/knowledge_test/import', 'Admin\KnowledgeController@import_questions')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/knowledge_test/delete/{uid}', 'Admin\KnowledgeController@question_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/knowledge_test/edit/{uid}', 'Admin\KnowledgeController@question_edit')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/knowledge_test/update/{uid}', 'Admin\KnowledgeController@question_update')->middleware(['auth.admin:man', 'perms']);

// The simulations: the list; their categories and their
// attributes, each with a list, a dialog for a new or an
// existing one with its two saves, and a delete — categores
// again the spelling the delete link uses; the import of a
// language's simulations; and deleting, editing and saving
// one
Route::get('admin-panel/admin_simulations', 'Admin\SimulationsController@index')->name('simulations')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/admin_simulations/categories', 'Admin\SimulationsController@categories')->name('simulations_categories')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/simulations/categories/{type}/{uid}', 'Admin\SimulationsController@categories_form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/simulations/categories/create', 'Admin\SimulationsController@categories_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/simulations/categories/update', 'Admin\SimulationsController@categories_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/admin_simulations/categores/delete/{uid}', 'Admin\SimulationsController@categories_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/admin_simulations/attributes', 'Admin\SimulationsController@attributes')->name('simulations_attributes')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/simulations/attributes/{type}/{uid}', 'Admin\SimulationsController@attributes_form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/simulations/attributes/create', 'Admin\SimulationsController@attributes_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/simulations/attributes/update', 'Admin\SimulationsController@attributes_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/admin_simulations/attributes/delete/{uid}', 'Admin\SimulationsController@attributes_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/simulation/import/{lang_id}', 'Admin\SimulationsController@dialog_import_simulation')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/admin_simulations/import', 'Admin\SimulationsController@simulations_import')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/admin_simulations/delete/{uid}', 'Admin\SimulationsController@simulation_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/admin_simulations/edit/{uid}', 'Admin\SimulationsController@simulation_edit')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/admin_simulations/update/{uid}', 'Admin\SimulationsController@simulation_update')->middleware(['auth.admin:man', 'perms']);

// The member reports, for every manager: a member's
// self-evaluation and simulation history in one language,
// and the report of one simulation run
Route::get('admin-panel/user/self_evaluation_history/{lang_id}/{user_id}', 'Admin\ManagerMainController@self_evaluation_history')->middleware(['auth.admin:man']);

Route::get('admin-panel/user/simulations_history/{lang_id}/{user_id}', 'Admin\ManagerMainController@simulations_history')->middleware(['auth.admin:man']);

Route::get('admin-panel/user/simulation/report/{public_id}', 'Admin\ManagerMainController@simulation_report')->middleware(['auth.admin:man']);

// The dialog that files a simulation under categories, and
// its save
Route::get('admin-panel/dialog/simulation/cat/{lang_id}/{simulation_id}', 'Admin\SimulationsController@simulation_categories')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/simulation/cat/{lang_id}/{simulation_id}', 'Admin\SimulationsController@simulation_categories_update')->middleware(['auth.admin:man', 'perms']);

// The report of one knowledge test attempt, for every
// manager
Route::get('admin-panel/user/knowledge_results/{public_id}', 'Admin\ManagerMainController@knowledge_report')->middleware(['auth.admin:man']);
