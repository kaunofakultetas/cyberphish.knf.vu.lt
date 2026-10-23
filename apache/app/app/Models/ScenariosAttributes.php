<?php

// -----------------------------------------------------------
//  [*] ScenariosAttributes — the attributes of simulations
//
//  One bei_scenarios_attributes row per attribute a
//  simulation can be tagged with, in one language.
//  Simulations carry them through ScenariosInAttributes,
//  and the start page of a simulation lists its attributes.
//
//  Used by:
//    - SimulationsController (Http/Controllers/)
//    - Admin\SimulationsController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ScenariosAttributes
// -----------------------------------------------------------
//
// Columns: lang_id and name.
//
//   IdByAttr — an attribute's id by its name
//
// Used by:
//   - SimulationsController::simulation_start — the names
//     on the start page
//   - Admin\SimulationsController — attributes,
//     attributes_form, attributes_create, attributes_update,
//     attributes_delete, and the import through IdByAttr
// -----------------------------------------------------------

class ScenariosAttributes extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_scenarios_attributes";
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
    // IdByAttr
    // -----------------------------------------------------------
    //
    // The id of the attribute with that name in the language,
    // the spreadsheet cell's surrounding spaces trimmed off;
    // null when the language has no attribute of that name.
    // Another language's attribute of the same name never
    // answers.
    //
    // Used by:
    //   - Admin\SimulationsController::get_xlsx_attrs — the
    //     attributes ticked in an imported spreadsheet
    // -----------------------------------------------------------

    public static function IdByAttr($name, $lang_id)
    {
        $id = ScenariosAttributes::where(['name'=>trim($name), 'lang_id'=>$lang_id])->pluck('id')->first();

        return $id;
    }
}
