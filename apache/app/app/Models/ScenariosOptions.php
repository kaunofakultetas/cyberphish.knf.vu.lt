<?php

// -----------------------------------------------------------
//  [*] ScenariosOptions — the choices of a simulation
//
//  One bei_scenarios_options row per choice a simulation
//  offers, stored as a tree: the choices of the first step
//  hang under parent 0, every later choice under the choice
//  that leads to it. A run follows one path down the tree
//  and ends where a choice has no children.
//
//  Used by:
//    - SimulationsController (Http/Controllers/)
//    - Admin\SimulationsController,
//      Admin\SiteSettingsController,
//      Admin\ManagerMainController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ScenariosOptions
// -----------------------------------------------------------
//
// Columns: scenario_id, parent_option_id (0 on the first
// step), level (the step, from 1), situation (the text),
// feedback, image, link, option_type and points. The schema
// pairs the types with points: 0 incorrect (0), 1
// semi-incorrect (50), 2 semi-correct (100), 3 correct
// (200).
//
// Used by:
//   - SimulationsController::step_options — the choices of
//     the current step, which simulation_do_stuff shows and
//     simulation_do_stuff_save accepts
//   - SimulationsController::simulation_do_stuff_save — the
//     chosen option's type, points and feedback
//   - Admin\SimulationsController — simulations_import,
//     simulation_edit, simulation_update, simulation_delete
//   - Admin\SiteSettingsController::SimulationsDownloadPdf —
//     the printable page of every simulation, HTML despite
//     the name
//   - Admin\ManagerMainController::simulation_report — a
//     member's run
// -----------------------------------------------------------

class ScenariosOptions extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_scenarios_options";
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
