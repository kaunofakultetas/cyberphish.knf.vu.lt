<?php

// -----------------------------------------------------------
//  [*] Scenarios — the phishing simulations
//
//  One bei_scenarios row per simulation, in one language: the
//  story the start page tells and the labels it shows. The
//  steps live in ScenariosOptions, the categories and
//  attributes in the two link tables, and the members' runs
//  in ScenariosUsers. The panel imports a simulation from a
//  spreadsheet.
//
//  Used by:
//    - SimulationsController (Http/Controllers/)
//    - Admin\SimulationsController,
//      Admin\SiteSettingsController,
//      Admin\ManagerMainController (Http/Controllers/Admin/)
//    - R_Exists (Http/Middleware/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// Scenarios
// -----------------------------------------------------------
//
// Columns: lang_id, descr, goal, source and actors (the
// story), image, and choose_type and attack_type — free
// text out of the import spreadsheet, shown as labels.
//
//   scopeOffered — only the simulations a member page offers
//
// Used by:
//   - SimulationsController — simulation_start (the start
//     page), check_if_all_simulations_finished (the
//     simulations of a language)
//   - R_Exists::handle — the sim_id check
//   - Admin\SimulationsController — index,
//     simulations_import, simulation_edit, simulation_update,
//     simulation_delete
//   - Admin\SiteSettingsController::SimulationsDownloadPdf —
//     the printable page of every simulation, HTML despite
//     the name
//   - Admin\ManagerMainController::dashboard,
//     simulation_report — the members' runs, and the offered
//     simulations of the dashboard's figures
// -----------------------------------------------------------

class Scenarios extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_scenarios";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // Empty: nothing mass-assigns this model
    protected $fillable = [
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // Empty: every column comes back uncast
    protected $casts = [
    ];






    // -----------------------------------------------------------
    // scopeOffered
    // -----------------------------------------------------------
    //
    // Only the simulations a member page offers: those a
    // category of their own language lists, as the category
    // pages list them (SimulationsController::
    // simulations_list). One filed under no category of its
    // language — an import whose sheet marked none, a
    // category deleted — is out of every member's reach, so
    // neither the badge, the progress nor the dashboard counts
    // it. The stored function bei_user_course_progress and
    // LMUsers::country_progress repeat this rule in SQL, and
    // must keep step with it.
    //
    // Used by:
    //   - SimulationsController::check_if_all_simulations_finished
    //   - Admin\ManagerMainController::dashboard
    // -----------------------------------------------------------

    public function scopeOffered($query)
    {
        return $query->whereExists(function ($in) {
            $in->select(DB::raw(1))
                ->from('bei_scenarios_in_category')
                ->join('bei_scenarios_category', 'bei_scenarios_category.id', '=', 'bei_scenarios_in_category.cat_id')
                ->whereColumn('bei_scenarios_in_category.scenario_id', 'bei_scenarios.id')
                ->whereColumn('bei_scenarios_in_category.lang_id', 'bei_scenarios.lang_id')
                ->whereColumn('bei_scenarios_category.lang_id', 'bei_scenarios.lang_id');
        });
    }
}
