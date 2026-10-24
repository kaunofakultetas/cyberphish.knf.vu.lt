<?php

// -----------------------------------------------------------
//  [*] KnowledgeDefectsTest — known knowledge-test defects
//
//  Each test states the behaviour the knowledge test should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into KnowledgeExamTest.
//
//    knowledge-gate-language     — the lessons gate reads the
//                                  session's language, the
//                                  attempt the URL's
//    knowledge-badge-language    — a closing attempt's badge
//                                  goes to the URL's language
//    knowledge-unknown-language  — closing under an unknown
//                                  language code is a 500 and
//                                  loses the badge
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Knowledge;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// KnowledgeDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; three
// English questions drawn from one category, and a member
// who has done every English lesson, for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class KnowledgeDefectsTest extends FeatureTestCase
{

    // Starting the test and answering its questions
    use Flows;

    // The English id, the questions and the member of the
    // test running now
    private $en;
    private $questions = [];
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // Three questions, each with one right option first, all
    // three drawn; the member holds the English
    // badge_all_presentations.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $category = $this->world->lmCategory($this->en);
        for ($i = 0; $i < 3; $i++) {
            $this->questions[] = $this->world->knowledgeQuestion($category, [['Right', true], ['Wrong', false]]);
        }
        $this->world->knowledgeLimits([$this->en => [$category => 3]]);
        $this->member = $this->world->member(['fullname' => 'Jonas Jonaitis']);
        $this->world->badge($this->member->id, $this->en, 'badge_all_presentations');
    }






    // -----------------------------------------------------------
    // testLessonsGateFollowsTheTestsLanguage
    // -----------------------------------------------------------
    //
    // The gate asks Badges::get_badge, which reads the
    // session's language, while the attempt takes the URL's —
    // and the session moves only for the five exact codes,
    // where the language lookup ignores case. A member with
    // every Lithuanian lesson done, none in English, browsing
    // in Lithuanian, starts an English test at /EN/. Wanted:
    // no English attempt without the English lessons.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonsGateFollowsTheTestsLanguage(): void
    {
        $lt = $this->world->language('lt');
        $member = $this->world->member(['fullname' => 'Ona Onaite']);
        $this->world->badge($member->id, $lt, 'badge_all_presentations');

        $this->signInMember($member)->withSession(['lang' => 'lt'])->post('/EN/knowledge_start');

        $this->pinDefect('knowledge-gate-language', function () use ($member) {
            $this->assertFalse(DB::table('bei_users_knowledge_test')->where(['user_id' => $member->id, 'lang_id' => $this->en])->exists(), 'an English attempt was drawn without the English lessons');
        });
    }






    // -----------------------------------------------------------
    // testClosingAwardsTheAttemptsLanguage
    // -----------------------------------------------------------
    //
    // The visit that closes an attempt awards badge_final_test
    // in the URL's language while the attempt keeps its own:
    // an English pass closed under /lt/ earns the Lithuanian
    // badge, and later visits take the closed-before path,
    // which awards nothing, so English never gets it. Wanted:
    // the badge of the attempt's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testClosingAwardsTheAttemptsLanguage(): void
    {
        $lt = $this->world->language('lt');
        $publicId = $this->answerEverythingRight();
        $this->signInMember($this->member)->get("/lt/knowledge_test/progress/$publicId");

        $this->pinDefect('knowledge-badge-language', function () use ($lt) {
            $this->assertFalse($this->hasFinalBadge($lt), 'an English pass earned the Lithuanian badge');
            $this->assertTrue($this->hasFinalBadge($this->en), 'the English pass earned no English badge');
        });
    }






    // -----------------------------------------------------------
    // testUnknownLanguageClosingIsNoServerError
    // -----------------------------------------------------------
    //
    // Under a language code no language has, the closing visit
    // closes the attempt, then stores badge_final_test with no
    // language — the NOT NULL column refuses it, a 500 — and
    // the attempt, closed already, never awards it again.
    // Wanted: no server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownLanguageClosingIsNoServerError(): void
    {
        $publicId = $this->answerEverythingRight();
        $status = $this->signInMember($this->member)->get("/xx/knowledge_test/progress/$publicId")->getStatusCode();

        $this->pinDefect('knowledge-unknown-language', function () use ($status) {
            $this->assertNotEquals(500, $status, 'the closing visit under an unknown language code is a server error');
        });
    }






    // -----------------------------------------------------------
    // answerEverythingRight
    // -----------------------------------------------------------
    //
    // Starts the English test and answers its three questions
    // right, without the visit that closes it; returns the
    // attempt's public id.
    //
    // Used by:
    //   - the tests of this class
    // -----------------------------------------------------------

    private function answerEverythingRight(): string
    {
        $publicId = $this->startKnowledgeTest($this->member);
        foreach ($this->questions as $question) {
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], [$question['options'][0]]);
        }
        return $publicId;
    }






    // -----------------------------------------------------------
    // hasFinalBadge
    // -----------------------------------------------------------
    //
    // Whether the member holds badge_final_test in the
    // language.
    //
    // Used by:
    //   - the tests of this class
    // -----------------------------------------------------------

    private function hasFinalBadge(int $langId): bool
    {
        return DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'lang_id' => $langId, 'name' => 'badge_final_test'])
            ->exists();
    }
}
