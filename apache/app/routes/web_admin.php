<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('admin-panel', 'Auth\AdminLoginController@showLoginForm')->name('admin_login');

Route::post('admin-panel', 'Auth\AdminLoginController@login');

Route::get('admin-panel/logout', 'Auth\AdminLoginController@logout')->name('admin_logout');

Route::get('admin-panel/dashboard', 'Admin\ManagerMainController@dashboard')->name('admin_dashboard')->middleware(['auth.admin:man']);

Route::get('admin-panel/change-pass', function () { return view('admin-panel.dialog.changepass'); })->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/change-pass', array('before' => 'csrf', 'uses' => 'Admin\ManagerMainController@change_pass'))->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/users', 'Admin\UsersController@index')->name('users')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/user/delete/{user_id}', 'Admin\UsersController@delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/user/status/{status_id}/{user_id}', 'Admin\UsersController@change_status')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/languages', 'Admin\LanguagesController@index')->name('langs')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/simulations_download_pdf', 'Admin\SiteSettingsController@SimulationsDownloadPdf')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/languages/delete/{uid}', 'Admin\LanguagesController@delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lang/new', 'Admin\LanguagesController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lang/edit/{uid?}', 'Admin\LanguagesController@form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lang/create', 'Admin\LanguagesController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lang/update', 'Admin\LanguagesController@update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news', 'Admin\NewsController@index')->name('news')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/new', 'Admin\NewsController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/edit/{uid?}', 'Admin\NewsController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/delete/{uid}', 'Admin\NewsController@delete')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/news/create', 'Admin\NewsController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/news/update', 'Admin\NewsController@update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/news/delete_picture/{uid}', 'Admin\NewsController@delete_picture')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/managers', 'Admin\ManagersController@managers')->name('managers')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/manager/new', 'Admin\ManagersController@manager_form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/manager/edit/{uid?}', 'Admin\ManagersController@manager_form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/manager/delete/{uid}', 'Admin\ManagersController@manager_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/manager/status/{status_id}/{user_id}', 'Admin\ManagersController@manager_status')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/settings/other', 'Admin\SiteSettingsController@other')->name('other_settings')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/settings/self_eval_save', 'Admin\SiteSettingsController@self_eval_save')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/settings/about_us_save', 'Admin\SiteSettingsController@about_us_save')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/settings/knowledge_test_settings_save', 'Admin\SiteSettingsController@knowledge_test_settings_save')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/manager/create', 'Admin\ManagersController@manager_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/manager/update', 'Admin\ManagersController@manager_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/information', 'Admin\InfoController@index')->name('information')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/information/new', 'Admin\InfoController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/information/edit/{uid?}', 'Admin\InfoController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/information/delete/{uid}', 'Admin\InfoController@delete')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/information/create', 'Admin\InfoController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/information/update', 'Admin\InfoController@update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/categories', 'Admin\LMController@categories')->name('lm_categories')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/categories/{type}/{uid}', 'Admin\LMController@categories_form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lm/categories/create', 'Admin\LMController@categories_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/lm/categories/update', 'Admin\LMController@categories_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/categores/delete/{uid}', 'Admin\LMController@categories_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/content', 'Admin\LMController@index')->name('lm')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/content/{type}/{uid}', 'Admin\LMController@form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/content/create', 'Admin\LMController@create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/content/update', 'Admin\LMController@update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/content_delete/{uid}', 'Admin\LMController@delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/files/{uid}', 'Admin\LMController@additional_files')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/file/{type}/{uid}', 'Admin\LMController@files_form')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/file/create', 'Admin\LMController@files_create')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/file/update', 'Admin\LMController@files_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/files/delete/{uid}', 'Admin\LMController@files_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/self_eval/{uid}', 'Admin\SelfevalController@index')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/self_eval/question_{type}/{uid}', 'Admin\SelfevalController@form')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/self_eval/import/{uid}', 'Admin\SelfevalController@import_question_dialog')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/self_eval/import', 'Admin\SelfevalController@import_questions')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/self_eval/delete/{uid}', 'Admin\SelfevalController@question_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/self_eval/edit/{uid}', 'Admin\SelfevalController@question_edit')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/self_eval/update/{uid}', 'Admin\SelfevalController@question_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/knowledge_test/{uid}', 'Admin\KnowledgeController@index')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/dialog/lm/knowledge_test/import/{uid}', 'Admin\KnowledgeController@import_question_dialog')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/knowledge_test/import', 'Admin\KnowledgeController@import_questions')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/knowledge_test/delete/{uid}', 'Admin\KnowledgeController@question_delete')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/lm/knowledge_test/edit/{uid}', 'Admin\KnowledgeController@question_edit')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/lm/knowledge_test/update/{uid}', 'Admin\KnowledgeController@question_update')->middleware(['auth.admin:man', 'perms']);

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

Route::get('admin-panel/user/self_evaluation_history/{lang_id}/{user_id}', 'Admin\ManagerMainController@self_evaluation_history')->middleware(['auth.admin:man']);

Route::get('admin-panel/user/simulations_history/{lang_id}/{user_id}', 'Admin\ManagerMainController@simulations_history')->middleware(['auth.admin:man']);

Route::get('admin-panel/user/simulation/report/{public_id}', 'Admin\ManagerMainController@simulation_report')->middleware(['auth.admin:man']);

Route::get('admin-panel/dialog/simulation/cat/{lang_id}/{simulation_id}', 'Admin\SimulationsController@simulation_categories')->middleware(['auth.admin:man', 'perms']);

Route::post('admin-panel/dialog/simulation/cat/{lang_id}/{simulation_id}', 'Admin\SimulationsController@simulation_categories_update')->middleware(['auth.admin:man', 'perms']);

Route::get('admin-panel/user/knowledge_results/{public_id}', 'Admin\ManagerMainController@knowledge_report')->middleware(['auth.admin:man']);


























