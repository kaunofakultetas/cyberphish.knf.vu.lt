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
    // The id of the attribute with that name, the spreadsheet
    // cell's surrounding spaces trimmed off; null when no
    // attribute has it, which the import passes on unchecked.
    //
    // Known bug (low): the name is matched across every
    // language (ScenariosAttributes.php:95), so where two
    // languages share an attribute name, whichever row MySQL
    // returns first wins, and an imported simulation can be
    // tagged with the other language's attribute. Fix: pass
    // the import's language in and match lang_id too.
    // Pinned as import-cross-language.
    //
    // Used by:
    //   - Admin\SimulationsController::get_xlsx_attrs — the
    //     attributes ticked in an imported spreadsheet
    // -----------------------------------------------------------

    public static function IdByAttr($name)
    {
        $id = ScenariosAttributes::where(['name'=>trim($name)])->pluck('id')->first();

        return $id;
    }
}
