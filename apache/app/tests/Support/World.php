<?php

// -----------------------------------------------------------
//  [*] World — the rows a test builds its scene from
//
//  One builder per kind of row the app reads — languages,
//  members, managers, settings, content, simulations, test
//  questions — each filling in valid defaults so a test
//  states only what matters to it. Rows go in through the
//  query builder, never through the app's models, so a
//  model's quirks cannot leak into the scene. Every row is
//  remembered: in the Feature suite the transaction
//  rollback removes them anyway, in the Apache suite
//  removeAll deletes them after the test.
//
//  Used by:
//    - TestCase (tests/) — one World per test
//    - ApacheTestCase (tests/), the Feature and Apache
//      suites — PASSWORD, to type or check a password
// -----------------------------------------------------------


namespace Tests\Support;

use App\Models\Managers;
use App\Models\Members;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;








// -----------------------------------------------------------
// World
// -----------------------------------------------------------
//
// Emails, user names and titles get a serial number that is
// unique for the whole run, so rows a test commits never
// collide with another test's. English (locale en, id 3)
// is seeded by mysql/init.sql; language() adds the others
// on demand.
//
//   language, setting     — the site's reference rows
//   member, manager       — accounts that can sign in
//   news, information     — the public content
//   lmCategory, lmContent, lmFile
//                         — the learning material
//   selfEvalQuestion, knowledgeQuestion, knowledgeLimits
//                         — the questions of both tests
//   scenarioCategory, scenarioAttribute, scenario,
//   scenarioOption        — the simulations
//   loginLog, lessonDone, badge, selfEvalAttempt,
//   knowledgeAttempt, scenarioAttempt
//                         — a member's history
//   nextId                — the id a table gives next
//   removeAll             — deletes the World's rows, puts
//                           back the replaced settings
//
// Used by:
//   - TestCase (tests/) — one World per test
//   - ApacheTestCase (tests/), the Feature and Apache
//     suites — PASSWORD, to type or check a password
// -----------------------------------------------------------

class World
{

    // The password of every account a World creates — long
    // enough for the member login's 12-character minimum
    public const PASSWORD = 'Correct-Horse-Battery-9';

    // The tables whose rows the app writes for a member — a
    // login, a badge, a finished lesson or test — deleted
    // with the member by removeAll
    private const MEMBER_TABLES = [
        'bei_users_loginlog',
        'bei_users_badges',
        'bei_lm_users',
        'bei_users_selfeval',
        'bei_users_selfeval_test',
        'bei_users_knowledge_options',
        'bei_users_knowledge_test',
        'bei_users_scenarios_options',
        'bei_users_scenarios',
    ];

    // The serial of the last generated name, shared by every
    // World of the run
    private static $serial = 0;

    // The rows this World inserted, as table and id pairs in
    // insertion order
    private $rows = [];

    // The seeded bei_settings rows setting replaced, by id, as
    // they were — removeAll puts them back
    private $replacedSettings = [];






    // -----------------------------------------------------------
    // language
    // -----------------------------------------------------------
    //
    // The id of the language with that locale, adding it when
    // the seed rows do not have it.
    //
    // Used by:
    //   - the Feature suite — Account/, Admin/, Auth/,
    //     Knowledge/, Learning/, Simulations/, Site/
    // -----------------------------------------------------------

    public function language(string $locale): int
    {
        $id = DB::table('bei_lang')->where('locale', $locale)->value('id');
        if ($id !== null) {
            return (int) $id;
        }
        return $this->insert('bei_lang', ['name' => 'Language ' . $locale, 'locale' => $locale]);
    }






    // -----------------------------------------------------------
    // setting
    // -----------------------------------------------------------
    //
    // Writes one bei_settings row — the site keeps its
    // settings in rows with fixed ids (1 the about text, 2 the
    // self-evaluation length, 3 the knowledge test limits) —
    // replacing what the seed may have put there. The row it
    // replaces is kept, the first time only, for removeAll to
    // put back.
    //
    // Used by:
    //   - CourseProgressTest, SelfEvaluationTest
    //     (Feature/Learning/)
    //   - PublicPagesTest (Feature/Site/)
    //   - knowledgeLimits (below)
    // -----------------------------------------------------------

    public function setting(int $id, string $name, ?string $val1, ?string $val2 = null, int $val3 = 0): void
    {
        $seed = DB::table('bei_settings')->where('id', $id)->first();
        if ($seed !== null && !array_key_exists($id, $this->replacedSettings)) {
            $this->replacedSettings[$id] = (array) $seed;
        }

        DB::table('bei_settings')->where('id', $id)->delete();
        DB::table('bei_settings')->insert([
            'id'   => $id,
            'name' => $name,
            'val1' => $val1,
            'val2' => $val2,
            'val3' => $val3,
        ]);
        $this->rows[] = ['bei_settings', $id];
    }






    // -----------------------------------------------------------
    // member
    // -----------------------------------------------------------
    //
    // A verified, active member (status 1) of country 1 whose
    // password is PASSWORD; the columns given replace the
    // defaults. Returns the member's model, ready for
    // signInMember.
    //
    // Used by:
    //   - the Feature and Apache suites
    // -----------------------------------------------------------

    public function member(array $columns = []): Members
    {
        $n = $this->next();
        $id = $this->insert('bei_users', array_merge([
            'username'       => 'Member' . $n,
            'email'          => 'member' . $n . '@example.test',
            'password'       => Hash::make(self::PASSWORD),
            'status'         => 1,
            'country'        => 1,
            'verify_token'   => '',
            'reset_token'    => '',
            'remember_token' => '',
        ], $columns));
        return Members::find($id);
    }






    // -----------------------------------------------------------
    // manager
    // -----------------------------------------------------------
    //
    // An active main admin (main 1, status 1, country 0) whose
    // password is PASSWORD; pass main 0 and a country for a
    // country manager. Returns the manager's model, ready for
    // signInManager.
    //
    // Used by:
    //   - the admin tests (Feature/Admin/)
    // -----------------------------------------------------------

    public function manager(array $columns = []): Managers
    {
        $n = $this->next();
        $id = $this->insert('bei_managers', array_merge([
            'email'    => 'manager' . $n . '@example.test',
            'password' => Hash::make(self::PASSWORD),
            'main'     => 1,
            'status'   => '1',
            'country'  => 0,
        ], $columns));
        return Managers::find($id);
    }






    // -----------------------------------------------------------
    // news
    // -----------------------------------------------------------
    //
    // A news article in the language, published now by the
    // seeded admin (manager 1); returns its id.
    //
    // Used by:
    //   - AdminContentTest (Feature/Admin/)
    //   - PublicPagesTest (Feature/Site/)
    // -----------------------------------------------------------

    public function news(int $langId, array $columns = []): int
    {
        $n = $this->next();
        return $this->insert('bei_news', array_merge([
            'title'      => 'News ' . $n,
            'content'    => '<p>News body ' . $n . '</p>',
            'alias'      => 'news-' . $n,
            'feat_img'   => '',
            'created'    => date('Y-m-d H:i:s'),
            'created_by' => 1,
            'lang_id'    => $langId,
        ], $columns));
    }






    // -----------------------------------------------------------
    // information
    // -----------------------------------------------------------
    //
    // An information page in the language; returns its id.
    //
    // Used by:
    //   - PublicPagesTest (Feature/Site/)
    // -----------------------------------------------------------

    public function information(int $langId, array $columns = []): int
    {
        $n = $this->next();
        return $this->insert('bei_information', array_merge([
            'title'   => 'Page ' . $n,
            'content' => '<p>Page body ' . $n . '</p>',
            'alias'   => 'page-' . $n,
            'lang_id' => $langId,
        ], $columns));
    }






    // -----------------------------------------------------------
    // lmCategory
    // -----------------------------------------------------------
    //
    // A learning-material category in the language; returns
    // its id.
    //
    // Used by:
    //   - the Feature suite — Admin/, Knowledge/, Learning/,
    //     Site/
    // -----------------------------------------------------------

    public function lmCategory(int $langId, array $columns = []): int
    {
        return $this->insert('bei_lm_category', array_merge([
            'name'    => 'Category ' . $this->next(),
            'lang_id' => $langId,
        ], $columns));
    }






    // -----------------------------------------------------------
    // lmContent
    // -----------------------------------------------------------
    //
    // A lesson of the category, worth one hour of the course;
    // returns its id.
    //
    // Used by:
    //   - AdminLearningTest, AdminUsersTest (Feature/Admin/)
    //   - CourseProgressTest, LearningMaterialTest
    //     (Feature/Learning/)
    // -----------------------------------------------------------

    public function lmContent(int $categoryId, int $langId, array $columns = []): int
    {
        $n = $this->next();
        return $this->insert('bei_lm_content', array_merge([
            'cat_id'  => $categoryId,
            'title'   => 'Lesson ' . $n,
            'content' => '<p>Lesson body ' . $n . '</p>',
            'hours'   => 1.0,
            'lang_id' => $langId,
        ], $columns));
    }






    // -----------------------------------------------------------
    // lmFile
    // -----------------------------------------------------------
    //
    // A file attached to the lesson — a downloadable PDF
    // unless the columns say otherwise; returns its id.
    //
    // Used by:
    //   - AdminLearningTest (Feature/Admin/)
    //   - LearningMaterialTest (Feature/Learning/)
    // -----------------------------------------------------------

    public function lmFile(int $contentId, array $columns = []): int
    {
        $n = $this->next();
        return $this->insert('bei_lm_content_files', array_merge([
            'content_id' => $contentId,
            'name'       => 'File ' . $n,
            'file_name'  => 'lm/file-' . $n . '.pdf',
            'mime_type'  => 'application/pdf',
            'file_size'  => 1024,
            'embed'      => 0,
        ], $columns));
    }






    // -----------------------------------------------------------
    // selfEvalQuestion
    // -----------------------------------------------------------
    //
    // A self-evaluation question of the category with its
    // options, each given as its text, whether it is correct
    // and its points; the question is a single-choice one
    // (q_type 1) with one correct option and a multiple-choice
    // one (q_type 2) with more. Returns the question id under
    // "question" and the option ids, in order, under
    // "options".
    //
    // Used by:
    //   - AdminLearningTest (Feature/Admin/)
    //   - CourseProgressTest, SelfEvaluationTest
    //     (Feature/Learning/)
    // -----------------------------------------------------------

    public function selfEvalQuestion(int $categoryId, array $options): array
    {
        $correct = count(array_filter($options, function (array $option) {
            return $option[1];
        }));
        $questionId = $this->insert('bei_selfeval_question', [
            'cat_id'   => $categoryId,
            'question' => 'Self-evaluation question ' . $this->next(),
            'q_type'   => $correct > 1 ? 2 : 1,
        ]);
        $optionIds = [];
        foreach ($options as list($text, $isCorrect, $points)) {
            $optionIds[] = $this->insert('bei_selfeval_option', [
                'question_id' => $questionId,
                'option'      => $text,
                'correct'     => $isCorrect ? 1 : 0,
                'points'      => $points,
            ]);
        }
        return ['question' => $questionId, 'options' => $optionIds];
    }






    // -----------------------------------------------------------
    // knowledgeQuestion
    // -----------------------------------------------------------
    //
    // A knowledge-test question of the category with its
    // options, each given as its text and whether it is
    // correct. Returns the question id under "question" and
    // the option ids, in order, under "options".
    //
    // Used by:
    //   - AdminLearningTest (Feature/Admin/)
    //   - KnowledgeExamTest (Feature/Knowledge/)
    // -----------------------------------------------------------

    public function knowledgeQuestion(int $categoryId, array $options): array
    {
        $questionId = $this->insert('bei_knowledge_test', [
            'cat_id'   => $categoryId,
            'question' => 'Knowledge question ' . $this->next(),
        ]);
        $optionIds = [];
        foreach ($options as list($text, $isCorrect)) {
            $optionIds[] = $this->insert('bei_knowledge_option', [
                'question_id' => $questionId,
                'option'      => $text,
                'correct'     => $isCorrect ? 1 : 0,
            ]);
        }
        return ['question' => $questionId, 'options' => $optionIds];
    }






    // -----------------------------------------------------------
    // knowledgeLimits
    // -----------------------------------------------------------
    //
    // How many questions a knowledge test draws, per language
    // id and per category id within it — the JSON the
    // settings page stores in settings row 3.
    //
    // Used by:
    //   - KnowledgeExamTest (Feature/Knowledge/)
    // -----------------------------------------------------------

    public function knowledgeLimits(array $limits): void
    {
        $this->setting(3, 'knowledge_test', json_encode($limits));
    }






    // -----------------------------------------------------------
    // scenarioCategory
    // -----------------------------------------------------------
    //
    // A simulation category in the language; returns its id.
    //
    // Used by:
    //   - AdminContentTest, AdminSimulationsTest
    //     (Feature/Admin/)
    //   - SimulationsTest (Feature/Simulations/)
    // -----------------------------------------------------------

    public function scenarioCategory(int $langId, array $columns = []): int
    {
        return $this->insert('bei_scenarios_category', array_merge([
            'name'    => 'Simulation category ' . $this->next(),
            'lang_id' => $langId,
        ], $columns));
    }






    // -----------------------------------------------------------
    // scenarioAttribute
    // -----------------------------------------------------------
    //
    // A simulation attribute in the language; returns its id.
    //
    // Used by:
    //   - AdminContentTest, AdminSimulationsTest
    //     (Feature/Admin/)
    //   - SimulationsTest (Feature/Simulations/)
    // -----------------------------------------------------------

    public function scenarioAttribute(int $langId, array $columns = []): int
    {
        return $this->insert('bei_scenarios_attributes', array_merge([
            'name'    => 'Simulation attribute ' . $this->next(),
            'lang_id' => $langId,
        ], $columns));
    }






    // -----------------------------------------------------------
    // scenario
    // -----------------------------------------------------------
    //
    // A simulation in the language, filed under the given
    // categories and tagged with the given attributes; its
    // steps are added with scenarioOption. Returns its id.
    //
    // Used by:
    //   - AdminAccessTest, AdminContentTest,
    //     AdminSimulationsTest (Feature/Admin/)
    //   - CourseProgressTest (Feature/Learning/)
    //   - SimulationsTest (Feature/Simulations/)
    //   - PublicPagesTest (Feature/Site/)
    // -----------------------------------------------------------

    public function scenario(int $langId, array $columns = [], array $categoryIds = [], array $attributeIds = []): int
    {
        $n = $this->next();
        $scenarioId = $this->insert('bei_scenarios', array_merge([
            'lang_id'     => $langId,
            'descr'       => 'Scenario ' . $n,
            'goal'        => 'Goal ' . $n,
            'source'      => 'Source ' . $n,
            'actors'      => 'Actors ' . $n,
            'image'       => '',
            'choose_type' => '1',
            'attack_type' => 'Phishing',
        ], $columns));
        foreach ($categoryIds as $categoryId) {
            $this->insert('bei_scenarios_in_category', [
                'lang_id'     => $langId,
                'cat_id'      => $categoryId,
                'scenario_id' => $scenarioId,
            ]);
        }
        foreach ($attributeIds as $attributeId) {
            $this->insert('bei_scenarios_in_attributes', [
                'lang_id'      => $langId,
                'scenario_id'  => $scenarioId,
                'attribute_id' => $attributeId,
            ]);
        }
        return $scenarioId;
    }






    // -----------------------------------------------------------
    // scenarioOption
    // -----------------------------------------------------------
    //
    // One choice of a simulation step: level 1 choices hang
    // under parent 0, a deeper one under the choice that leads
    // to it. The option type follows the points the way the
    // schema describes it — 0, 50, 100 and 200 points are
    // types 0 to 3. Returns its id.
    //
    // Used by:
    //   - AdminSimulationsTest (Feature/Admin/)
    //   - SimulationsTest (Feature/Simulations/)
    // -----------------------------------------------------------

    public function scenarioOption(int $scenarioId, int $parentId, int $level, int $points, array $columns = []): int
    {
        $type = 0;
        foreach ([50 => 1, 100 => 2, 200 => 3] as $threshold => $optionType) {
            if ($points >= $threshold) {
                $type = $optionType;
            }
        }
        return $this->insert('bei_scenarios_options', array_merge([
            'scenario_id'      => $scenarioId,
            'parent_option_id' => $parentId,
            'level'            => $level,
            'situation'        => 'Choice ' . $this->next(),
            'feedback'         => '',
            'image'            => '',
            'link'             => '',
            'option_type'      => $type,
            'points'           => $points,
        ], $columns));
    }






    // -----------------------------------------------------------
    // loginLog
    // -----------------------------------------------------------
    //
    // A login of the member at the given time, as the login
    // log keeps it; returns its id.
    //
    // Used by:
    //   - LoginTest (Feature/Auth/)
    // -----------------------------------------------------------

    public function loginLog(int $userId, string $loggedAt): int
    {
        return $this->insert('bei_users_loginlog', ['user_id' => $userId, 'login_date' => $loggedAt]);
    }






    // -----------------------------------------------------------
    // lessonDone
    // -----------------------------------------------------------
    //
    // Marks the lesson as completed by the member; returns
    // the row id.
    //
    // Used by:
    //   - AdminLearningTest, AdminUsersTest (Feature/Admin/)
    //   - CourseProgressTest (Feature/Learning/)
    // -----------------------------------------------------------

    public function lessonDone(int $userId, int $contentId): int
    {
        return $this->insert('bei_lm_users', ['user_id' => $userId, 'content_id' => $contentId]);
    }






    // -----------------------------------------------------------
    // badge
    // -----------------------------------------------------------
    //
    // One badge of the member in the language, by its name —
    // the knowledge test opens only for a member who holds
    // badge_all_presentations; returns the row id.
    //
    // Used by:
    //   - KnowledgeExamTest (Feature/Knowledge/)
    //   - PublicPagesTest (Feature/Site/)
    // -----------------------------------------------------------

    public function badge(int $userId, int $langId, string $name): int
    {
        return $this->insert('bei_users_badges', ['user_id' => $userId, 'lang_id' => $langId, 'name' => $name]);
    }






    // -----------------------------------------------------------
    // selfEvalAttempt
    // -----------------------------------------------------------
    //
    // A finished self-evaluation of the category by the
    // member — the columns set its points and the like;
    // returns its id.
    //
    // Used by:
    //   - PublicPagesTest (Feature/Site/)
    // -----------------------------------------------------------

    public function selfEvalAttempt(int $userId, int $categoryId, int $langId, array $columns = []): int
    {
        return $this->insert('bei_users_selfeval_test', array_merge([
            'public_id' => $this->publicId(),
            'cat_id'    => $categoryId,
            'user_id'   => $userId,
            'lang_id'   => $langId,
            'finished'  => 1,
            'started'   => date('Y-m-d H:i:s', time() - 600),
            'ended'     => date('Y-m-d H:i:s'),
            'points'    => 0,
        ], $columns));
    }






    // -----------------------------------------------------------
    // knowledgeAttempt
    // -----------------------------------------------------------
    //
    // A finished knowledge test of the member in the
    // language — the columns set its results (a pass is above
    // 74) and the like; returns its id.
    //
    // Used by:
    //   - AdminAccessTest (Feature/Admin/)
    //   - CertificateTest, KnowledgeExamTest
    //     (Feature/Knowledge/)
    //   - CourseProgressTest (Feature/Learning/)
    // -----------------------------------------------------------

    public function knowledgeAttempt(int $userId, int $langId, array $columns = []): int
    {
        return $this->insert('bei_users_knowledge_test', array_merge([
            'public_id'    => $this->publicId(),
            'user_id'      => $userId,
            'lang_id'      => $langId,
            'finished'     => 1,
            'started'      => date('Y-m-d H:i:s', time() - 600),
            'ended'        => date('Y-m-d H:i:s'),
            'question_set' => '{}',
            'results'      => 0,
        ], $columns));
    }






    // -----------------------------------------------------------
    // scenarioAttempt
    // -----------------------------------------------------------
    //
    // A finished run of the simulation by the member — the
    // columns set its points and the like; returns its id.
    //
    // Used by:
    //   - AdminAccessTest, AdminSimulationsTest
    //     (Feature/Admin/)
    //   - CourseProgressTest (Feature/Learning/)
    //   - SimulationsTest (Feature/Simulations/)
    //   - PublicPagesTest (Feature/Site/)
    // -----------------------------------------------------------

    public function scenarioAttempt(int $userId, int $scenarioId, int $langId, array $columns = []): int
    {
        return $this->insert('bei_users_scenarios', array_merge([
            'public_id'   => $this->publicId(),
            'user_id'     => $userId,
            'scenario_id' => $scenarioId,
            'lang_id'     => $langId,
            'finished'    => 1,
            'started'     => date('Y-m-d H:i:s', time() - 600),
            'ended'       => date('Y-m-d H:i:s'),
            'points'      => 0,
        ], $columns));
    }






    // -----------------------------------------------------------
    // nextId
    // -----------------------------------------------------------
    //
    // The id the table's auto-increment counter hands out
    // next, as MySQL 5.7 reports it — rolled-back inserts
    // included, since they use ids up too.
    //
    // Used by:
    //   - AdminLearningTest (Feature/Admin/)
    //   - KnowledgeExamTest (Feature/Knowledge/)
    // -----------------------------------------------------------

    public function nextId(string $table): int
    {
        return (int) DB::table('information_schema.TABLES')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->value('AUTO_INCREMENT');
    }






    // -----------------------------------------------------------
    // removeAll
    // -----------------------------------------------------------
    //
    // Deletes every row this World inserted, newest first, and
    // for each of its members the rows the app wrote in
    // MEMBER_TABLES, then puts back as they were the seeded
    // settings rows that setting replaced, so the tests after
    // it find the rows mysql/init.sql seeds — the Apache suite
    // has no transaction to roll them back.
    //
    // Used by:
    //   - ApacheTestCase::tearDown (tests/)
    //   - SchemaTest (Platform/) — shows the seeded settings
    //     come back
    // -----------------------------------------------------------

    public function removeAll(): void
    {
        foreach (array_reverse($this->rows) as list($table, $id)) {
            if ($table === 'bei_users') {
                foreach (self::MEMBER_TABLES as $memberTable) {
                    DB::table($memberTable)->where('user_id', $id)->delete();
                }
            }
            DB::table($table)->where('id', $id)->delete();
        }
        $this->rows = [];

        foreach ($this->replacedSettings as $row) {
            DB::table('bei_settings')->insert($row);
        }
        $this->replacedSettings = [];
    }






    // -----------------------------------------------------------
    // insert
    // -----------------------------------------------------------
    //
    // Inserts one row and remembers it; returns its id.
    //
    // Used by:
    //   - every builder of this class but setting, which
    //     replaces its fixed-id row itself, and
    //     knowledgeLimits, which goes through setting
    // -----------------------------------------------------------

    private function insert(string $table, array $columns): int
    {
        $id = (int) DB::table($table)->insertGetId($columns);
        $this->rows[] = [$table, $id];
        return $id;
    }






    // -----------------------------------------------------------
    // next
    // -----------------------------------------------------------
    //
    // The next serial number of the run.
    //
    // Used by:
    //   - member, manager (above) and the other builders that
    //     name their rows
    // -----------------------------------------------------------

    private function next(): int
    {
        self::$serial++;
        return self::$serial;
    }






    // -----------------------------------------------------------
    // publicId
    // -----------------------------------------------------------
    //
    // A fresh public id of 16 characters, the length the app
    // gives the attempts it creates.
    //
    // Used by:
    //   - selfEvalAttempt, knowledgeAttempt, scenarioAttempt
    //     (above)
    // -----------------------------------------------------------

    private function publicId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
