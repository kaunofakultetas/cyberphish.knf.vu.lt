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

Route::get('/', 'HomeController@index')->name('home');

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');

Route::post('login', 'Auth\LoginController@login');

Route::get('register', 'Auth\RegisterController@register_form');

Route::post('register', array('before' => 'csrf', 'uses' => 'Auth\RegisterController@register'))->name('register');

Route::get('forgot-password', 'Auth\LoginController@ForgotPasswordForm');

Route::post('forgot-password', 'Auth\LoginController@forgotpassword')->name('forgot-password');

Route::get('cp/logout', 'Auth\LoginController@logout')->name('logout');

Route::get('cp/dashboard', 'ManagerMainController@dashboard')->name('dashboard')->middleware('auth:mem');

#Route::get('cp/change-pass', function () { return view('dialog.changepass'); })->middleware('auth:mem');

Route::post('cp/change-pass', array('before' => 'csrf', 'uses' => 'ManagerMainController@change_pass'))->middleware('auth:mem');

Route::get('verify/{verify_token}', 'Auth\RegisterController@verify');

Route::get('reset/{reset_token}', 'Auth\LoginController@reset_pass')->middleware('validate.reset');

Route::post('cp/change-username', array('before' => 'csrf', 'uses' => 'ManagerMainController@change_username'))->middleware('auth:mem');

Route::post('cp/change-fullname', array('before' => 'csrf', 'uses' => 'ManagerMainController@change_fullname'))->middleware('auth:mem');

#Route::get('news', 'NewsController@index')->name('news');

Route::get('{lang}/news', 'NewsController@index')->name('news');

#Route::get('news/{slug}/{uid}', 'NewsController@content')->middleware(['news.exists']);

Route::get('{lang}/news/{slug}/{uid}', 'NewsController@content')->middleware(['news.exists']);

#Route::get('page/{slug}/{uid}', 'InfoController@content')->middleware(['page.exists']);

Route::get('{lang}/page/{slug}/{uid}', 'InfoController@content')->middleware(['page.exists']);

#Route::get('cp/learning-material', 'LMController@index')->name('learning-material')->middleware('auth:mem');

Route::get('{lang}/cp/learning-material', 'LMController@index')->name('learning-material')->middleware('auth:mem');

#Route::get('learn/{slug}/{uid}', 'LMController@content')->middleware(['lm.exists']);

Route::get('{lang}/learn/{slug}/{uid}', 'LMController@content')->middleware(['lm.exists']);

Route::post('cp/mark_completed', array('before' => 'csrf', 'uses' => 'LMController@mark_completed'))->middleware('auth:mem');

Route::get('{lang}/self-evaluation-test/{cat_id}', 'LMController@self_evaluation_test')->middleware(['auth:mem', 'r_exists:lm_cat']);

Route::get('cp/se/{public_id}/{cat_id}', 'LMController@self_eval_test')->middleware(['auth:mem', 'finished.se', 'r_exists:lm_cat']);

Route::post('cp/se/{public_id}/{cat_id}', 'LMController@self_eval_test_save')->middleware(['auth:mem', 'finished.se']);

Route::get('cp/se_r/{public_id}', 'LMController@self_eval_test_results')->middleware(['auth:mem', 'r_exists:se_public_unfinished']);

Route::get('cp/self-evaluation-history', 'LMController@self_evaluation_history')->middleware(['auth:mem']);

Route::get('cp/simulations-history', 'SimulationsController@history')->middleware(['auth:mem']);

Route::get('{lang}/ranks/self-evaluation', 'RanksController@self_evaluation');

Route::get('lang/{lang}', 'SettingsController@lang_switch');

Route::get('{lang}/simulations', 'SimulationsController@categories')->middleware(['auth:mem']);

Route::get('{lang}/simulations/{name}/{uid}', 'SimulationsController@simulations_list')->middleware(['auth:mem', 'r_exists:sim_cat']);

Route::get('{lang}/simulation/{uid}', 'SimulationsController@simulation_start')->middleware(['auth:mem', 'r_exists:sim_id']);

Route::post('{lang}/simulation_progress/{uid}', 'SimulationsController@simulation_progress')->middleware(['auth:mem', 'r_exists:sim_id']);

Route::get('{lang}/simulation/progress/{public_id}', 'SimulationsController@simulation_do_stuff')->middleware(['auth:mem', 'r_exists:sim_public_id']);

Route::post('{lang}/simulation/progress/{public_id}', 'SimulationsController@simulation_do_stuff_save')->middleware(['auth:mem', 'r_exists:sim_public_id']);

Route::get('{lang}/ranks/simulations', 'RanksController@simulations');

Route::get('cp/badges', 'BadgesController@index')->middleware(['auth:mem']);

Route::get('{lang}/knowledge_test', 'KnowledgeTestController@index')->middleware(['auth:mem']);

Route::post('{lang}/knowledge_start', 'KnowledgeTestController@start')->middleware(['auth:mem']);

Route::get('{lang}/knowledge_test/progress/{public_id}', 'KnowledgeTestController@progress')->middleware(['auth:mem']);

Route::post('{lang}/knowledge_test/progress/{public_id}', 'KnowledgeTestController@progress_save')->middleware(['auth:mem']);

Route::get('cp/{lang}/certificate', 'CertController@download')->middleware(['auth:mem']);
