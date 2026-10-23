<?php

// -----------------------------------------------------------
//  [*] ScenariosUsersOptions — the choices made in a run
//
//  One bei_users_scenarios_options row per choice a member
//  made in a simulation run, with the option's type and
//  points copied in. The last row of a run tells which step
//  comes next; the sum of its points is the run's score.
//
//  Used by:
//    - SimulationsController (Http/Controllers/)
//    - Admin\SimulationsController,
//      Admin\ManagerMainController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ScenariosUsersOptions
// -----------------------------------------------------------
//
// Columns: user_id, user_scenario_id and
// user_scenario_public_id (the run, in ScenariosUsers),
// scenario_id, option_id (bei_scenarios_options),
// option_type and points.
//
// Used by:
//   - SimulationsController::simulation_do_stuff — whether
//     the run holds a choice yet, and the feedback list at
//     its end
//   - SimulationsController::step_options — the latest
//     choice and its level, which pick the current step
//   - SimulationsController::simulation_do_stuff_save — a
//     row per choice
//   - SimulationsController::simulation_total_points — the
//     run's score
//   - Admin\SimulationsController::simulation_delete
//   - Admin\ManagerMainController::simulation_report — a
//     member's run
// -----------------------------------------------------------

class ScenariosUsersOptions extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_scenarios_options";
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
