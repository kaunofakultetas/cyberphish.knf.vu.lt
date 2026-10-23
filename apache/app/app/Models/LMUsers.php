<?php

// -----------------------------------------------------------
//  [*] LMUsers — the lessons each member completed
//
//  One bei_lm_users row per lesson a member has marked
//  completed. Besides the ticks in the lesson lists, the
//  table drives the course progress every member page
//  shows, which the stored function bei_user_course_progress
//  of mysql/init.sql works out.
//
//  Used by:
//    - BaseController, LMController (Http/Controllers/)
//    - Admin\LMController, Admin\ManagerMainController
//      (Http/Controllers/Admin/)
//    - CourseProgressTest (tests/Feature/Learning/)
//    - AdminDashboardTest (tests/Feature/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// LMUsers
// -----------------------------------------------------------
//
// Columns: user_id (bei_users; the foreign key sets it to
// NULL when the member is deleted) and content_id
// (bei_lm_content).
//
//   get_users_progress — the lessons a member completed
//   course_progress    — a member's course progress
//   country_progress   — the course progress of a whole
//                        country's members, every language
//                        at once
//
// Used by:
//   - BaseController::__construct — both helpers, on every
//     member page
//   - LMController::mark_completed — records a completed
//     lesson
//   - LMController::show_self_eval — whether a category's
//     lessons are all done
//   - Admin\LMController::delete — the rows go with their
//     lesson
//   - Admin\ManagerMainController::dashboard — every
//     member's progress per language, through
//     country_progress
// -----------------------------------------------------------

class LMUsers extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_lm_users";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns firstOrCreate may fill — mark_completed
    // records a lesson through it, once per member and
    // lesson
    protected $fillable = [
        'id', 'content_id', 'user_id'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model
    protected $casts = [
        'id'=>'integer',
        'content_id'=>'integer',
        'user_id'=>'integer'
    ];






    // -----------------------------------------------------------
    // get_users_progress
    // -----------------------------------------------------------
    //
    // The ids of the lessons the member has completed, as a
    // plain array — empty for none.
    //
    // Used by:
    //   - BaseController::__construct — shared with every
    //     view: the ticks of the lesson lists, and the
    //     completed button of a lesson page
    // -----------------------------------------------------------

    public static function get_users_progress($user_id)
    {
        $data = LMUsers::select("content_id")->where(['user_id'=>$user_id]);

        if($data->count() > 0){

            return $data->get()->pluck('content_id')->toArray();

        } else {
            return array();
        }
    }






    // -----------------------------------------------------------
    // course_progress
    // -----------------------------------------------------------
    //
    // The member's course progress in the language, a whole
    // percentage, from the stored function
    // bei_user_course_progress of mysql/init.sql: up to 25
    // points for the hours of completed lessons, 20 for the
    // self-evaluations — one per category that has
    // questions, counted once however often it is taken —
    // 30 for a passed knowledge test (75 or more) and 25 for
    // the simulations finished. A share the language does
    // not offer counts 0, so in a language without
    // simulations the progress tops out at 75. A NULL from
    // the function comes back as 0.
    //
    // Used by:
    //   - BaseController::__construct — the progress bar of
    //     every member page
    //   - CourseProgressTest (tests/Feature/Learning/) — runs
    //     it on the real function
    //   - AdminDashboardTest (tests/Feature/Admin/) — the
    //     figure each dashboard row is checked against
    // -----------------------------------------------------------

    public static function course_progress($user_id, $lang_id)
    {
        $data = DB::select("SELECT bei_user_course_progress(?, ?) AS progress", [$user_id, $lang_id]);

        if(is_array($data) && isset($data[0]->progress)){
            return $data[0]->progress;
        } else {
            return 0;
        }
    }






    // -----------------------------------------------------------
    // country_progress
    // -----------------------------------------------------------
    //
    // The course progress of every member of the country —
    // of every country when the country is null — in every
    // language, keyed by language id and then member id, from
    // one statement. The stored function runs only for the
    // member and language pairs that have a row of the
    // member's own in one of the four tables its shares count
    // — a completed lesson of the language, a finished
    // self-evaluation, knowledge test or simulation run —
    // because every other pair is 0 by the function's own
    // sums, as mysql/init.sql notes beside it. A pair missing
    // from the result is therefore 0. Most members work in
    // one language, so most pairs never reach the function,
    // which the dashboard of a large country once called for
    // every member in every language. A NULL from the
    // function comes back as 0, as in course_progress.
    //
    // Used by:
    //   - Admin\ManagerMainController::dashboard — the course
    //     progress of every language tab, of every country
    //     for a main admin
    // -----------------------------------------------------------

    public static function country_progress($country)
    {
        $rows = DB::select("SELECT pairs.user_id, pairs.lang_id, bei_user_course_progress(pairs.user_id, pairs.lang_id) AS progress
            FROM (
                SELECT bei_lm_users.user_id, bei_lm_content.lang_id FROM bei_lm_users JOIN bei_lm_content ON bei_lm_content.id = bei_lm_users.content_id
                UNION SELECT user_id, lang_id FROM bei_users_selfeval_test WHERE finished = 1
                UNION SELECT user_id, lang_id FROM bei_users_knowledge_test WHERE finished = 1
                UNION SELECT user_id, lang_id FROM bei_users_scenarios WHERE finished = 1
            ) AS pairs
            JOIN bei_users ON bei_users.id = pairs.user_id
            WHERE ? IS NULL OR bei_users.country = ?", [$country, $country]);

        $progress = array();
        foreach($rows as $row){
            $progress[$row->lang_id][$row->user_id] = $row->progress ?? 0;
        }

        return $progress;
    }
}
