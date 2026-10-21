<?php

// -----------------------------------------------------------
//  [*] ScenariosCategory — the categories of the simulations
//
//  One bei_scenarios_category row per simulation category,
//  in one language. Simulations are filed under them
//  through ScenariosInCategory: the member pages list a
//  language's categories first, then the simulations of
//  the one picked.
//
//  Used by:
//    - SimulationsController (Http/Controllers/)
//    - Admin\SimulationsController (Http/Controllers/Admin/)
//    - R_Exists (Http/Middleware/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ScenariosCategory
// -----------------------------------------------------------
//
// Columns: lang_id and name.
//
//   IdByCategory — a category's id by its name
//
// Used by:
//   - SimulationsController — categories (the list),
//     simulations_list, simulation_start (the names on the
//     start page)
//   - R_Exists::handle — the sim_cat check
//   - Admin\SimulationsController — categories,
//     categories_form, categories_create, categories_update,
//     categories_delete, simulation_categories, and the
//     import through IdByCategory
// -----------------------------------------------------------

class ScenariosCategory extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_scenarios_category";
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
    // IdByCategory
    // -----------------------------------------------------------
    //
    // The id of the category with that name, the spreadsheet
    // cell's surrounding spaces trimmed off; null when no
    // category has it, which the import passes on unchecked.
    //
    // Known bug (low): the name is matched across every
    // language (ScenariosCategory.php:100), so where two
    // languages share a category name, whichever row MySQL
    // returns first wins, and an imported simulation can be
    // filed under the other language's category. Fix: pass
    // the import's language in and match lang_id too.
    // Pinned as import-cross-language.
    //
    // Used by:
    //   - Admin\SimulationsController::get_xlsx_categories —
    //     the categories ticked in an imported spreadsheet
    // -----------------------------------------------------------

    public static function IdByCategory($name)
    {
        $id = ScenariosCategory::where(['name'=>trim($name)])->pluck('id')->first();

        return $id;
    }
}
