<?php

// -----------------------------------------------------------
//  [*] ScenariosInCategory — simulations in their categories
//
//  The link table between the simulations (Scenarios) and
//  their categories (ScenariosCategory): one
//  bei_scenarios_in_category row per simulation and
//  category, carrying the language as well, so the member
//  pages can filter by it.
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
// ScenariosInCategory
// -----------------------------------------------------------
//
// Columns: lang_id, cat_id (bei_scenarios_category) and
// scenario_id (bei_scenarios). No foreign keys: the panel's
// deletes remove the link rows themselves.
//
// Used by:
//   - SimulationsController::simulations_list — the
//     simulations of a category
//   - SimulationsController::simulation_start — the
//     categories on the start page
//   - Admin\SimulationsController — simulations_import,
//     simulation_categories, simulation_categories_update,
//     categories_delete, simulation_delete
//   - Admin\SiteSettingsController::SimulationsDownloadPdf —
//     the simulations as a PDF
// -----------------------------------------------------------

class ScenariosInCategory extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_scenarios_in_category";
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
