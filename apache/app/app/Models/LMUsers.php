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
    // self-evaluations, 30 for a passed knowledge test (75
    // or more) and 25 for the simulations finished. The
    // lessons are those filed under a category, the ones a
    // member page lists, each counted once however often
    // its completion was stored; the self-evaluations are
    // one per category that has questions, counted once
    // however often it is taken. A share the language does
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
    // of every country when every_country is set, and of the
    // members without a country when the country is null —
    // in every language, keyed by language id and then
    // member id, from one statement; a pair at 0 is left
    // out, so a pair missing from the result is 0. The
    // statement works out the stored function's four shares
    // for all the pairs at once instead of calling
    // bei_user_course_progress per
    // pair — seven queries a call, which made the dashboard of
    // every country take over a second. The rows the shares
    // count — completed lessons, each once and only
    // those filed under a category, finished
    // self-evaluations, passed tests, finished runs —
    // are stacked into one list and grouped by member
    // and language in a single pass, then set against
    // each language's totals: joining the four groupings
    // to every member and language instead leaned on
    // keys MySQL builds only while its table statistics
    // are settled, and took 3.8 seconds on a freshly
    // restored database. The arithmetic is the
    // function's, expression for expression, on the same
    // column types, so MySQL rounds alike: the lesson
    // hours are a FLOAT, whose doubles round half to
    // even, the counts are exact and round half away
    // from zero, and the sum turns into a whole number
    // as the function's INT return does. A change to the
    // function must be made here too, as mysql/init.sql
    // notes beside it; AdminDashboardTest holds these
    // figures to the function's, halfway cases included.
    //
    // Used by:
    //   - Admin\ManagerMainController::dashboard — the course
    //     progress of every language tab, of every country
    //     for a main admin
    // -----------------------------------------------------------

    public static function country_progress($country, $every_country)
    {
        $rows = DB::select("SELECT pairs.user_id, pairs.lang_id,
                CAST(ROUND(
                      IFNULL(ROUND(pairs.hours * 100 / NULLIF(totals.hours, 0), 0) * 0.25, 0)
                    + IFNULL(ROUND(pairs.categories * 100 / NULLIF(totals.categories, 0), 0) * 0.2, 0)
                    + IF(pairs.passed = 1, 30, 0)
                    + IFNULL(ROUND(pairs.scenarios * 100 / NULLIF(totals.scenarios, 0), 0) * 0.25, 0)
                , 2) AS SIGNED) AS progress
            FROM (
                SELECT shares.user_id, shares.lang_id, SUM(shares.hours) AS hours,
                    COUNT(DISTINCT shares.cat_id) AS categories, MAX(shares.passed) AS passed,
                    COUNT(DISTINCT shares.scenario_id) AS scenarios
                FROM (
                    SELECT done.user_id, bei_lm_content.lang_id, bei_lm_content.hours, NULL AS cat_id, 0 AS passed, NULL AS scenario_id
                        FROM (SELECT DISTINCT user_id, content_id FROM bei_lm_users) AS done JOIN bei_lm_content ON bei_lm_content.id = done.content_id
                        WHERE bei_lm_content.cat_id IS NOT NULL
                    UNION ALL
                    SELECT t.user_id, t.lang_id, NULL, t.cat_id, 0, NULL
                        FROM bei_users_selfeval_test t JOIN bei_lm_category c ON c.id = t.cat_id AND c.lang_id = t.lang_id
                        WHERE t.finished = 1 AND t.cat_id IN (SELECT cat_id FROM bei_selfeval_question)
                    UNION ALL
                    SELECT user_id, lang_id, NULL, NULL, 1, NULL FROM bei_users_knowledge_test WHERE finished = 1 AND results >= 75
                    UNION ALL
                    SELECT user_id, lang_id, NULL, NULL, 0, scenario_id FROM bei_users_scenarios WHERE finished = 1
                ) AS shares
                WHERE shares.user_id IN (SELECT id FROM bei_users WHERE ? = 1 OR country <=> ?)
                GROUP BY shares.user_id, shares.lang_id
            ) AS pairs
            JOIN (
                SELECT bei_lang.id AS lang_id,
                    (SELECT SUM(hours) FROM bei_lm_content WHERE bei_lm_content.lang_id = bei_lang.id AND bei_lm_content.cat_id IS NOT NULL) AS hours,
                    (SELECT COUNT(DISTINCT q.cat_id) FROM bei_selfeval_question q JOIN bei_lm_category c ON c.id = q.cat_id WHERE c.lang_id = bei_lang.id) AS categories,
                    (SELECT COUNT(*) FROM bei_scenarios WHERE bei_scenarios.lang_id = bei_lang.id) AS scenarios
                FROM bei_lang
            ) AS totals ON totals.lang_id = pairs.lang_id
            HAVING progress <> 0", [$every_country ? 1 : 0, $country]);

        $progress = array();
        foreach($rows as $row){
            $progress[$row->lang_id][$row->user_id] = $row->progress;
        }

        return $progress;
    }
}
