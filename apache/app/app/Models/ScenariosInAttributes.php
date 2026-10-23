<?php

// -----------------------------------------------------------
//  [*] ScenariosInAttributes — the attributes of simulations
//
//  The link table between the simulations (Scenarios) and
//  their attributes (ScenariosAttributes): one
//  bei_scenarios_in_attributes row per simulation and
//  attribute, carrying the language as well.
//
//  Used by:
//    - SimulationsController (Http/Controllers/)
//    - Admin\SimulationsController,
//      Admin\SiteSettingsController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ScenariosInAttributes
// -----------------------------------------------------------
//
// Columns: lang_id, scenario_id (bei_scenarios) and
// attribute_id (bei_scenarios_attributes). No foreign keys:
// the panel's deletes remove the link rows themselves.
//
// Used by:
//   - SimulationsController::simulation_start — the
//     attributes on the start page
//   - Admin\SimulationsController — simulations_import,
//     attributes_delete, simulation_delete
//   - Admin\SiteSettingsController::SimulationsDownloadPdf —
//     the printable page of every simulation, HTML despite
//     the name
// -----------------------------------------------------------

class ScenariosInAttributes extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_scenarios_in_attributes";
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
