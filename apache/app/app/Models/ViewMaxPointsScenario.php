<?php

// -----------------------------------------------------------
//  [*] ViewMaxPointsScenario — best simulation scores
//
//  Maps the MySQL view view_max_points_per_scenario of
//  mysql/init.sql: one row per member and simulation, with
//  the best points among the member's finished runs. The
//  simulations ranking adds these up per member. A view, so
//  the model only ever reads.
//
//  Used by:
//    - RanksController (Http/Controllers/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ViewMaxPointsScenario
// -----------------------------------------------------------
//
// Columns: id (the member's id in bei_users, not a row id
// of its own), username, max_points, scenario_id and
// lang_id. Only members with a finished run appear. A run's
// points are what its recorded choices add up to — each an
// option of the step it answered, the only kind
// SimulationsController::simulation_do_stuff_save records.
//
// Used by:
//   - RanksController::simulations — the simulations
//     ranking of a language
// -----------------------------------------------------------

class ViewMaxPointsScenario extends Model
{

    // The view, which has no created_at / updated_at columns;
    // rememberTokenName belongs to the account models, a
    // plain model never reads it
    protected $table = "view_max_points_per_scenario";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // Empty: nothing writes to a view
    protected $fillable = [
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // Empty: every column comes back uncast
    protected $casts = [
    ];
}
