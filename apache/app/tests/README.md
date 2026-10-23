# Apache app regression tests

The PHPUnit suite of the CYBERPHISH app (Laravel 13.38 on PHP 8.4.27). It
runs in one throwaway container with no network, built from the
production image. Inside that container it starts MySQL 5.7.42 with the
schema from `mysql/init.sql` and Apache serving the app the way
production does, then runs PHPUnit 13.4.

## Running

From `apache/`:

```sh
./runTests.sh                           # every suite
./runTests.sh --testsuite Apache        # one suite: Platform, Feature or Apache
./runTests.sh --filter SessionTest      # one class, or one test by name
./runTests.sh tests/Feature/Knowledge   # one folder or file
```

Each run does three things:

1. Builds the production `Dockerfile` from the working tree as
   `cyberphish-apache-check`, so the suite tests what would ship. The
   published `vuknf/cyberphish-apache` image is never touched.
2. Adds `Dockerfile.tests` on top.
3. Starts the result with `--network none` and the production
   container's own settings: read-only root filesystem, the same tmpfs
   mounts, uid 1000.

Every argument goes to PHPUnit. When PHPUnit fails, `container/run.sh`
also prints what the server side logged: the tail of Apache's error
log, the latest Laravel log entries, and MySQL's errors.

The host needs only docker. PHP, MySQL and the test tools live in the
image.

## Layers

| Suite | Folder | What it checks | How |
|---|---|---|---|
| Platform | `Platform/` | The image as shipped: the PHP runtime, its extensions and production php.ini, no dev packages and only the locked versions, Laravel's production config, the schema `init.sql` builds, the Apache configuration | Reads the container's files and the database |
| Feature | `Feature/` | Every page and form of the app: public pages, registration, login and password reset, account settings, learning material, self-evaluations, simulations, the knowledge test and certificate, the admin panel, the route table | Laravel's in-process HTTP kernel; each test runs inside a transaction that is rolled back |
| Apache | `Apache/` | What happens in front of the app: the front controller, which files Apache never serves, scripts in the upload folder never running, how errors look in production mode, sessions over real HTTP | Requests to the real Apache on `127.0.0.1:8080`, carrying the headers the Caddy endpoint adds; rows are committed and deleted after each test |

## Layout

| Path | What lives there |
|---|---|
| `TestCase.php` | Base of every test: the World, `Mail::fake`, the captcha double (`solveCaptcha`), the sign-in helpers (`signInMember`, `signInManager`), `mailLink`, `uploadedFile` |
| `FeatureTestCase.php` | `TestCase` plus `DatabaseTransactions` |
| `ApacheTestCase.php` | `TestCase` plus an `ApacheClient`, World cleanup, and `signInOverHttp` |
| `CreatesApplication.php` | The fresh app every test gets, built from `bootstrap/app.php` |
| `bootstrap.php` | PHPUnit's bootstrap: the app's autoloader, the test tools' from `/opt/test-tools`, and the `Tests` namespace |
| `Support/World.php` | Builders for every kind of row the app reads, with valid defaults |
| `Support/Flows.php` | Multi-step member flows: starting and answering self-evaluations, simulations and the knowledge test |
| `Support/Sheets.php` | The simulation spreadsheet the panel's import reads, with cells a test can replace |
| `Support/KnownDefects.php` | `pinDefect`, see Rules |
| `Support/ApacheClient.php` | Guzzle client for the real Apache, with its own cookie store |
| `container/` | `run.sh`, the image's entrypoint, plus `my.cnf` for MySQL and the composer files of the test tools |
| `Platform/`, `Feature/<Area>/`, `Apache/` | The suites |

Four files outside `tests/` belong to the suite:

- `apache/runTests.sh` runs everything.
- `apache/Dockerfile.tests` builds the test image.
- `apache/app/phpunit.xml` names the three suites and their bootstrap.
- `apache/.dockerignore` keeps the tests out of the production image.

## Rules

- **Offline.** Nothing reaches Google or Mailjet:
  - Feature tests fake the mailer and mock the captcha.
  - Over HTTP, the unreachable Google is itself under test (`ProductionModeTest`).
- **Production-shaped.**
  - Apache runs with no `.env`, `APP_DEBUG` off, and the environment
    `docker-compose.yml` gives the production container, with test
    values in place of the secrets.
  - The app's `vendor/` stays `--no-dev`. PHPUnit and Mockery live
    apart, in `/opt/test-tools`.
- **Every test builds its own rows** through the World. A test relies
  on nothing but the seed rows of `init.sql`. Emails, user names and
  titles carry a serial number that is unique for the whole run.
- **No bug is fixed from here.** A test that finds a defect does not
  change the app. It pins the defect in its folder's `*DefectsTest.php`
  with `pinDefect('id', fn)`:
  - The closure asserts the wanted behaviour.
  - While the defect lives, the test is reported as incomplete, tagged
    with its id, and the run stays green.
  - Once a fix makes the closure pass, the test fails with "no longer
    reproduces". That failure is the cue to drop the pin and move the
    test into its home file.
- **House style.** Banners follow the house-comments skill and its PHP
  8.4 reference. The suite is PHP 8.4 code since the upgrade; apart from
  the four `#[DataProvider]` attributes PHPUnit 13 requires, it still
  uses only PHP 7.2 syntax. From the repository root, this gate must
  print `-- 0 violation(s)`:

  ```sh
  python3 -B .claude/skills/house-comments/scripts/house_check_php84.py --new apache/app/tests
  ```

## Pinned defects

None at the moment. The last five waited on the production database
and were fixed in October 2026 (see Fixed defects).

The move to Laravel 13 released two pins, through the framework's own
changes rather than any fix in the app. They are now ordinary tests in
their home files:

- `news-svg-picture` → `AdminContentTest::testSvgNewsPictureIsRefused`.
  Laravel 13's `image` rule refuses SVG unless it is given `allow_svg`.
- `session-cookie-secure` → `SessionTest::testSessionCookieIsSecure`.
  With `SESSION_SECURE_COOKIE` unset, Laravel 13 marks the cookie Secure
  on HTTPS requests, and behind the endpoint every request is HTTPS.

## Fixed defects

A first batch of 34 pins was fixed in October 2026, each with a change
small enough that no user should notice it. Their tests now live in their
home files, without the pin:

| Id | Home test |
|---|---|
| `knowledge-category-lookup` | `KnowledgeExamTest::testAnswerIsFiledUnderItsQuestionsCategory` |
| `knowledge-attempt-owner` | `KnowledgeExamTest::testAnotherMembersAttemptIsNotFound` |
| `knowledge-attempt-limit` | `KnowledgeExamTest::testFourthAttemptIsRefused` |
| `knowledge-repeated-option` | `KnowledgeExamTest::testRepeatedOptionCountsOnce` |
| `knowledge-unknown-attempt` | `KnowledgeExamTest::testUnknownAttemptIsNotFound` |
| `knowledge-reclose` | `KnowledgeExamTest::testFinishedAttemptStaysClosed` |
| `selfeval-foreign-options` | `SelfEvaluationTest::testRepeatedOptionScoresOnce`, `testForgedAnswersAreNotStored` |
| `selfeval-reclose` | `SelfEvaluationTest::testFinishedAttemptKeepsItsEndDate` |
| `simulation-foreign-option` | `SimulationsTest::testRunNeverScoresAboveItsBestPath` |
| `simulation-other-language` | `SimulationsTest::testSimulationOfAnotherLanguageIsNotFound` |
| `simulation-results-mail-repeat` | `SimulationsTest::testFinishedRunMailsItsResultOnce` |
| `verify-blank-token` | `VerificationTest::testBlankLinkLeavesDeactivatedAccountsAlone` |
| `password-flash` | `RegistrationTest::testFailedSignUpKeepsNoPasswordInTheSession` |
| `password-trim` | `RegistrationTest::testPasswordWithBlankEdgesRegisters` |
| `register-while-signed-in` | `RegistrationTest::testSignedInMemberIsSentAwayFromTheRegistrationForm` |
| `login-throttle` | `LoginTest::testTenWrongPasswordsLockTheLogin`, `testNineWrongPasswordsStillLetTheMemberIn` |
| `country-out-of-range` | `AccountSettingsTest::testMemberOfAnUnknownCountryOpensTheDashboard`, and the "country out of range" case of `RegistrationTest::testIncompleteFormIsRefused` |
| `username-length` | `AccountSettingsTest::testUsernameTheColumnCannotHoldIsRefused`, `testUsernameOfFortyFiveCharactersIsTaken` |
| `manager-country-scope` | `AdminAccessTest::testCountryManagerReadsOnlyItsCountrysReports`, `testMainAdminReadsEveryCountrysReports` |
| `knowledge-report-unknown` | `AdminAccessTest::testUnknownKnowledgeReportIsNotFound` |
| `manager-update-main` | `AdminManagersTest::testMainAdminIsNeverRewritten` |
| `admin-login-throttle` | `AdminLoginTest::testTenWrongPasswordsLockTheAdminLogin` |
| `selfeval-form-route` | `AdminLearningTest::testQuestionFormPathIsNotFound` |
| `lesson-file-delete-unknown` | `AdminLearningTest::testRepeatedFileDeleteLinkIsHarmless` |
| `seed-settings-rows` | `AdminContentTest::testSettingsAreSaved`, which no longer creates rows 1 and 3 itself, and `SchemaTest::testSeedRowsArePresent` |
| `ranks-query-per-member` | `PublicPagesTest::testRankingQueriesDoNotGrowWithTheMembers`, with `testRankingShowsEachBadgeOnceInTheOrderEarned` keeping the page as it was |
| `client-ip-header` | `SessionTest::testRecordedAddressIsTheOneApacheSaw` |
| `expose-php` | `ProtectionTest::testResponsesDoNotNamePhp` |
| `server-banner` | `ProtectionTest::testResponsesDoNotNameApachesVersion` |
| `trailing-slash-http` | `FrontControllerTest::testTrailingSlashIsRedirectedAway`, which now checks the whole `Location` |
| `captcha-outage-500` | `ProductionModeTest::testRegistrationSurvivesAGoogleOutage` |
| `captcha-outage-reset` | `ProductionModeTest::testResetRequestSurvivesAGoogleOutage` |
| `remoteip-trust-range` | `ApacheConfigTest::testOnlyPrivateProxiesAreTrusted` |
| `seed-admin-password` | `SchemaTest::testSeededAdminHasNoKnownPassword` |

A second batch, later that month, fixed every pin whose fix lies in the
Apache code alone, visible changes included: skipped knowledge-test
questions count as wrong, changed passwords need the login's 12 to 50
characters, a full name may hold the form's 100 characters, and the
certificate shrinks a name too wide for its line. Nine defects found on
the way were pinned and fixed with them: `weak-tokens`,
`username-draw-cost`, `verify-message-lost`, `reset-message-lost`,
`cors-wildcard`, `upload-htaccess`, `certificate-name-wrap`,
`upload-folder-readonly` and `news-picture-remove`.

| Id | Home test |
|---|---|
| `home-about-missing` | `PublicPagesTest::testHomePageOpensInALanguageWithoutAboutText` |
| `news-page-links` | `PublicPagesTest::testNewsPageLinkOpensPageTwo` |
| `cors-wildcard` | `PublicPagesTest::testResponsesDoNotInviteEveryOrigin` |
| `login-error-lost` | `LoginTest::testFailedLoginShowsItsError` |
| `login-badge-message` | `LoginTest::testLoginThatEarnsTheBadgeAnnouncesIt` |
| `captcha-message` | `RegistrationTest::testCaptchaErrorsReadInWords` |
| `weak-tokens` | `PasswordResetTest::testTokensDoNotFollowTheMersenneTwistersSeed` |
| `username-draw-cost` | `RegistrationTest::testUserNameIsDrawnWithoutAMillionNumbers` |
| `verify-message-lost` | `VerificationTest::testVerificationGreetsAVisitorSentFromAMemberPage` |
| `reset-message-lost` | `PasswordResetTest::testNewPasswordMessageReachesAVisitorSentFromAMemberPage` |
| `password-policy` | the "new under 12 characters" and "new over 50 characters" cases of `AccountSettingsTest::testPasswordChangeIsRefused` |
| `fullname-length` | `AccountSettingsTest::testLongFullNameIsStored` |
| `ee-strings` | `AccountSettingsTest::testEstonianUserNameFormSpeaksOfTheUserName` |
| `lesson-badge-chain` | `LearningMaterialTest::testCompletionAwardsEveryBadgeThatFallsDue` |
| `badge-duplicate` | `LearningMaterialTest::testBadgeStoredTwiceStillReadsAsHeld` |
| `selfeval-get-creates` | `SelfEvaluationTest::testRevisitingTheStartPageOpensNoSecondAttempt` |
| `selfeval-short-category` | `SelfEvaluationTest::testAttemptClosesWhenTheCategoryRunsOutOfQuestions` |
| `selfeval-badge-count` | `SelfEvaluationTest::testFirstFinishedSelfEvaluationEarnsTheBadge` |
| `selfeval-badge-locale` | `SelfEvaluationTest::testNewBadgeMessageIsInTheMembersLanguage` |
| `lang-no-questions` | `SelfEvaluationTest::testCategoryWithoutQuestionsSaysSo` |
| `simulation-without-category` | `SimulationsTest::testSimulationWithoutACategoryOpens` |
| `simulation-badge-message` | `SimulationsTest::testHeldBadgeIsNotAnnouncedAgain` |
| `knowledge-skipped-answers` | `KnowledgeExamTest::testSkippedQuestionsCountAsWrong` |
| `knowledge-all-skipped` | `KnowledgeExamTest::testTestWithEveryQuestionSkippedCloses` |
| `knowledge-limits-missing` | `KnowledgeExamTest::testLanguageMissingFromTheLimitsDrawsNothing` |
| `knowledge-badge-later-pass` | `KnowledgeExamTest::testPassOnALaterTryEarnsTheBadge` |
| `certificate-greek-name` | `CertificateTest::testGreekNamePrintsInGreekLetters` |
| `certificate-name-wrap` | `CertificateTest::testNameStaysOnItsLine`, with `testLatinNameKeepsTheScriptFont` keeping the usual names as they were |
| `admin-get-deletes` | `AdminUsersTest::testDeletingAMemberNeedsMoreThanALink`; the panel's other delete and status tests now post |
| `admin-news-shadowed` | `AdminContentTest::testNewsPagesOfThePanelReachThePanel`, and `AdminAccessTest`'s sweeps, which no longer leave the news pages out |
| `news-picture-remove` | `AdminContentTest::testRemovingAMissingPictureIsHarmless` |
| `empty-body-500` | `AdminContentTest::testNewsWithAnEmptyBodyIsSaved` |
| `info-create-intended` | `AdminContentTest::testNewInformationPageReturnsToTheList` |
| `language-rules` | `AdminContentTest::testTakenLanguageNameIsRefused` |
| `language-delete-in-use` | `AdminContentTest::testLanguageStillInUseIsRefusedWithAMessage` |
| `simulations-pdf-categories` | `AdminContentTest::testEachScenarioOfThePrintoutShowsItsOwnCategories` |
| `admin-login-error-lost` | `AdminLoginTest::testFailedAdminLoginShowsItsError` |
| `admin-pass-messages` | `AdminLoginTest::testPasswordDialogAnswersInWords` |
| `manager-country-update` | `AdminManagersTest::testManagerCountryChangeIsSaved` |
| `selfeval-greek-import` | `AdminLearningTest::testGreekQuestionsAreImported` |
| `import-markers` | `AdminLearningTest::testImportedOptionsKeepTheirText` |
| `question-update-no-options` | `AdminLearningTest::testQuestionWithoutOptionsIsSaved` |
| `lesson-delete-missing-file` | `AdminLearningTest::testLessonWithAMissingFileCanBeDeleted` |
| `upload-folder-readonly` | `AdminLearningTest::testDeletesWorkOnAReadOnlyUploadFolder` |
| `lesson-file-embed` | `AdminLearningTest::testFileWithoutEmbedIsListedForDownload` |
| `lesson-file-missing` | `AdminLearningTest::testFilePostWithoutAFileGetsAnError` |
| `lm-delete-anchor` | `AdminLearningTest::testDeletesReturnToTheLanguageTab` |
| `category-delete-orphans` | `AdminLearningTest::testCategoryWithQuestionsIsNotDeleted`: the delete is refused while questions remain |
| `simulation-delete-links` | `AdminSimulationsTest::testDeleteIconsDelete` |
| `simulation-import-partial` | `AdminSimulationsTest::testFailedImportLeavesNothingBehind` |
| `import-cross-language` | `AdminSimulationsTest::testImportFilesUnderTheCategoriesOfItsLanguage` |
| `simulation-update-descr` | `AdminSimulationsTest::testDescriptionOfASimulationWithoutStepsIsSaved` |
| `upload-htaccess` | `ProtectionTest::testUploadedHtaccessChangesNothing`, and the `AllowOverride None` check of `ApacheConfigTest::testUploadFolderRunsNoPhp` |

A third batch, the same month, fixed the five pins that waited on the
production database. A one-time change to it turned `bei_users` from
latin1 into utf8mb4, gave the reset token a column of its own,
`reset_token`, and made the stored function `bei_user_course_progress`
count self-evaluations per category and treat a missing share as 0.
`mysql/init.sql` builds a fresh database the same way.

| Id | Home test |
|---|---|
| `reset-link-logout` | `PasswordResetTest::testSigningOutKeepsAPendingResetLink` |
| `username-lithuanian` | `AccountSettingsTest::testUsernameWithLithuanianLettersIsSaved` |
| `fullname-latin1` | `AccountSettingsTest::testFullNameWithLithuanianLettersIsStoredAsTyped`, with `CertificateTest::testLithuanianNamePrintsEveryLetter` for the certificate it feeds |
| `course-progress-selfeval` | `CourseProgressTest::testFinishedSelfEvaluationsCountTheirFullShare` |
| `course-progress-null` | `CourseProgressTest::testLanguageWithoutSimulationsStillShowsTheProgress`, `testPassedKnowledgeTestCountsWithoutLessons` |

A fourth batch, the same month, fixed the low-severity defects a
multi-agent review of the whole app found. Each test was seen failing on
the code before its fix. Some changes are visible:

- signing out posts a form from the member and admin menus;
- the reset link opens a page whose button mails the new password;
- dates show in Eastern European Time, and are still stored in UTC;
- the knowledge test opens only once every lesson of the language is
  done, as its sidebar button always waited for;
- the user-name form asks for the 6 to 45 characters the server takes.

| Id | Home test |
|---|---|
| `username-error-message` | `AccountSettingsTest::testRefusedUsernameSaysWhyInTheMembersLanguage`, `testUsernameFormAsksForWhatTheServerTakes`, `testRefusedFullNameSaysWhyInTheMembersLanguage` |
| `logout-on-get` | `LoginTest::testSigningOutNeedsAPost`, `AdminLoginTest::testSigningOutNeedsAPost` |
| `reset-link-prefetch` | `PasswordResetTest::testOpeningTheResetLinkChangesNothing` |
| `selfeval-overshoot` | `SelfEvaluationTest::testAttemptWithMoreAnswersThanItsLengthCloses` |
| `selfeval-length-zero` | `AdminContentTest::testSelfEvaluationLengthBelowOneIsRefused`, `SelfEvaluationTest::testLengthDroppingToZeroClosesTheAttempt` |
| `selfeval-answer-race` | `SelfEvaluationTest::testAnswerStoredTwiceScoresOnce`, which checks the state a race leaves; the lock itself is not testable (see Found but not pinned) |
| `selfeval-unanswerable-question` | `SelfEvaluationTest::testQuestionWithoutAnswersIsNeverAsked` |
| `selfeval-other-language` | `SelfEvaluationTest::testAttemptTakesItsCategorysLanguage` |
| `knowledge-without-lessons` | `KnowledgeExamTest::testTestNeedsEveryLessonDone` |
| `knowledge-limit-blank` | `KnowledgeExamTest::testBlankLimitDrawsNothing`, `AdminContentTest::testBlankKnowledgeLimitIsStoredAsZero` |
| `knowledge-start-race` | none — see Found but not pinned |
| `dates-in-utc` | `SelfEvaluationTest::testTimesShowInLocalTime`, `CertificateTest::testCertificateIsDatedInLocalTime` |
| `simulation-start-any-language` | `SimulationsTest::testRunOfAnotherLanguagesSimulationIsNotStarted` |
| `simulation-mail-outage` | `SimulationsTest::testMailOutageStillShowsTheEndPage` |
| `cookie-link-language` | `PublicPagesTest::testCookieNoticeKeepsTheLanguage` |
| `page-number-overflow` | `PublicPagesTest::testHugePageNumberOpensTheNewsList`, `AdminUsersTest::testHugePageNumberOpensTheMemberList` |
| `sim-import-missing-file` | `AdminSimulationsTest::testImportWithoutAFileSaysSo` |
| `selfeval-history-denominator` | `AdminUsersTest::testSelfEvaluationHistoryCountsOutOfTheAttemptsQuestions` |
| `update-lang-unchecked` | `AdminContentTest::testUpdatesNeedALanguageThatExists` |
| `users-intended-redirect` | `AdminUsersTest::testMemberActionsReturnToTheMemberList` |
| `language-delete-simulations` | `AdminContentTest::testLanguageWithSimulationsIsNotDeleted` |
| `disable-functions-noop` | `PhpRuntimeTest::testDisabledListNamesNoLanguageConstruct` |
| `recaptcha-language-codes` | `RegistrationTest::testCaptchaSpeaksGreekAndEstonian` |
| `optional-before-required` | `PhpRuntimeTest::testNoFunctionTakesAnOptionalParameterBeforeARequiredOne` |

A fifth batch, the same day, fixed the six medium-severity defects of
that review. Each test was again seen failing on the code before its
fix. Behaviour that changed:

- an account switched off in the panel is signed out at its next page;
- a manager's main flag and country follow the row at once;
- deleting a member deletes every row of theirs;
- a knowledge-test question that can no longer be answered counts as
  wrong instead of holding the attempt open.

| Id | Home test |
|---|---|
| `switched-off-session` | `AdminUsersTest::testSwitchingAMemberOffEndsTheirSession`, `AdminManagersTest::testSwitchedOffManagerLosesTheSession`, `testDemotedMainAdminLosesTheMainPages` |
| `member-delete-orphans` | `AdminUsersTest::testDeletingAMemberLeavesNothingOfTheirs` |
| `knowledge-deleted-question` | `KnowledgeExamTest::testDeletedQuestionDoesNotHoldTheAttempt`, `testQuestionWithoutOptionsIsNotDrawn` |
| `knowledge-answer-race` | `KnowledgeExamTest::testAnswerStoredTwiceCountsOnce`, which checks the state a race leaves; the lock itself is not testable (see Found but not pinned) |
| `simulation-choice-race` | `SimulationsTest::testChoiceStoredTwiceScoresOnce`, likewise |
| `knowledge-import-markers` | `AdminLearningTest::testKnowledgeQuestionsOfEveryLanguageAreImported` |

Three more followed the same day. `bei_managers` became utf8mb4, the
last latin1 table, in production and in `mysql/init.sql`; so did the
last two latin1 defaults, the table default of `bei_lm_category` and the
database's own. The
self-evaluation progress bar counts towards the attempt's real length.
The translation files hold each key once, which gives the Greek sign-up
button back its "Εγγραφείτε".

| Id | Home test |
|---|---|
| `managers-latin1` | `SchemaTest::testEveryTextColumnIsUtf8mb4`, `testTablesAndTheDatabaseDefaultToUtf8mb4` |
| `selfeval-progress-length` | `SelfEvaluationTest::testProgressBarCountsTowardsTheAttemptsLength` |
| `duplicate-strings` | `RegistrationTest::testGreekSignUpButtonSaysRegister`, `ConfigTest::testTranslationFilesHoldEachKeyOnce` |

No `*DefectsTest.php` file is left; a new pin starts its folder's file
again.

## Found but not pinned

These were found during review but not pinned. Some have no impact in
production; others can't be tested deterministically.

- **Races closed with row locks.** Posts that arrive at the same moment
  each passed the app's check before either stored its row. This held
  for two answers to one self-evaluation question, two knowledge-test
  starts, two answers to one knowledge-test question, and two choices
  for one simulation step. Each is now checked and stored inside a
  transaction that first locks a row: the attempt's in
  `LMController::self_eval_test_save` and
  `KnowledgeTestController::save_answer`, the member's in
  `KnowledgeTestController::start`, the run's in
  `SimulationsController::simulation_do_stuff_save`. The suite runs its
  requests one after another, so it cannot force the interleaving. The
  three scorings are checked on the duplicates a race once left behind;
  the knowledge start has no test.
- **Unreachable retries.** `LMController::gen_test_id` and
  `SimulationsController::gen_test_id` retried through a method their
  models do not have; they now retry through their own class. The
  retry runs only when two random ids collide, so no test reaches it.
- **Dependencies.** Since the PHP 8.4 upgrade the imports use
  phpoffice/phpspreadsheet 5.10.0, and `composer audit` finds no
  advisory for any locked package.

## Practical notes

Things the suite learned the hard way:

- **`REMOTE_ADDR`.** `BlockIpMiddleware` runs on every request and reads
  `$_SERVER['REMOTE_ADDR']` directly. Laravel's test requests don't set
  that superglobal, so `TestCase::setUp` does.
- **Guards hold a `GenericUser`.** Both guards (`mem` for members, `man`
  for managers) use the `database` provider, so a signed-in user is not
  a `Members` or `Managers` model.
  - The sign-in helpers fetch the user through the guard's provider.
  - `assertAuthenticatedAs($model)` fails. Use `assertAuthenticated`
    and compare ids.
  - The default guard is `man`.
- **Mail and captcha.**
  - `Mail::fake` is always on. `mailLink` builds a mailable and returns
    the link it carries.
  - Registration and forgot-password need `solveCaptcha()`; the member
    login has no captcha.
- **Character sets.** Every text column, every table default and the
  database default are utf8mb4, so every letter is kept, as
  `SchemaTest` checks.
- **MySQL rounding.** The course-progress function rounds a double, and
  MySQL rounds half to even: 12.5 % shows as 12.
- **PHPUnit 13 and Laravel 13.**
  - A data provider is a `public static` method named by a
    `#[DataProvider]` attribute: PHPUnit 12 dropped docblock
    annotations.
  - PHPUnit 13's `TestCase` has a final `attempt()` method, so no
    helper may use that name (`KnowledgeExamTest` has `attemptRow`).
  - Assertions use the PHPUnit 9+ names: `assertMatchesRegularExpression`,
    `assertFileDoesNotExist`, `assertDirectoryDoesNotExist`.
  - PHP 8 words a missing key as "Undefined array key", where PHP 7 said
    "Undefined offset".
  - Laravel encrypts a cookie's value behind a prefix bound to the
    cookie's name; `SessionTest::sessionId` checks and strips it with
    `CookieValuePrefix::validate`.
- **The locale outlives a request.** Within one test the app keeps the
  locale the previous request set, while production starts every
  request at the configured `en`. A test that checks a translated text
  after a request in another language sets the locale back first, as
  `SelfEvaluationTest::testNewBadgeMessageIsInTheMembersLanguage` does.
- **A real server error.** `ProductionModeTest` renames
  `bei_users_badges` for one request and puts it back, so the 500 it
  inspects comes from a real exception. It used to borrow the error of a
  pinned defect, `country-out-of-range`, and lost it when that was fixed.
- **A read-only upload folder.** Production mounts the upload folders
  read-only. `AdminLearningTest::testDeletesWorkOnAReadOnlyUploadFolder`
  makes `public/upload/lm` read-only with `chmod` and makes it writable
  again in a `finally`.
- **The random seed.** `PasswordResetTest` seeds the Mersenne Twister
  twice to show the tokens ignore it, then reseeds it at random for the
  tests that follow.
- **Certificates.** dompdf drops a page's frames once the page is drawn,
  so `CertificateTest` reads the name's font, size, lines, width and
  block height in an `end_frame` callback.
- **Apache tests commit.** Apache runs the app in its own processes,
  which see only committed rows. `ApacheTestCase` therefore gives its
  tests no transaction. `World::removeAll` deletes their rows and the
  rows the app wrote for the World's members.
- **Session cookies.** Apache's cookies are kept by hand, not in
  Guzzle's jar. The jar would hold back a Secure cookie over plain
  HTTP, and since Laravel 13 the session cookie is Secure.
