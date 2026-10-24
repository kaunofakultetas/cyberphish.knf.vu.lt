<?php

// -----------------------------------------------------------
//  [*] KnowledgeDefectsTest — known defects of the knowledge test
//
//  Each test states the behaviour the knowledge test should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into its home file.
//
//    knowledge-question-without-correct — a question with
//      no correct option is drawn into members' tests
//    progress-shown-before-the-close — the page that closes
//      a test shows the progress from before it counted
//      (SimulationDefectsTest too)
//    knowledge-page-bold — the question page never closes
//      its header's bold tag
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Knowledge;

use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// KnowledgeDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; English
// and a main admin for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class KnowledgeDefectsTest extends FeatureTestCase
{

    // Starting and answering self-evaluations and knowledge
    // tests
    use Flows;

    // The English id and the main admin of the test running
    // now
    private $en;
    private $admin;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English and a main admin.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->admin = $this->world->manager();
    }






    // -----------------------------------------------------------
    // testQuestionWithoutACorrectOptionIsNeverDrawn
    // -----------------------------------------------------------
    //
    // An option line the import does not recognise is skipped
    // — here the correct one, indented by a space — so the
    // knowledge import stores the question with wrong options
    // only, as it does any block without an "=" line, and the
    // edit page lets every option be set incorrect. The
    // knowledge test draws every question with options, so a
    // member who gets this one loses it whatever they choose,
    // in a test passed at 75 %. The self-evaluation import
    // gives such a question no type, and the self-evaluation
    // never asks it. Wanted: a member's test draws no question
    // without a correct option, or the import refuses the
    // block.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionWithoutACorrectOptionIsNeverDrawn(): void
    {
        $category = $this->world->lmCategory($this->en);
        $this->signInManager($this->admin)->from("/admin-panel/lm/knowledge_test/$category")->post('/admin-panel/lm/knowledge_test/import', [
            'cat_id' => $category,
            'doc'    => $this->uploadedFile('questions.txt', "::\nQ\\: Who may ask for your password?\n =Nobody\n~Your bank\n~The IT desk\n"),
        ]);
        $member = $this->world->member();
        $this->world->badge($member->id, $this->en, 'badge_all_presentations');
        $this->world->knowledgeLimits([$this->en => [$category => 1]]);

        $this->signInMember($member)->post('/en/knowledge_start');
        $drawn = array_keys(json_decode(DB::table('bei_users_knowledge_test')->where('user_id', $member->id)->value('question_set') ?? '{}', true));
        $unanswerable = array_values(array_filter($drawn, function ($question) {
            return !DB::table('bei_knowledge_option')->where(['question_id' => $question, 'correct' => 1])->exists();
        }));

        $this->pinDefect('knowledge-question-without-correct', function () use ($unanswerable) {
            $this->assertSame([], $unanswerable, 'the test drew a question no option of which is correct');
        });
    }






    // -----------------------------------------------------------
    // testClosingPageCountsThePassInTheCourseProgress
    // -----------------------------------------------------------
    //
    // The page a member lands on after the last answer of a
    // passed attempt says the member passed, while the course
    // progress the same page shows, in the member menu and
    // above the result, still leaves the pass out:
    // BaseController works the figure out before
    // KnowledgeTestController::progress closes the attempt. A
    // member whose only progress is this pass reads "You
    // passed!" beside 0%, and only the next page shows the 30%
    // the pass is worth. The last answer is posted and every
    // redirect followed, so the check holds whether a fix works
    // the figure out after the close or closes the attempt and
    // redirects to a fresh page; either way the landing page
    // must still announce the pass and the new badge. Wanted:
    // the page that announces the pass counts it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testClosingPageCountsThePassInTheCourseProgress(): void
    {
        $en = $this->world->language('en');
        $category = $this->world->lmCategory($en);
        $question = $this->world->knowledgeQuestion($category, [['Right', true], ['Wrong', false]]);
        $this->world->knowledgeLimits([$en => [$category => 1]]);
        $member = $this->world->member();
        $this->world->badge($member->id, $en, 'badge_all_presentations');
        $publicId = $this->startKnowledgeTest($member);

        $landing = $this->signInMember($member)->followingRedirects()
            ->post("/en/knowledge_test/progress/$publicId", [
                'pbid' => md5(md5($question['question'])),
                'a'    => [$question['options'][0]],
            ])
            ->assertOk()
            ->assertSee('You passed!')
            ->assertSee(__('main.new_badge'), false)
            ->getContent();
        $this->signInMember($member)->get('/en/knowledge_test')
            ->assertOk()
            ->assertSee('<span class="float-right">30%</span>', false);

        $this->pinDefect('progress-shown-before-the-close', function () use ($landing) {
            $this->assertStringNotContainsString('<span class="float-right">0%</span>', $landing, 'the page announcing the pass shows the course progress without it');
            $this->assertStringContainsString('<span class="float-right">30%</span>', $landing);
        });
    }






    // -----------------------------------------------------------
    // testKnowledgeQuestionPageClosesItsBoldHeader
    // -----------------------------------------------------------
    //
    // The card header of knowledge_progress.blade.php opens a
    // bold tag before its title and never closes it. The
    // browser's parser keeps the bold open and carries it into
    // the card's body and on to the page's end, so the options
    // to choose from, the Next button and the footer all show
    // in bold on every question of the test, unlike the
    // self-evaluation's question page next to it. Wanted: only
    // the header and the question in bold, the options not.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testKnowledgeQuestionPageClosesItsBoldHeader(): void
    {
        $en = $this->world->language('en');
        $category = $this->world->lmCategory($en);
        $this->world->knowledgeQuestion($category, [['Right', true], ['Wrong', false]]);
        $this->world->knowledgeLimits([$en => [$category => 1]]);
        $member = $this->world->member();
        $this->world->badge($member->id, $en, 'badge_all_presentations');
        $publicId = $this->startKnowledgeTest($member);

        $html = $this->signInMember($member)->get("/en/knowledge_test/progress/$publicId")->assertOk()->getContent();
        $labels = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('.custom-control-label');

        $bold = 0;
        foreach ($labels as $label) {
            if ($label->closest('b') !== null) {
                $bold++;
            }
        }

        $this->assertCount(2, $labels);
        $this->pinDefect('knowledge-page-bold', function () use ($bold) {
            $this->assertSame(0, $bold, 'options of the question show in bold');
        });
    }
}
