<?php

// -----------------------------------------------------------
//  [*] ViewMaxPointsSelfEvalCat — best self-evaluation scores
//
//  Maps the MySQL view view_max_points_per_selfeval_cat of
//  mysql/init.sql: one row per member and category, with the
//  best points among the member's finished self-evaluations.
//  The self-evaluation ranking adds these up per member. A
//  view, so the model only ever reads.
//
//  Used by:
//    - RanksController (Http/Controllers/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// ViewMaxPointsSelfEvalCat
// -----------------------------------------------------------
//
// Columns: id (the member's id in bei_users, not a row id
// of its own), username, max_points, cat_id
// (bei_lm_category) and lang_id. Only members with a
// finished self-evaluation appear.
//
// Used by:
//   - RanksController::self_evaluation — the self-evaluation
//     ranking of a language
// -----------------------------------------------------------

class ViewMaxPointsSelfEvalCat extends Model
{

    // The view, which has no created_at / updated_at columns;
    // rememberTokenName belongs to the account models, a
    // plain model never reads it
    protected $table = "view_max_points_per_selfeval_cat";
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
