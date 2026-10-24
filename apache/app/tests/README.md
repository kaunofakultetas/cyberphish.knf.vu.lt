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
  the five `#[DataProvider]` attributes PHPUnit 13 requires, it still
  uses only PHP 7.2 syntax. From the repository root, this gate must
  print `-- 0 violation(s)`:

  ```sh
  python3 -B .claude/skills/house-comments/scripts/house_check_php84.py --new apache/app/tests
  ```

## Pinned defects

A second review of the whole app, on 24 October 2026, found 31 defects,
each pinned by a test that reproduced it. The 25 whose fixes no member or
admin would notice in normal use were fixed that day, and the six whose
fixes people see followed the same evening (see Fixed defects).

A third review followed that night and found 18 more, each pinned by a
test that reproduced it. All 18 were fixed the same night (see Fixed
defects).

A fourth review, the same night, ran twelve hunters over the whole
codebase: eight on areas of the app and four on cross-cutting concerns
(output escaping, authorization, data and SQL, the platform). Each hunter
proved its findings with a test. A skeptic per hunter re-ran every test
and tried to refute it, which turned down one finding. Seven others were
the same defect found twice. That left 42 defects: 40 pinned by 41 tests,
and two in the infrastructure. All 42 were fixed on 25 October (see Fixed
defects), so no defect is pinned now. The next one found is pinned in its
folder's `*DefectsTest.php`, as the rules above describe.

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

The last one that day was speed. The admin dashboard, where every
manager lands after signing in, took about half a minute for the main
admin and over a minute for the largest country. It asked for every
figure once per member and language, and the member tables had no key
besides the primary one, so each of those queries read a whole table.
The dashboard now gathers the figures for the whole country in a few
queries. It runs the progress function only for a member and language
with a row the function counts; every other pair is 0 by the function's
own sums. `mysql/init.sql` now keys the columns the app finds rows by.

| Id | Home test |
|---|---|
| `dashboard-query-per-member` | `AdminDashboardTest::testDashboardQueriesDoNotGrowWithTheMembers`, with `testEveryRowShowsTheMembersOwnFigures` holding every row to the figures the page used to work out one member at a time |
| `lookups-unkeyed` | `SchemaTest::testLookupsAreKeyed` |

Two fixes to how the panel looks followed. The KNF logo keeps its
proportions: the template's own logo was a 102×27 strip, and its
high-DPI rule squeezed the KNF logo into that strip. The logo is CSS
only, so it has no test. Switching a tab, or coming back to one after a
save, no longer scrolls the page. Each tab pane used to carry its tab's
hash as its id, and the browser scrolls to the element a hash names.

| Id | Home test |
|---|---|
| `admin-tab-scroll` | `AdminTabsTest::testTabHashNamesNoElement` |

The dashboard's tabs came next. Every language tab listed every member
of the country, so most tabs showed the whole country at 0%. A tab now
appears only for a language that some members have begun, and it lists
only those members, with their count in the label. The busiest language
comes first. A last tab lists the members who have begun nothing.

| Id | Home test |
|---|---|
| `dashboard-tabs-all-members` | `AdminDashboardTest::testTabsListTheMembersWhoBeganTheirLanguage`, with `testEveryRowShowsTheMembersOwnFigures` checking that the rows are exactly the member and language pairs begun |

The dashboard also gained a quick search box. `User_Search` in `admn.js`
keeps only the rows whose user name or email holds the typed text,
ignoring case and accents, and marks the tabs with matches. The suite
can't run the script itself, so
`AdminDashboardTest::testEveryMemberRowCarriesItsSearchText` checks
what the script reads: the box, every row's `data-search` text, and
each tab's row for a search that finds nobody.

The users page followed. It used to cut the members into pages of 25,
which a search in the browser could not reach past. It now lists them
all on one page, newest first, with the same search and a Country
column. A delete or a status change returns to where the page stood:
`Post_To` notes the scroll position and the search text, and
`Return_To_Place` puts them back.

| Id | Home test |
|---|---|
| `users-paged` | `AdminUsersTest::testEveryMemberIsOnTheOnePage`, `testEveryRowCarriesItsSearchText` |
| `hash-link-jump` | `TemplatesTest::testHashLinksDoNotScrollToTheTop`. Twenty-eight links to `#` (14 dialog links, 14 delete icons) still followed the link after their handler ran, which scrolled the page to the top |
| `login-email-lost` | `LoginTest::testRefusedLoginKeepsTheEmail`, `AdminLoginTest::testRefusedLoginKeepsTheEmail`. A wrong password brought both forms back empty. Now the email comes back, the password is never kept, and both forms let the browser fill a saved login |
| `no-tab-icon` | `TemplatesTest::testEveryLayoutLinksTheTabIcon`, `testTabIconIsARealIcon`. No page had a tab icon. The panel linked a file that didn't exist, and the root `favicon.ico` was empty. It now holds the round mark of the CyberPhish logo at 16, 32 and 48 pixels |
| `dashboard-progress-calls` | `AdminDashboardTest::testDashboardRunsNoProgressFunction`, with `testProgressMatchesTheFunctionAtHalfwayPoints` holding the new statement to the stored function. With every country on it, the main admin's dashboard took about 1.5 seconds, a second of that in 804 calls of the progress function. One statement now works out every pair, and plain rows replace a model per member. Main admin 1,553 → 249 ms, users page 354 → 87 ms, a country manager 800 → 226 ms, measured on a copy of production |
| `panel-unused-scripts` | `TemplatesTest::testPanelLoadsOnlyTheScriptsItUses`. Every panel page loaded and ran jQuery UI, sparkline and Summernote, about 470 KB of script. Only the three editor forms use Summernote, and they now load it themselves |

The same day, 25 pins of the second review were fixed: every one whose
fix no member or admin would notice in normal use. Each test was seen
failing on the code before its fix, then moved to its home file. Siblings
found on the way were fixed and tested with them: the knowledge test's
answer form took a list as badly as the self-evaluation's
(`knowledge-array-input`), the full name and the new password as badly as
the current password, and the cases the home tests list beside each id.

What changed:

- Badges and the lessons gate follow the run or attempt, not the URL or
  the session. That holds for the simulations, the knowledge test and the
  self-evaluation, and for the knowledge test's gate, which reads the
  attempt's language.
- The rankings read the page's language.
- Deleting a lesson, or a category, that a member had left now awards
  badge_all_presentations.
- The course progress counts each lesson once, and only lessons filed
  under a category. The stored function `bei_user_course_progress` in
  `mysql/init.sql` changed with `LMUsers::country_progress`, so a
  production database needs the function replaced. Production has no
  duplicate completions and no lesson without a category, so no figure
  moves.
- A mail outage at sign-up or on the reset button says so. The message is
  `main.mail_failed`, new in all five languages. It used to be a 500.
- PHP takes uploads of 10 MB.

| Id | Home test |
|---|---|
| `simulation-without-steps` | `SimulationsTest::testSimulationWithoutStepsEnds` |
| `simulation-badge-language` | `SimulationsTest::testEndPageAwardsTheRunsLanguage` |
| `simulation-unknown-language` | `SimulationsTest::testUnknownLanguageEndPageIsNoServerError` |
| `knowledge-gate-language` | `KnowledgeExamTest::testLessonsGateFollowsTheTestsLanguage` |
| `knowledge-badge-language` | `KnowledgeExamTest::testClosingAwardsTheAttemptsLanguage` |
| `knowledge-unknown-language` | `KnowledgeExamTest::testUnknownLanguageClosingIsNoServerError` |
| `knowledge-array-input` | `KnowledgeExamTest::testAnswerPostedAsAListIsNoServerError` |
| `lesson-delete-badge` | `LearningMaterialTest::testDeletedLessonDoesNotLockTheTestAway`, `testDeletedCategoryDoesNotLockTheTestAway` |
| `lesson-double-completion` | `CourseProgressTest::testLessonStoredTwiceCountsOnce`, and the member with a lesson stored twice in `AdminDashboardTest::testProgressMatchesTheFunctionAtHalfwayPoints` |
| `category-delete-hours` | `CourseProgressTest::testLessonsMembersCannotReachDoNotCount`, and the member with an uncategorised lesson in `AdminDashboardTest::testProgressMatchesTheFunctionAtHalfwayPoints` |
| `selfeval-badge-language` | `SelfEvaluationTest::testBadgeTakesTheAttemptsLanguage` |
| `learning-array-input` | `SelfEvaluationTest::testAnswerPostedAsAListIsNoServerError`, `LearningMaterialTest::testLessonPostedAsAListIsNoServerError` |
| `account-array-input` | `AccountSettingsTest::testFieldsPostedAsListsAreRefused`, which covers the full name and the new password too |
| `ranks-url-language` | `PublicPagesTest::testRankingFollowsThePagesLanguage` |
| `dashboard-null-country` | `AdminDashboardTest::testCountryManagerWithoutACountrySeesNoOtherCountry`. Only the main flag opens every country now; a null country no longer stands for every country |
| `admin-uid-script` | `AdminLearningTest::testUrlSegmentNeverReachesThePagesScript`, `AdminContentTest::testNewsIdNeverReachesThePagesScript`, the fourth page |
| `language-delete-lessons` | `AdminContentTest::testLanguageWithLessonsIsNotDeleted` |
| `simulation-import-blanks` | `AdminSimulationsTest::testSheetWithBlankCellsIsRefusedWithAMessage`, which also refuses a step without an answer type (a 500 too) and one more than a level below the step before it (saved where no run reached it) |
| `lesson-file-blank-name` | `AdminLearningTest::testFileNamedWithSpacesOnlyIsRefused`, renaming included |
| `lesson-hours-comma` | `AdminLearningTest::testHoursWithADecimalCommaAreNotCut`, editing included |
| `question-import-no-file` | `AdminLearningTest::testQuestionImportWithoutAFileIsRefused` |
| `verify-switched-off` | `VerificationTest::testLinkLeavesASwitchedOffAccountOff`, with `testLinkOfAnAccountSwitchedOnAlreadyGreets` keeping the case an admin switched on |
| `intended-cross-guard` | `AdminLoginTest::testAdminLoginIgnoresAMemberPagesParking`, `LoginTest::testSignInIgnoresAPanelPagesParking` |
| `auth-mail-outage` | `PasswordResetTest::testMailOutageIsNoServerErrorAndTellsNothing`, `RegistrationTest::testMailOutageCreatesNoAccount` |
| `throttle-shared` | `AdminLoginTest::testFailedMemberLoginsDoNotLockThePanel`, `LoginTest::testFailedPanelLoginsDoNotLockTheLogin` |
| `upload-limit` | `PhpRuntimeTest::testUploadsTakeTheCoursesLessonFiles` |

The six visible ones followed the same evening, each a change people
see. Each test was again seen failing on the code before its fix.

- badge_all_simulations counts only the simulations that a category of
  the language offers. Finishing every simulation members can reach now
  earns it. Production's Latvian had six simulations, ids 159–164, linked
  to no Latvian category: four to English categories, two to none. They
  were imported on 14 December 2022 with the 18 around them, which were
  re-filed by hand later, and these six were missed. That evening they
  were filed under Latvian categories. Four took the categories their
  import sheets had marked. The sheets of 162 and 164 marked none, so
  those two took the categories of their English originals. The links as
  they were are saved in `_ORG/scenario_categories_before_latvian_fix.sql`.
- Self-evaluation points count to the hundredth, and the total is rounded
  once. Three right options of 33.33 make 100, not 99. Production's
  finished attempts were rescored the same way that evening: 1,319 rose by
  1–4 points and 6 fell by 1. The 17 whose questions changed after they
  closed kept their scores. The table as it was is saved in
  `_ORG/selfeval_scores_before_rescore.sql`.
- badge_finished_course can fall due on any member page. It is announced
  on the first page that shows messages, and only once.
- The manager edit dialog opens on the stored country.
- A password reset switches on an account still waiting for its
  verification. An account an admin switched off stays off.
- A refused sign-up comes back with its email, country and terms box.

| Id | Home test |
|---|---|
| `simulation-badge-unreachable` | `SimulationsTest::testBadgeCountsTheSimulationsMembersAreOffered` |
| `selfeval-partial-points` | `SelfEvaluationTest::testPartlyWorthyOptionsAddUpExactly` |
| `finished-course-message` | `LearningMaterialTest::testFinishedCourseBadgeIsAnnouncedOnTheNextPageWithMessages`, with `testFinishedCourseBadgeIsAnnouncedOnceWithTheLastLesson` holding the lesson page that earns two badges to one message |
| `manager-edit-country` | `AdminManagersTest::testManagerEditDialogShowsTheStoredCountry` |
| `reset-unverified` | `PasswordResetTest::testResetGivesAnUnverifiedMemberAWorkingLogin`, with `testResetLeavesASwitchedOffAccountOff` |
| `register-input-lost` | `RegistrationTest::testRefusedSignUpKeepsTheForm` |

The third review's 17 followed the same night. Four were regressions of
the day's own fixes:
- `simulation-steps-order` came from the keys of `lookups-unkeyed`, and
  production showed it.
- `new-manager-dialog-preselects-own-country` came from the fix of
  `manager-edit-country`.
- `badge-note-outlives-the-member` came from the fix of
  `finished-course-message`.
- `simulation-share-counts-unoffered` was the half of
  `simulation-badge-unreachable` that the badge got and the progress did
  not.

The other 13 are the low ones whose fixes no member or admin would
notice in normal use. Each test was seen failing on the code before its
fix, then moved to its home file. Guard tests beside them hold the
behaviour next to each fix in place. An independent review of the whole
change, hunting only for regressions, found one more, from the new start
gate: `selfeval-retake-button`, fixed with them.

What changed:

- Run reports, the simulation edit page and the printout list the steps
  in sheet order again.
- The simulations quarter of the progress, and the dashboard's
  simulations figure, count only the simulations a category of the
  language offers, on both sides of the division. The stored function
  `bei_user_course_progress` in `mysql/init.sql` changed with
  `LMUsers::country_progress`, so a production database needs the
  function replaced.
- A run keeps its mode in a new column, `bei_users_scenarios.simulation_type`.
  A production database needs the column before the new code runs. A run
  with no mode stored falls back on its session, as every run did before.
- A new self-evaluation waits until the category's lessons are done, as
  the lesson page's button always did. An open attempt still resumes. The
  results page shows its do-it-again button only where the start opens
  an attempt.
- A closed self-evaluation stays closed when attempts grow longer.
- A question file with a question line lacking its `\:` is refused whole,
  with a message naming the line.
- Unticking every category of a simulation takes it off them all.
- Forms for a lesson or a language that is gone are refused, and nothing
  is stored or left on disk.
- The announcement of badge_finished_course belongs to the member who
  earned it. Signing out drops it.
- The server, too, requires the sign-up's terms box.

| Id | Home test |
|---|---|
| `simulation-steps-order` | `AdminSimulationsTest::testStepsAreListedInSheetOrder`, which covers the printout too |
| `new-manager-dialog-preselects-own-country` | `AdminManagersTest::testNewManagerDialogOpensOnNoCountry` |
| `badge-note-outlives-the-member` | `LearningMaterialTest::testBadgeAnnouncementStaysWithItsMember`, which also hands the session to another member without a sign-out |
| `simulation-share-counts-unoffered` | `CourseProgressTest::testSimulationsMembersCannotReachDoNotCount`, `AdminDashboardTest::testSimulationsFigureCountsTheSimulationsOnOffer` |
| `selfeval-closed-attempt-reopens` | `SelfEvaluationTest::testClosedAttemptStaysClosed` |
| `selfeval-starts-without-lessons` | `SelfEvaluationTest::testNewAttemptWaitsForTheCategorysLessons`, with `testOpenAttemptResumesAfterALessonIsAdded` |
| `selfeval-nested-answer-500` | `SelfEvaluationTest::testAnswerOfNestedListsIsRefused` |
| `selfeval-retake-button` | `SelfEvaluationTest::testResultsOfferAnotherAttemptOnlyWhereOneWouldStart`. The button always showed, and after the gate it led a member whose category had gained a lesson to the learning material with no word why |
| `simulation-mode-session-only` | `SimulationsTest::testModeOutlivesTheSession`, with `testRunWithoutAKeptModeFallsBackOnTheSession` for runs started before the column |
| `question-import-partial` | `AdminLearningTest::testQuestionImportWithABadLineStoresNothing` |
| `selfeval-import-carryover` | `AdminLearningTest::testSelfEvaluationBlockWithoutAQuestionStoresNoOptions` |
| `selfeval-points-comma` | `AdminLearningTest::testOptionPointsWithADecimalCommaAreNotCut` |
| `simulation-categories-clear` | `AdminSimulationsTest::testCategoriesDialogStoresTheTickedCategoriesOfItsLanguage`, which also refuses another language's category |
| `unchecked-hidden-ids` | `AdminLearningTest::testFormsForAGoneLessonOrLanguageStoreNothing`, `AdminSimulationsTest::testFormsForAGoneLanguageStoreNothing` |
| `admin-change-pass-array-500` | `AdminLoginTest::testPasswordDialogTakesNoLists` |
| `login-streak-counts-utc-days` | `LoginTest::testStreakCountsTheMembersDays`. With `testTenDaysInARowEarnTheBadge`, it catches a return to UTC days at any hour |
| `register-terms-not-enforced` | the "terms not ticked" case of `RegistrationTest::testIncompleteFormIsRefused` |
| `news-empty-picture-broken-image` | `PublicPagesTest::testNewsPictureShowsOnlyWhenThereIsOne` |

The last pin, `login-password-list`, followed. A password posted as a
list was a 500 for an active account and the usual refusal for an
unknown address, so the answer told accounts apart. Every password field
now refuses a list the way it refuses a password too short, before any
account is looked at: the member and panel logins, the sign-up and the
manager dialog. The sign-up used to mail its link before failing. The
manager dialog's country takes a whole number or nothing. A probe posted
every field of the sign-in, sign-up, reset and manager forms as a list;
only these answered with a 500. The independent review of the fix found
a sibling: a new password with a NUL byte, which bcrypt refuses, was a
500 wherever a password is set. That covers the sign-up (after it had
mailed its link), the manager dialog and both password-change forms. All
of them refuse it now, and so do both logins. bcrypt reads a password
only up to a NUL byte, so the real password followed by one and more
signed in. With a stored hash of another cost than the configured 10, as
`init.sql`'s `password_hash` command makes, the rehash on sign-in failed
on it with a 500.

| Id | Home test |
|---|---|
| `login-password-list` | `LoginTest::testPasswordPostedAsAListIsRefused`, `AdminLoginTest::testPasswordPostedAsAListIsRefused`, the "password as a list" and "repetition as a list" cases of `RegistrationTest::testIncompleteFormIsRefused`, and `AdminManagersTest::testFieldsPostedAsListsAreRefused` for the manager dialog. The NUL byte: the "password with a NUL" case of `RegistrationTest::testIncompleteFormIsRefused`, the "new with a NUL byte" case of `AccountSettingsTest::testPasswordChangeIsRefused`, `AdminLoginTest::testPasswordDialogRefusesANulByte`, `AdminManagersTest::testPasswordWithANulByteIsRefused`, and `LoginTest::testPasswordWithANulByteIsRefused` and `AdminLoginTest::testPasswordWithANulByteIsRefused` for the logins |

The five open questions from the reviews followed, at the user's request.
Each test was again seen failing on the code before its fix.

- Every manager can change their own password. The menu always offered
  it, but the route was kept to main admins, so a country manager's
  dialog opened empty.
- badge_login_10 belongs to the member, not to a language. It was filed
  under the language of the session at sign-in, which a fresh session
  gives as English, and the badges page showed it only in that language.
  Now one is filed, a later streak earns no second one, and the badges
  page shows it in every language. Production's seven holders needed no
  change.
- The sign-up form no longer tells which addresses have accounts. Every
  sign-up with matching passwords gets the same answer, and the address's
  owner hears of it by mail.
  - An account still waiting for its verification gets a reset link,
    whose page mails a fresh password and switches the account on. Its
    old verification link is cancelled. Production had 171 such accounts,
    whose owners used to be told only that the address was taken.
  - A first version resent that old link instead. The independent review
    caught it before it shipped: the link switches the account on with the
    password of whoever signed the address up first, who may not be the
    address's owner.
  - An active account gets a new note, `SendAccountExists`, which points
    to the login form. Its strings, `main.account_exists_subject` and
    `main.account_exists_mail`, are new in all five languages.
  - An account an admin switched off got nothing at first (see the two
    follow-ups below).
  - The password is hashed for every address, so an address in use
    answers no faster.
- Two sign-ups of one new address that arrive together no longer end in a
  500. The row is written before its mail. The sign-up that loses the race
  to the email's unique key answers as every sign-up does and mails
  nothing; the winner's mail carries the link. A mail that fails takes its
  new row with it.
- A new manager's message says to switch the manager on in the list. It
  used to ask for permissions the panel has no page for.

| Id | Home test |
|---|---|
| `country-manager-password` | `AdminLoginTest::testCountryManagerChangesItsOwnPassword` |
| `login-badge-session-language` | `LoginTest::testLoginBadgeIsTheMembersWhateverTheLanguage`, with `testTenDaysInARowEarnTheBadge` still filing a first badge under the session's language |
| `register-reveals-registered-emails` | `RegistrationTest::testAddressInUseIsAnsweredAsANewOne`, `testAddressInUseIsFoundWhateverItsCase`, `testPendingAccountSignedUpAgainOpensOnlyToItsMailbox` for the review's case from start to finish, `testAddressInUseCostsTheHashOfANewOne`, with `testRefusedSignUpKeepsTheForm` keeping the real refusals as they were |
| `register-signup-race` | `RegistrationTest::testSignUpThatLosesARaceAnswersAsAnAddressInUse`, which writes the rival row the moment the sign-up's own is about to be, and `testMailOutageCreatesNoAccount` for the row a failed mail takes with it |
| `manager-created-permissions` | `AdminManagersTest::testNewManagerStartsSwitchedOff` |

Two follow-ups to the sign-up came next, also at the user's request:

- An owner whose account was still waiting, and who signed up again, got
  a plain "Password reset link" mail, while the page spoke of a
  verification link. That case now gets its own mail,
  `SendActivationLink`. It says the address was signed up more than once
  and the account never activated, and that its link mails a password to
  sign in with, which can be changed once signed in. The link and the
  reset page behind it are unchanged. The message on the page now says
  only to check the email for the next step, which is true in every case.
  `main.user_registered` was reworded in all five languages.
- An account an admin switched off sent no mail, so its sign-up answered
  sooner. While mail could not go out, it also answered `user_registered`
  where every other address answered `mail_failed`. It now gets a note,
  `SendAccountDeactivated`, which says the account is deactivated and
  whom to ask. Every address in use now sends exactly one mail, as a new
  one does.
- The new strings `main.account_pending_subject`,
  `main.account_pending_mail`, `main.account_off_subject` and
  `main.account_off_mail` are in all five languages.

| Id | Home test |
|---|---|
| `signup-pending-friction` | `RegistrationTest::testMailsToAnAddressInUseSayWhatToDo`, which renders both mails in all five languages, and `testAddressInUseIsAnsweredAsANewOne`, `testPendingAccountSignedUpAgainOpensOnlyToItsMailbox` |
| `signup-switched-off-timing` | `RegistrationTest::testAddressInUseIsAnsweredAsANewOne`, where every address in use gets one mail, and `testMailOutageAnswersAlikeForEveryAddress` |

The fourth review's 42 defects were fixed on 25 October, at the user's
request. Every pin had reproduced its defect on the code before the fix.
What changed, beyond each defect's own repair:

- The reset link goes to the account's address as stored, whatever
  spelling of it was typed. Both logins key their lockout on the email as
  its column compares it, folded by the collation and hashed, so a locked
  address stays locked under every spelling of it, whether an account has
  it or not.
- The panel login opens for a member signed in on the course. A manager
  signed in is still sent on to the dashboard.
- MySQL takes packets of up to 64 MB: the mysql service's `command` in
  docker-compose sets it, and `container/my.cnf` does here. PHP's 12 MB
  post limit now comes first.
- The rich-text editors load the stored HTML as stored.
- A news article's picture file goes with the article, and with the
  picture a new upload replaces.
- Saving an article or an information page deleted meanwhile is refused
  with a message, and nothing is stored.
- Deleting a language deletes its members' badges, knowledge tests,
  self-evaluations and simulation runs, with their answers, in one
  transaction. Only badge_login_10 stays, being the member's in every
  language. Deleting a self-evaluation category deletes its attempts and
  their answers, which the ranking counted and the histories no longer
  listed.
- A question file in UTF-16 with a byte order mark is converted, a UTF-8
  byte order mark is dropped, and any other file not in UTF-8 is refused
  with a message asking for UTF-8. This holds for both imports.
- A file named .xlsx that is no spreadsheet is refused with a message.
- When the panel takes a member's last unfinished simulation off the
  offer, the member gets badge_all_simulations, as deleting lessons
  awards badge_all_presentations. Deleting a category, the categories
  dialog and deleting a simulation can each do it. A change that leaves
  no simulation on offer awards nothing.
- The panel's self-evaluation history leaves a deleted question out of
  both its counts, as the member's results page leaves it out.
- The self-evaluation share of the course progress counts only the
  categories a member can finish: those with a lesson and a question
  that has options. The stored function and `LMUsers::country_progress`
  agree. A read-only check of production found no member whose figure
  moves. A category without lessons opens no attempt, and its results
  page offers none; such a category once counted as done at once.
- The knowledge test page offers a start only when the language has a
  question with a correct option.
- A right answer to a question deleted during the attempt keeps its
  points, and the results skip the question.
- The badge pictures ship with the app, in `public/assets/img/badges`.
  The pages used to link `upload/badges`, which only production's data
  folder had.
- A simulation choice must be a whole number the step offers.
- Equal totals share a place in the rankings, 1, 1, 3, and are listed by
  user name.
- The cookie notice is translated. Its strings, `main.cookie_message`,
  `main.cookie_link` and `main.cookie_dismiss`, are new in all five
  languages.
- Every page declares the ISO code of its language: el for gr, et for
  ee.
- A route's `{lang}` is two letters, in either case. Any other segment is
  not found, where `api` once took the site's pages under /api/, and a
  line break reached a redirect's `Location`. An unknown two-letter code
  still falls back to the session's language.
- No path gets CORS headers.

| Id | Home test |
|---|---|
| `reset-link-typed-address` | `PasswordResetTest::testResetLinkGoesOnlyToTheAccountsOwnAddress` |
| `rich-text-body-over-packet` | `AdminContentTest::testBodyWithAPastedPhotoIsNoServerError` |
| `rich-text-edit-unescapes` | `AdminContentTest::testRichTextEditorsLoadTheStoredHtml` |
| `knowledge-question-without-correct` | `KnowledgeExamTest::testQuestionWithoutACorrectOptionIsNeverDrawn`, with `testLanguageWithNothingToDrawOffersNoStart` |
| `login-throttle-collation-bypass` | `LoginTest::testLockedAccountCannotBeReachedByAnEquivalentSpelling`, `AdminLoginTest::testLockoutHoldsUnderEverySpellingOfTheAddress`, with `LoginTest::testAddressNoAccountHasLocksAsAnAccountsDoes` |
| `register-password-loose-compare` | `RegistrationTest::testSignUpRejectsPasswordsThatOnlyLookEqual` |
| `reset-captcha-language` | `PasswordResetTest::testResetRequestCaptchaSpeaksTheSitesLanguage` |
| `news-delete-keeps-picture` | `AdminContentTest::testDeletedArticleTakesItsPictureAlong`, `testReplacingAPictureRemovesTheOldFile` |
| `update-of-a-deleted-item` | `AdminContentTest::testEditOfADeletedItemIsRefused` |
| `language-delete-orphans` | `AdminContentTest::testDeletedLanguageLeavesNothingOfItsMembers`, with `testDeletedLanguageLeavesTheLoginBadge` |
| `language-locale-taken` | `AdminContentTest::testLocaleOfAnotherLanguageIsRefused` |
| `selfeval-history-deleted-question` | `AdminUsersTest::testSelfEvaluationHistoryLeavesADeletedQuestionOut`, with `testDeletedQuestionTurnsNoPartAnswerRight` |
| `selfeval-history-duplicate-answer` | `AdminUsersTest::testSelfEvaluationHistoryCountsAnAnswerStoredTwiceOnce` |
| `question-import-encoding` | `AdminLearningTest::testFileInAnotherEncodingIsReadOrRefused` |
| `question-import-bom` | `AdminLearningTest::testFileWithAByteOrderMarkKeepsItsFirstQuestion` |
| `simulation-import-level-text` | `AdminSimulationsTest::testLevelWrittenAsTextIsImported` |
| `simulation-import-unreadable` | `AdminSimulationsTest::testFileThatIsNoSpreadsheetIsRefusedWithAMessage` |
| `simulation-categories-gone` | `AdminSimulationsTest::testCategoriesDialogOfADeletedSimulationStoresNothing` |
| `simulation-delete-badge` | `AdminSimulationsTest::testTakingTheLastSimulationLeftOffTheOfferAwardsTheBadge`, with `testOfferLeftEmptyAwardsNothing` |
| `admin-knowledge-empty-label` | `AdminLearningTest::testEmptyKnowledgeQuestionListSpeaksOfKnowledgeQuestions` |
| `panel-login-bounces-members` | `AdminLoginTest::testPanelLoginOpensForASignedInMember` |
| `admin-form-list-500` | `AdminContentTest::testPanelFormsRefuseFieldsOfTheWrongShape` |
| `admin-report-unknown-ids` | `AdminAccessTest::testReportsOfUnknownIdsAreNotFound` |
| `category-delete-ranking` | `AdminLearningTest::testRankingAgreesWithTheHistoryAfterACategoryIsDeleted` |
| `admin-login-email-list-500` | `AdminLoginTest::testPanelLoginFormSurvivesAnEmailPostedAsAList` |
| `selfeval-share-counts-unreachable` | `CourseProgressTest::testSelfEvaluationShareCountsOnlyWhatMembersCanFinish`, with `SelfEvaluationTest::testCategoryWithoutLessonsOffersNoSelfEvaluation` |
| `category-done-without-category` | `LearningMaterialTest::testCategoryThatIsGoneIsNeverFinished` |
| `selfeval-deleted-question-scores-nothing` | `SelfEvaluationTest::testAnswerToADeletedQuestionKeepsItsScore` |
| `selfeval-results-blank-question` | `SelfEvaluationTest::testResultsListNoQuestionThatIsGone` |
| `badge-images-not-shipped` | `LearningMaterialTest::testBadgePicturesShipWithTheApp` |
| `progress-shown-before-the-close` | `KnowledgeExamTest::testClosingPageCountsThePassInTheCourseProgress`, `SimulationsTest::testEndPageShowsTheProgressItsRunCompletes` |
| `knowledge-page-bold` | `KnowledgeExamTest::testKnowledgeQuestionPageClosesItsBoldHeader` |
| `simulation-choice-json-true` | `SimulationsTest::testChoicePostedAsTrueIsNotRecorded` |
| `simulations-list-last-ended` | `SimulationsTest::testSimulationsListShowsTheLatestEnd` |
| `cors-api-paths` | `PublicPagesTest::testApiPathsInviteNoOrigin` |
| `ranks-tied-positions` | `PublicPagesTest::testTiedMembersShareTheirPlace` |
| `login-redirect-language` | `PublicPagesTest::testLoginFormKeepsTheLanguageOfTheLinkFollowed` |
| `cookie-notice-english` | `PublicPagesTest::testCookieNoticeSpeaksThePagesLanguage` |
| `html-lang-english` | `PublicPagesTest::testPagesDeclareTheirLanguage` |
| `lang-segment-line-break` | `FrontControllerTest::testLineBreakInTheLanguageIsNoServerError`, which also opens an unknown code and an upper-case one |

The suite and an independent review of the whole diff found nine
regressions in these fixes before they were reported. Each is fixed, with
a test that failed without its fix:

- The `{lang}` pattern took only lower-case letters at first, so a link
  under /EN/ was not found (`KnowledgeExamTest::testLessonsGateFollowsTheTestsLanguage`,
  and `FrontControllerTest::testLineBreakInTheLanguageIsNoServerError`
  now opens one).
- A change that left no simulation on offer gave badge_all_simulations to
  every member with a finished run
  (`AdminSimulationsTest::testOfferLeftEmptyAwardsNothing`).
- Deleting a language took badge_login_10 along
  (`AdminContentTest::testDeletedLanguageLeavesTheLoginBadge`).
- The history read part of an answer to a deleted question as the whole
  answer (`AdminUsersTest::testDeletedQuestionTurnsNoPartAnswerRight`).
- A category without lessons still opened attempts the progress no
  longer counted (`SelfEvaluationTest::testCategoryWithoutLessonsOffersNoSelfEvaluation`).
- The knowledge test page offered a start that drew nothing when no
  question had a correct option
  (`KnowledgeExamTest::testLanguageWithNothingToDrawOffersNoStart`).
- The lockout keyed a known address on its account and an unknown one on
  the typed address, so ten failures and one more under another spelling
  told which addresses had accounts
  (`LoginTest::testAddressNoAccountHasLocksAsAnAccountsDoes`,
  `AdminLoginTest::testLockoutHoldsUnderEverySpellingOfTheAddress`).
- `--routines` stopped every dump at the function, the views lost with
  it, wherever the backup user could not read the function's body.
  `init.sql` now grants that on a fresh install
  (`SchemaTest::testAppUserCanReadTheProgressFunction`).
- The dashboard's progress query checked every finished self-evaluation's
  category again, three times as slow. It now reads the counted
  categories once.

A second review, of those nine fixes, found one test that had lost its
meaning: `AdminDashboardTest::testProgressMatchesTheFunctionAtHalfwayPoints`
built its self-evaluation categories without lessons, so it no longer
compared the self-evaluation share at all. Its categories now hold a
lesson of no hours, a fourth without lessons stays out, and the test
catches a share computed in the wrong order.

The two infrastructure defects are fixed in docker-compose:

- **Backups.** Both backup services set MYSQLDUMP_OPTIONS to
  `--single-transaction --no-tablespaces --default-character-set=utf8mb4
  --routines`. The dumps now keep 4-byte characters and include
  `bei_user_course_progress`. `--routines` needs the dump user, the app's,
  to read `mysql.proc`. `init.sql` grants that to the database's user on a
  fresh install; production, set up before, grants it once by hand.
  `--no-tablespaces` keeps mysqldump from warning that the user lacks the
  PROCESS privilege. A restore of such a dump into an empty database,
  tried on MySQL 5.7.42, brought back the emoji and a working function.
- **Uploads.** The mount of `_DATA/apache/upload` is gone from both
  compose files, and `runUpdateThisStack.sh` no longer creates the
  folder. The uploads live in `_DATA/apache/public`, mounted at
  `storage/app/public`, where the image's `public/upload` link points.

## Found but not pinned

These were found during review but not pinned. Some have no impact in
production; others can't be tested deterministically.

- **Signing up someone else's address.** Anyone can sign up an address
  that is not theirs, with a password of their own. The verification
  mail goes to the address. If its owner clicks that unsolicited link
  without signing up themselves, the account opens with the other
  person's password. Once the owner signs up too, that link is cancelled
  (`testPendingAccountSignedUpAgainOpensOnlyToItsMailbox`). Closing the
  case fully would mean choosing the password after the click, not at
  sign-up.

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
