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
| `Support/World.php` | Builders for every kind of row the app reads, with valid defaults |
| `Support/Flows.php` | Multi-step member flows: starting and answering self-evaluations, simulations and the knowledge test |
| `Support/Sheets.php` | The simulation spreadsheet the panel's import reads, with cells a test can replace |
| `Support/KnownDefects.php` | `pinDefect`, see Rules |
| `Support/ApacheClient.php` | Guzzle client for the real Apache, with its own cookie store |
| `container/` | `run.sh`, the image's entrypoint, plus `my.cnf` for MySQL and the composer files of the test tools |
| `Platform/`, `Feature/<Area>/`, `Apache/` | The suites |

Three files outside `tests/` belong to the suite:

- `apache/runTests.sh` runs everything.
- `apache/Dockerfile.tests` builds the test image.
- `apache/.dockerignore` keeps the tests out of the production image.

## Rules

- **Offline.** Nothing reaches Google or Mailjet:
  - Feature tests fake the mailer and mock the captcha.
  - Over HTTP, the unreachable Google is itself under test (`captcha-outage-500`).
- **Production-shaped.**
  - Apache runs with no `.env`, `APP_DEBUG` off, and the environment
    `docker-compose.yml` gives the production container, with test
    values in place of the secrets.
  - The app's `vendor/` stays `--no-dev`. PHPUnit and Mockery live
    apart, in `/opt/test-tools`.
- **Every test builds its own rows** through the World. A test relies on
  nothing but the seed rows of `init.sql`. Emails, user names and titles
  carry a serial number that is unique for the whole run.
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

| Id | File | What breaks |
|---|---|---|
| `seed-admin-password` | `Platform/PlatformDefectsTest` | `init.sql` seeds the main admin with the password "admin" |
| `remoteip-trust-range` | `Platform/PlatformDefectsTest` | Apache trusts all of 172.0.0.0/8, public addresses included, to name the visitor in the access log |
| `home-about-missing` | `Feature/Site/SiteDefectsTest` | The home page is a 500 in a language the saved about text has no entry for |
| `news-page-links` | `Feature/Site/SiteDefectsTest` | The page links under the news list point at `/news`, a 404 |
| `ranks-query-per-member` | `Feature/Site/SiteDefectsTest` | The ranking pages run one more query for every ranked member |
| `verify-blank-token` | `Feature/Auth/AuthDefectsTest` | `/verify/%20` switches every deactivated account back on |
| `login-throttle` | `Feature/Auth/AuthDefectsTest` | The member login takes any number of password guesses |
| `country-out-of-range` | `Feature/Auth/AuthDefectsTest` | A country id above 5 breaks every page of that member |
| `password-trim` | `Feature/Auth/AuthDefectsTest` | A password with a blank at either end never matches its repeat; the change forms save it trimmed, and the login then refuses it |
| `password-flash` | `Feature/Auth/AuthDefectsTest` | A failed sign-up keeps the repeated password in the session |
| `reset-link-logout` | `Feature/Auth/AuthDefectsTest` | Signing out replaces `remember_token`, which holds a pending reset link's token |
| `login-error-lost` | `Feature/Auth/AuthDefectsTest` | A failed login that a member page led to returns to the form without its error |
| `login-badge-message` | `Feature/Auth/AuthDefectsTest` | The login that earns the ten-days badge never announces it |
| `register-while-signed-in` | `Feature/Auth/AuthDefectsTest` | A signed-in member can still open and post the sign-up form |
| `captcha-message` | `Feature/Auth/AuthDefectsTest` | A failed reCAPTCHA shows the raw key `validation.captcha`, and an unticked one names the field `g-recaptcha-response` |
| `username-lithuanian` | `Feature/Account/AccountDefectsTest` | A user name with Lithuanian letters is a 500 |
| `username-length` | `Feature/Account/AccountDefectsTest` | A user name longer than its column is cut without a word |
| `password-policy` | `Feature/Account/AccountDefectsTest` | A changed password may be 6 characters; a new one needs 12 |
| `fullname-latin1` | `Feature/Account/AccountDefectsTest` | Lithuanian letters of the full name turn into question marks |
| `fullname-length` | `Feature/Account/AccountDefectsTest` | A full name over 20 characters is refused with a bare "Error!", though the form takes 100 |
| `ee-strings` | `Feature/Account/AccountDefectsTest` | The Estonian user-name button ends in a stray "2", and a rename is answered with password messages |
| `selfeval-foreign-options` | `Feature/Learning/LearningDefectsTest` | A self-evaluation scores any option ids posted, as often as posted |
| `course-progress-selfeval` | `Feature/Learning/LearningDefectsTest` | Progress counts finished self-evaluations against the number of questions |
| `lesson-badge-chain` | `Feature/Learning/LearningDefectsTest` | A completed lesson earns at most one badge, so badges that fall due together are lost |
| `selfeval-get-creates` | `Feature/Learning/LearningDefectsTest` | Every visit of the self-evaluation start page opens one more attempt |
| `selfeval-short-category` | `Feature/Learning/LearningDefectsTest` | In a category with fewer questions than the self-evaluation length, the attempt never closes |
| `selfeval-reclose` | `Feature/Learning/LearningDefectsTest` | Every visit to a finished self-evaluation closes it again, moving its end date |
| `selfeval-badge-count` | `Feature/Learning/LearningDefectsTest` | A member who left a self-evaluation unfinished never gets its badge |
| `selfeval-badge-locale` | `Feature/Learning/LearningDefectsTest` | The self-evaluation's new-badge message is always in English |
| `course-progress-null` | `Feature/Learning/LearningDefectsTest` | In a language without simulations or self-evaluation questions, the progress shows 0 whatever else is done |
| `badge-duplicate` | `Feature/Learning/LearningDefectsTest` | A badge stored twice reads as not held |
| `lang-no-questions` | `Feature/Learning/LearningDefectsTest` | `no_questions` is defined twice in four `main.php` files, so the self-evaluation page never shows its own text |
| `simulation-foreign-option` | `Feature/Simulations/SimulationDefectsTest` | A step records any option id posted, as often as posted |
| `simulation-results-mail-repeat` | `Feature/Simulations/SimulationDefectsTest` | Every visit to a finished run mails the result again |
| `simulation-other-language` | `Feature/Simulations/SimulationDefectsTest` | A simulation of another language is a 500 |
| `simulation-without-category` | `Feature/Simulations/SimulationDefectsTest` | A simulation filed under no category is a 500 |
| `simulation-badge-message` | `Feature/Simulations/SimulationDefectsTest` | Once every simulation is finished, each end page announces the held badge as new again |
| `knowledge-skipped-answers` | `Feature/Knowledge/KnowledgeDefectsTest` | A question answered with no option drops out of the result |
| `knowledge-repeated-option` | `Feature/Knowledge/KnowledgeDefectsTest` | A correct option posted many times counts many times |
| `knowledge-all-skipped` | `Feature/Knowledge/KnowledgeDefectsTest` | Skipping every question divides by zero, and the test never closes |
| `knowledge-unknown-attempt` | `Feature/Knowledge/KnowledgeDefectsTest` | An unknown attempt id is a 500 |
| `knowledge-attempt-owner` | `Feature/Knowledge/KnowledgeDefectsTest` | Any member can open another member's attempt |
| `knowledge-attempt-limit` | `Feature/Knowledge/KnowledgeDefectsTest` | The three-try limit exists only in the page |
| `knowledge-category-lookup` | `Feature/Knowledge/KnowledgeDefectsTest` | An answer's category is looked up by the attempt's id. Once attempt ids pass the question ids, answering fails |
| `knowledge-reclose` | `Feature/Knowledge/KnowledgeDefectsTest` | Every visit closes a finished attempt again, moving the certificate's date and working the result out again |
| `knowledge-limits-missing` | `Feature/Knowledge/KnowledgeDefectsTest` | A language missing from the knowledge-test limits makes every start a 500 |
| `knowledge-badge-later-pass` | `Feature/Knowledge/KnowledgeDefectsTest` | A pass on a later try never earns the final-test badge |
| `certificate-greek-name` | `Feature/Knowledge/KnowledgeDefectsTest` | The certificate's name font has no Greek letters, so a Greek member's name cannot print |
| `manager-country-scope` | `Feature/Admin/AdminDefectsTest` | A country manager can read any member's history and reports |
| `admin-get-deletes` | `Feature/Admin/AdminDefectsTest` | Deleting and switching off answer a plain GET, so a link can do it |
| `admin-login-throttle` | `Feature/Admin/AdminDefectsTest` | The admin login takes any number of password guesses |
| `selfeval-greek-import` | `Feature/Admin/AdminDefectsTest` | The import never finds a Greek question line |
| `seed-settings-rows` | `Feature/Admin/AdminDefectsTest` | A fresh install has no settings rows 1 and 3 to save into |
| `lesson-delete-missing-file` | `Feature/Admin/AdminDefectsTest` | A lesson whose file is gone from disk cannot be deleted |
| `empty-body-500` | `Feature/Admin/AdminDefectsTest` | News, pages and lessons saved with an empty body are a 500 |
| `admin-news-shadowed` | `Feature/Admin/AdminDefectsTest` | The public news routes answer the panel's news list, edit and delete links |
| `simulation-delete-links` | `Feature/Admin/AdminDefectsTest` | The delete icons of the simulation categories and attributes lead to a 404 |
| `admin-login-error-lost` | `Feature/Admin/AdminDefectsTest` | A failed admin login that a panel page led to returns to the form without its message |
| `admin-pass-messages` | `Feature/Admin/AdminDefectsTest` | The panel's password dialog answers with raw `main.msg_*` keys |
| `lesson-file-embed` | `Feature/Admin/AdminDefectsTest` | A lesson file saved with Embed unticked is stored as NULL and shows nowhere |
| `simulation-import-partial` | `Feature/Admin/AdminDefectsTest` | A failed spreadsheet import leaves a half-saved simulation behind |
| `info-create-intended` | `Feature/Admin/AdminDefectsTest` | A new information page sends the admin to a page parked in the session instead of the list |
| `import-markers` | `Feature/Admin/AdminDefectsTest` | Both question imports drop every "=" or "~" inside an option's text, not only its marker |
| `question-update-no-options` | `Feature/Admin/AdminDefectsTest` | A question with no options never saves its edited text, yet the page says "Updated!" |
| `lm-delete-anchor` | `Feature/Admin/AdminDefectsTest` | Deleting a lesson or a category reopens the list on the wrong language tab |
| `category-delete-orphans` | `Feature/Admin/AdminDefectsTest` | A deleted category's self-evaluation and knowledge-test questions stay behind |
| `lesson-file-missing` | `Feature/Admin/AdminDefectsTest` | A lesson-file post without a usable file stores nothing, yet says "Created!" |
| `lesson-file-delete-unknown` | `Feature/Admin/AdminDefectsTest` | Deleting a lesson file that is already gone is a 500 |
| `selfeval-form-route` | `Feature/Admin/AdminDefectsTest` | A self-evaluation route names a method its controller lacks: a 500 |
| `language-rules` | `Feature/Admin/AdminDefectsTest` | A language name another language already has is a 500, not a form error |
| `language-delete-in-use` | `Feature/Admin/AdminDefectsTest` | Deleting a language that still has content is a 500 instead of a message |
| `knowledge-report-unknown` | `Feature/Admin/AdminDefectsTest` | The panel's knowledge report for an unknown attempt is a 500 |
| `manager-country-update` | `Feature/Admin/AdminDefectsTest` | Editing a manager drops the country the dialog asks for |
| `manager-update-main` | `Feature/Admin/AdminDefectsTest` | The manager dialog's update rewrites a main admin's address and password |
| `simulation-update-descr` | `Feature/Admin/AdminDefectsTest` | Editing a simulation without steps drops the new description, though the page says "Updated!" |
| `simulations-pdf-categories` | `Feature/Admin/AdminDefectsTest` | Every scenario of the simulations printout shows its language's last scenario's categories |
| `import-cross-language` | `Feature/Admin/AdminDefectsTest` | The simulation import matches category and attribute names across languages |
| `expose-php` | `Apache/ApacheDefectsTest` | Every response names PHP's exact version |
| `server-banner` | `Apache/ApacheDefectsTest` | The Server header and Apache's own error pages name Apache's version |
| `client-ip-header` | `Apache/ApacheDefectsTest` | A `Client-IP` or `CF-Connecting-IP` header picks the address the app records |
| `trailing-slash-http` | `Apache/ApacheDefectsTest` | The trailing-slash redirect sends HTTPS visitors to `http://` |
| `captcha-outage-500` | `Apache/ApacheDefectsTest` | Registration is a 500 while Google is unreachable |
| `captcha-outage-reset` | `Apache/ApacheDefectsTest` | Forgot-password is a 500 while Google is unreachable |

Each test's banner explains its defect: the cause, the impact, and the
behaviour wanted instead.

The move to Laravel 13 released two pins, through the framework's own
changes rather than any fix in the app. They are now ordinary tests in
their home files:

- `news-svg-picture` → `AdminContentTest::testSvgNewsPictureIsRefused`.
  Laravel 13's `image` rule refuses SVG unless it is given `allow_svg`.
- `session-cookie-secure` → `SessionTest::testSessionCookieIsSecure`.
  With `SESSION_SECURE_COOKIE` unset, Laravel 13 marks the cookie Secure
  on HTTPS requests, and behind the endpoint every request is HTTPS.

## Found but not pinned

These were found during review but not pinned. Some have no impact in
production; others can't be tested deterministically.

- **CORS headers.** The `Cors` middleware adds
  `Access-Control-Allow-Origin: *` to every response. The endpoint
  answers any foreign `Origin` with 403 before Apache sees the request,
  and credentials are never allowed.
- **Uploaded `.htaccess`.** `AllowOverride All` reaches `public/upload`,
  so Apache would read an `.htaccess` dropped there. PHP stays off
  (`ProtectionTest` proves it). Only admins write to the folder, and
  production mounts it read-only.
- **Spreadsheet library.** The imports use phpoffice/phpspreadsheet
  1.19.0, which has known XXE vulnerabilities fixed in later releases.
  Only admins can upload a spreadsheet.
- **Weak tokens.** `SettingsController::random_string` builds the
  verification and reset tokens with `str_shuffle`, which is not a
  cryptographic generator.
- **Dead code.** `LMController::gen_test_id` and
  `SimulationsController::gen_test_id` retry through a method their
  models do not have. The retry runs only when a random id collides,
  so no test can reach it. The `ValidateVerify` middleware is never
  registered.
- **News picture removal.** `Admin\NewsController::delete_picture`
  unlinks the picture folder itself when an article has no picture. No
  test can reach it while `admin-news-shadowed` stands: the public news
  route answers that URL first.
- **Costly user names.** `RegisterController::randomGen` shuffles a
  million-element range for every new user name.

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
- **Knowledge attempts.** `progress_save` looks an answer's category up
  by the attempt's id (`knowledge-category-lookup`).
  - In production, every attempt id so far (up to 664) is also a
    question id (questions run to 760). Answers therefore save, but
    under the wrong category.
  - From attempt 761 on, the insert fails and every knowledge test
    breaks.
  - Tests that answer a knowledge question call `coverKnowledgeAttempts`
    first, so that a question with the attempt's id exists, as in
    production.
- **Shadowed routes.** The public `{lang}/news…` routes are registered
  first and answer four `admin-panel/news…` URLs. `AdminAccessTest`
  leaves them out of its route sweep (`SHADOWED`), and
  `AdminDefectsTest` pins them.
- **latin1 columns.** The full-name column cannot hold Lithuanian
  letters, so tests that read names back use plain ASCII. The pin
  `fullname-latin1` is the exception.
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
  `selfeval-badge-locale` does.
- **Apache tests commit.** Apache runs the app in its own processes,
  which see only committed rows. `ApacheTestCase` therefore gives its
  tests no transaction. `World::removeAll` deletes their rows and the
  rows the app wrote for the World's members.
- **Session cookies.** Apache's cookies are kept by hand, not in
  Guzzle's jar. The jar would hold back a Secure cookie over plain
  HTTP, and since Laravel 13 the session cookie is Secure.
