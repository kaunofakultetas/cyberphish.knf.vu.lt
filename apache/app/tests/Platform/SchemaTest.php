<?php

// -----------------------------------------------------------
//  [*] SchemaTest — the database mysql/init.sql builds
//
//  run.sh loads mysql/init.sql into a fresh MySQL 5.7.42,
//  the way the production stack's first start does. It
//  must create every table the app uses, the two ranking
//  views, the course-progress function, and the seed rows:
//  English, the self-evaluation length, and the main admin.
//  The admin's known password is pinned in
//  PlatformDefectsTest.
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;








// -----------------------------------------------------------
// SchemaTest
// -----------------------------------------------------------
//
// Reads the schema through information_schema and the seed
// rows directly; nothing here writes.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class SchemaTest extends TestCase
{

    // The tables of the app — the 28 the production database
    // holds
    private const TABLES = [
        'bei_information',
        'bei_knowledge_option',
        'bei_knowledge_test',
        'bei_lang',
        'bei_lm_category',
        'bei_lm_content',
        'bei_lm_content_files',
        'bei_lm_users',
        'bei_managers',
        'bei_news',
        'bei_scenarios',
        'bei_scenarios_attributes',
        'bei_scenarios_category',
        'bei_scenarios_in_attributes',
        'bei_scenarios_in_category',
        'bei_scenarios_options',
        'bei_selfeval_option',
        'bei_selfeval_question',
        'bei_settings',
        'bei_users',
        'bei_users_badges',
        'bei_users_knowledge_options',
        'bei_users_knowledge_test',
        'bei_users_loginlog',
        'bei_users_scenarios',
        'bei_users_scenarios_options',
        'bei_users_selfeval',
        'bei_users_selfeval_test',
    ];






    // -----------------------------------------------------------
    // testEveryTableExists
    // -----------------------------------------------------------
    //
    // The base tables are exactly the app's 28.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryTableExists(): void
    {
        $tables = DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->orderBy('TABLE_NAME')
            ->pluck('TABLE_NAME')
            ->all();

        $this->assertSame(self::TABLES, $tables);
    }






    // -----------------------------------------------------------
    // testRankingViewsAndProgressFunctionExist
    // -----------------------------------------------------------
    //
    // The two views the public rankings read and the stored
    // function behind every member's progress bar.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRankingViewsAndProgressFunctionExist(): void
    {
        $views = DB::table('information_schema.VIEWS')->whereRaw('TABLE_SCHEMA = DATABASE()')->pluck('TABLE_NAME')->all();
        $this->assertEqualsCanonicalizing(['view_max_points_per_scenario', 'view_max_points_per_selfeval_cat'], $views);

        $functions = DB::table('information_schema.ROUTINES')->whereRaw('ROUTINE_SCHEMA = DATABASE()')->pluck('ROUTINE_NAME')->all();
        $this->assertSame(['bei_user_course_progress'], $functions);
    }






    // -----------------------------------------------------------
    // testSeedRowsArePresent
    // -----------------------------------------------------------
    //
    // English is language 3, settings row 2 asks five
    // questions a self-evaluation, and manager 1 is an active
    // main admin.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSeedRowsArePresent(): void
    {
        $this->assertSame('en', DB::table('bei_lang')->where('id', 3)->value('locale'));
        $this->assertEquals(5, DB::table('bei_settings')->where('id', 2)->value('val3'));

        $admin = DB::table('bei_managers')->where('id', 1)->first();
        $this->assertSame('admin@knf.vu.lt', $admin->email);
        $this->assertEquals(1, $admin->main);
        $this->assertEquals(1, $admin->status);
    }
}
