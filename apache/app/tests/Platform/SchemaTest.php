<?php

// -----------------------------------------------------------
//  [*] SchemaTest — the database mysql/init.sql builds
//
//  run.sh loads mysql/init.sql into a fresh MySQL 5.7.42,
//  the way the production stack's first start does. It
//  must create every table the app uses, with a key on the
//  columns the app finds rows by, the two ranking views,
//  the course-progress function, and the seed rows:
//  English, the three settings rows the settings page saves
//  into, and the main admin — whose password nobody knows.
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    // The columns the app finds rows by, per table — the
    // member and language of the progress function and the
    // admin dashboard, the public id of an attempt, the
    // category of a question, the place of a simulation step;
    // each list must lead a key of its table
    private const LOOKUPS = [
        'bei_knowledge_option'        => [['question_id']],
        'bei_knowledge_test'          => [['cat_id']],
        'bei_scenarios'               => [['lang_id']],
        'bei_scenarios_in_attributes' => [['scenario_id']],
        'bei_scenarios_in_category'   => [['scenario_id']],
        'bei_scenarios_options'       => [['scenario_id', 'parent_option_id', 'level']],
        'bei_selfeval_option'         => [['question_id']],
        'bei_selfeval_question'       => [['cat_id']],
        'bei_users_badges'            => [['user_id', 'lang_id']],
        'bei_users_knowledge_options' => [['test_id']],
        'bei_users_knowledge_test'    => [['user_id', 'lang_id'], ['public_id']],
        'bei_users_loginlog'          => [['user_id']],
        'bei_users_scenarios'         => [['user_id', 'lang_id'], ['public_id']],
        'bei_users_scenarios_options' => [['user_scenario_public_id']],
        'bei_users_selfeval'          => [['test_id'], ['public_id']],
        'bei_users_selfeval_test'     => [['user_id', 'lang_id'], ['public_id']],
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
    // testEveryTextColumnIsUtf8mb4
    // -----------------------------------------------------------
    //
    // Every text column of the schema is utf8mb4, as the
    // connection is, so no column turns a letter into a
    // question mark or refuses a lookup with an illegal mix of
    // collations; bei_managers was the last latin1 table.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryTextColumnIsUtf8mb4(): void
    {
        $others = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->whereNotNull('CHARACTER_SET_NAME')
            ->where('CHARACTER_SET_NAME', '<>', 'utf8mb4')
            ->selectRaw("CONCAT(TABLE_NAME, '.', COLUMN_NAME, ' ', CHARACTER_SET_NAME) AS col")
            ->pluck('col')
            ->all();

        $this->assertSame([], $others);
    }






    // -----------------------------------------------------------
    // testTablesAndTheDatabaseDefaultToUtf8mb4
    // -----------------------------------------------------------
    //
    // The defaults are utf8mb4 too — every table's and the
    // database's own — so a column added later or a table
    // created without a character set of its own keeps every
    // letter, where a latin1 default once turned them into
    // question marks.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTablesAndTheDatabaseDefaultToUtf8mb4(): void
    {
        $tables = DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->where('TABLE_COLLATION', 'not like', 'utf8mb4%')
            ->pluck('TABLE_NAME')
            ->all();

        $this->assertSame([], $tables);
        $this->assertSame('utf8mb4', DB::table('information_schema.SCHEMATA')->whereRaw('SCHEMA_NAME = DATABASE()')->value('DEFAULT_CHARACTER_SET_NAME'));
    }






    // -----------------------------------------------------------
    // testLookupsAreKeyed
    // -----------------------------------------------------------
    //
    // Every column list of LOOKUPS leads a key of its table,
    // so the lookup reads only its own rows. Without them the
    // course-progress function and the dashboard read whole
    // member tables per member and language, and the
    // dashboard of the largest country took over a minute.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLookupsAreKeyed(): void
    {
        $keys = [];
        $columns = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->orderBy('SEQ_IN_INDEX')
            ->get();
        foreach ($columns as $column) {
            $keys[$column->TABLE_NAME][$column->INDEX_NAME][] = $column->COLUMN_NAME;
        }

        $unkeyed = [];
        foreach (self::LOOKUPS as $table => $lookups) {
            foreach ($lookups as $lookup) {
                $led = array_filter($keys[$table] ?? [], function (array $key) use ($lookup) {
                    return array_slice($key, 0, count($lookup)) === $lookup;
                });
                if ($led === []) {
                    $unkeyed[] = $table . ' (' . implode(', ', $lookup) . ')';
                }
            }
        }

        $this->assertSame([], $unkeyed);
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
    // English is language 3; settings row 2 asks five
    // questions a self-evaluation, and rows 1 and 3 wait,
    // empty, for the about text and the knowledge-test limits;
    // manager 1 is an active main admin.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSeedRowsArePresent(): void
    {
        $this->assertSame('en', DB::table('bei_lang')->where('id', 3)->value('locale'));
        $this->assertEquals(5, DB::table('bei_settings')->where('id', 2)->value('val3'));
        $this->assertSame('home_about', DB::table('bei_settings')->where('id', 1)->value('name'));
        $this->assertSame('knowledge_tests', DB::table('bei_settings')->where('id', 3)->value('name'));

        $admin = DB::table('bei_managers')->where('id', 1)->first();
        $this->assertSame('admin@knf.vu.lt', $admin->email);
        $this->assertEquals(1, $admin->main);
        $this->assertEquals(1, $admin->status);
    }






    // -----------------------------------------------------------
    // testSeedSettingsSurviveAWorldThatReplacedThem
    // -----------------------------------------------------------
    //
    // A test that replaces a seeded settings row leaves it as
    // it found it: removeAll puts the seed back. The Apache
    // suite has no transaction to roll the row back, and a
    // World once deleted it outright, which would have left
    // the tests after it without the self-evaluation length.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSeedSettingsSurviveAWorldThatReplacedThem(): void
    {
        $seed = (array) DB::table('bei_settings')->where('id', 2)->first();

        $this->world->setting(2, 'self_eval_pertime', null, null, 9);
        $this->world->setting(2, 'self_eval_pertime', null, null, 7);
        $this->world->removeAll();

        $this->assertEquals($seed, (array) DB::table('bei_settings')->where('id', 2)->first());
    }






    // -----------------------------------------------------------
    // testSeededAdminHasNoKnownPassword
    // -----------------------------------------------------------
    //
    // The main admin's hash is of a random password nobody
    // kept, so a fresh deployment opens with no admin anyone
    // can guess — not with "admin", as the seed once had it.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSeededAdminHasNoKnownPassword(): void
    {
        $hash = DB::table('bei_managers')->where('id', 1)->value('password');

        $this->assertFalse(Hash::check('admin', $hash), 'the seeded admin\'s password is "admin"');
    }
}
