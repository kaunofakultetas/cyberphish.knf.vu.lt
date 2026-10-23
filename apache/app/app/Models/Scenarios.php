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
//     simulation_report — the members' runs
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
}
