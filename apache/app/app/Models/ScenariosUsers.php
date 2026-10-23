<?php

// -----------------------------------------------------------
//  [*] ScenariosUsers — the members' runs of a simulation
//
//  One bei_users_scenarios row per run: a member starts a
//  simulation, walks its steps, and the run closes with the
//  points of the choices made. The run's random public id is
//  what its URLs carry.
//
//  Used by:
//    - SimulationsController (Http/Controllers/)
//    - Admin\SimulationsController,
//      Admin\ManagerMainController (Http/Controllers/Admin/)
//    - R_Exists (Http/Middleware/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ScenariosUsers
// -----------------------------------------------------------
//
// Columns: public_id, user_id, scenario_id, lang_id,
// finished (0 or 1), started, ended and points — the sum of
// the run's choices (ScenariosUsersOptions), written when
// the run ends. The best finished run per member and
// simulation feeds the ranking (ViewMaxPointsScenario).
//
// Used by:
//   - SimulationsController — simulations_list,
//     simulation_progress, gen_test_id, simulation_do_stuff,
//     simulation_do_stuff_save, history,
//     check_if_all_simulations_finished
//   - R_Exists::handle — the sim_public_id check: the run
//     exists and is the member's
//   - Admin\SimulationsController::simulation_delete
//   - Admin\ManagerMainController — dashboard,
//     simulations_history, simulation_report
// -----------------------------------------------------------

class ScenariosUsers extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_scenarios";
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
