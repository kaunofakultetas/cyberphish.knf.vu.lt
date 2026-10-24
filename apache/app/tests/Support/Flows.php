<?php

// -----------------------------------------------------------
//  [*] Flows — a member walking through the timed activities
//
//  The steps a member takes through the three activities
//  that keep state between requests — the self-evaluation
//  of a category, a simulation and the knowledge test —
//  posted exactly as their pages post them: question ids
//  travel as MD5 of their MD5 (pbid), options as the a
//  field. Each start returns the public id the app gave
//  the attempt, read from its redirect.
//
//  Used by:
//    - AdminUsersTest (Feature/Admin/)
//    - CourseProgressTest, SelfEvaluationTest
//      (Feature/Learning/)
//    - KnowledgeExamTest (Feature/Knowledge/)
//    - SimulationsTest (Feature/Simulations/)
// -----------------------------------------------------------


namespace Tests\Support;

use App\Models\Members;
use Illuminate\Testing\TestResponse;








// -----------------------------------------------------------
// Flows
// -----------------------------------------------------------
//
// Mixed into the test classes that drive an activity; every
// step signs the member in first, as their browser would
// stay signed in.
//
//   startSelfEvaluation, answerSelfEvaluation
//   startSimulation, chooseSimulationOption
//   startKnowledgeTest, answerKnowledgeQuestion
//
// Used by:
//   - AdminUsersTest (Feature/Admin/)
//   - CourseProgressTest, SelfEvaluationTest
//     (Feature/Learning/)
//   - KnowledgeExamTest (Feature/Knowledge/)
//   - SimulationsTest (Feature/Simulations/)
// -----------------------------------------------------------

trait Flows
{






    // -----------------------------------------------------------
    // startSelfEvaluation
    // -----------------------------------------------------------
    //
    // Starts the category's self-evaluation and returns the
    // attempt's public id.
    //
    // Used by:
    //   - AdminUsersTest (Feature/Admin/)
    //   - CourseProgressTest, SelfEvaluationTest
    //     (Feature/Learning/)
    // -----------------------------------------------------------

    protected function startSelfEvaluation(Members $member, int $categoryId): string
    {
        $location = $this->signInMember($member)
            ->get("/en/self-evaluation-test/$categoryId")
            ->assertRedirect()
            ->headers->get('Location');
        $this->assertMatchesRegularExpression("#/cp/se/[A-Za-z0-9]+/$categoryId\$#", $location);
        return explode('/', parse_url($location, PHP_URL_PATH))[3];
    }






    // -----------------------------------------------------------
    // answerSelfEvaluation
    // -----------------------------------------------------------
    //
    // Posts the chosen options for one question of the
    // attempt.
    //
    // Used by:
    //   - AdminUsersTest (Feature/Admin/)
    //   - CourseProgressTest, SelfEvaluationTest
    //     (Feature/Learning/)
    // -----------------------------------------------------------

    protected function answerSelfEvaluation(Members $member, string $publicId, int $categoryId, int $questionId, array $optionIds): TestResponse
    {
        return $this->signInMember($member)->post("/cp/se/$publicId/$categoryId", [
            'pbid' => md5(md5($questionId)),
            'a'    => $optionIds,
        ]);
    }






    // -----------------------------------------------------------
    // startSimulation
    // -----------------------------------------------------------
    //
    // Starts the simulation in English and returns the run's
    // public id. Type 1 shows feedback after every step, type
    // 2 lists it at the end.
    //
    // Used by:
    //   - SimulationsTest (Feature/Simulations/)
    // -----------------------------------------------------------

    protected function startSimulation(Members $member, int $scenarioId, int $type = 1): string
    {
        $location = $this->signInMember($member)
            ->post("/en/simulation_progress/$scenarioId", ['simulation_type' => $type])
            ->assertRedirect()
            ->headers->get('Location');
        $this->assertMatchesRegularExpression('#/en/simulation/progress/[A-Za-z0-9]+$#', $location);
        return basename(parse_url($location, PHP_URL_PATH));
    }






    // -----------------------------------------------------------
    // chooseSimulationOption
    // -----------------------------------------------------------
    //
    // Posts the option chosen at the current step of the run.
    //
    // Used by:
    //   - SimulationsTest (Feature/Simulations/)
    // -----------------------------------------------------------

    protected function chooseSimulationOption(Members $member, string $publicId, int $optionId): TestResponse
    {
        return $this->signInMember($member)->post("/en/simulation/progress/$publicId", ['a' => $optionId]);
    }






    // -----------------------------------------------------------
    // startKnowledgeTest
    // -----------------------------------------------------------
    //
    // Starts — or resumes — the member's knowledge test in
    // English and returns its public id.
    //
    // Used by:
    //   - KnowledgeExamTest (Feature/Knowledge/)
    // -----------------------------------------------------------

    protected function startKnowledgeTest(Members $member): string
    {
        $location = $this->signInMember($member)
            ->post('/en/knowledge_start')
            ->assertRedirect()
            ->headers->get('Location');
        $this->assertMatchesRegularExpression('#/en/knowledge_test/progress/[A-Za-z0-9]+$#', $location);
        return basename(parse_url($location, PHP_URL_PATH));
    }






    // -----------------------------------------------------------
    // answerKnowledgeQuestion
    // -----------------------------------------------------------
    //
    // Posts the chosen options — none at all is allowed by the
    // page's server side — for one question of the test.
    //
    // Used by:
    //   - KnowledgeExamTest (Feature/Knowledge/)
    // -----------------------------------------------------------

    protected function answerKnowledgeQuestion(Members $member, string $publicId, int $questionId, array $optionIds): TestResponse
    {
        return $this->signInMember($member)->post("/en/knowledge_test/progress/$publicId", [
            'pbid' => md5(md5($questionId)),
            'a'    => $optionIds,
        ]);
    }
}
