# Apache app regression tests

The PHPUnit suite of the CYBERPHISH app (Laravel 7.15 on PHP 7.2.24). It
runs in one throwaway container with no network, built from the
production image. Inside that container it starts MySQL 5.7.42 with the
schema from `mysql/init.sql` and Apache serving the app the way
production does, then runs PHPUnit 8.5.

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
  7.2 reference, with PHP 7.2 syntax only. From the repository root,
  this gate must print `-- 0 violation(s)`:

  ```sh
  python3 -B .claude/skills/house-comments/scripts/house_check_php72.py --new apache/app/tests
  ```

## Pinned defects

| Id | File | What breaks |
|---|---|---|
| `seed-admin-password` | `Platform/PlatformDefectsTest` | `init.sql` seeds the main admin with the password "admin" |
| `remoteip-trust-range` | `Platform/PlatformDefectsTest` | Apache trusts all of 172.0.0.0/8, public addresses included, to name the visitor in the access log |
| `verify-blank-token` | `Feature/Auth/AuthDefectsTest` | `/verify/%20` switches every deactivated account back on |
| `login-throttle` | `Feature/Auth/AuthDefectsTest` | The member login takes any number of password guesses |
| `country-out-of-range` | `Feature/Auth/AuthDefectsTest` | A country id above 5 breaks every page of that member |
| `username-lithuanian` | `Feature/Account/AccountDefectsTest` | A user name with Lithuanian letters is a 500 |
| `username-length` | `Feature/Account/AccountDefectsTest` | A user name longer than its column is cut without a word |
| `password-policy` | `Feature/Account/AccountDefectsTest` | A changed password may be 6 characters; a new one needs 12 |
| `fullname-latin1` | `Feature/Account/AccountDefectsTest` | Lithuanian letters of the full name turn into question marks |
| `selfeval-foreign-options` | `Feature/Learning/LearningDefectsTest` | A self-evaluation scores any option ids posted, as often as posted |
| `course-progress-selfeval` | `Feature/Learning/LearningDefectsTest` | Progress counts finished self-evaluations against the number of questions |
| `simulation-foreign-option` | `Feature/Simulations/SimulationDefectsTest` | A step records any option id posted, as often as posted |
| `simulation-results-mail-repeat` | `Feature/Simulations/SimulationDefectsTest` | Every visit to a finished run mails the result again |
| `simulation-other-language` | `Feature/Simulations/SimulationDefectsTest` | A simulation of another language is a 500 |
| `simulation-without-category` | `Feature/Simulations/SimulationDefectsTest` | A simulation filed under no category is a 500 |
| `knowledge-skipped-answers` | `Feature/Knowledge/KnowledgeDefectsTest` | A question answered with no option drops out of the result |
| `knowledge-repeated-option` | `Feature/Knowledge/KnowledgeDefectsTest` | A correct option posted many times counts many times |
| `knowledge-all-skipped` | `Feature/Knowledge/KnowledgeDefectsTest` | Skipping every question divides by zero, and the test never closes |
| `knowledge-unknown-attempt` | `Feature/Knowledge/KnowledgeDefectsTest` | An unknown attempt id is a 500 |
| `knowledge-attempt-owner` | `Feature/Knowledge/KnowledgeDefectsTest` | Any member can open another member's attempt |
| `knowledge-attempt-limit` | `Feature/Knowledge/KnowledgeDefectsTest` | The three-try limit exists only in the page |
| `knowledge-category-lookup` | `Feature/Knowledge/KnowledgeDefectsTest` | An answer's category is looked up by the attempt's id. Once attempt ids pass the question ids, answering fails |
| `manager-country-scope` | `Feature/Admin/AdminDefectsTest` | A country manager can read any member's history and reports |
| `admin-get-deletes` | `Feature/Admin/AdminDefectsTest` | Deleting and switching off answer a plain GET, so a link can do it |
| `admin-login-throttle` | `Feature/Admin/AdminDefectsTest` | The admin login takes any number of password guesses |
| `selfeval-greek-import` | `Feature/Admin/AdminDefectsTest` | The import never finds a Greek question line |
| `seed-settings-rows` | `Feature/Admin/AdminDefectsTest` | A fresh install has no settings rows 1 and 3 to save into |
| `lesson-delete-missing-file` | `Feature/Admin/AdminDefectsTest` | A lesson whose file is gone from disk cannot be deleted |
| `empty-body-500` | `Feature/Admin/AdminDefectsTest` | News, pages and lessons saved with an empty body are a 500 |
| `admin-news-shadowed` | `Feature/Admin/AdminDefectsTest` | The public news routes answer the panel's news list, edit and delete links |
| `expose-php` | `Apache/ApacheDefectsTest` | Every response names PHP's exact version |
| `server-banner` | `Apache/ApacheDefectsTest` | The Server header and Apache's own error pages name Apache's version |
| `session-cookie-secure` | `Apache/ApacheDefectsTest` | The session cookie has no Secure flag |
| `client-ip-header` | `Apache/ApacheDefectsTest` | A `Client-IP` or `CF-Connecting-IP` header picks the address the app records |
| `trailing-slash-http` | `Apache/ApacheDefectsTest` | The trailing-slash redirect sends HTTPS visitors to `http://` |
| `captcha-outage-500` | `Apache/ApacheDefectsTest` | Registration is a 500 while Google is unreachable |

Each test's banner explains its defect: the cause, the impact, and the
behaviour wanted instead.

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
- **Admin-only weaknesses.** Admins may upload SVG news pictures, and
  the spreadsheet imports use phpoffice/phpspreadsheet 1.19.0, which has
  known XXE vulnerabilities fixed in later releases.
- **Weak tokens.** `SettingsController::random_string` builds the
  verification and reset tokens with `str_shuffle`, which is not a
  cryptographic generator.
- **Dead code.** `LMController::gen_test_id` retries through a method
  `SelfEvalUserTest` does not have. The `ValidateVerify` middleware is
  never registered.
- **Cross-language match.** `ScenariosCategory::IdByCategory` matches a
  category name across languages.
- **Costly code.**
  - `RegisterController::randomGen` shuffles a million-element range
    for every new user name.
  - The ranking pages run one query per row.
  - Opening the self-evaluation test page with a GET creates an
    attempt.

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
- **Laravel 7.15 and PHPUnit 8.5 API gaps.**
  - `forgetGuards()` does not exist yet: log the guard out instead.
  - Use `assertFileNotExists`, not `assertFileDoesNotExist`, and
    `assertRegExp`.
  - PHPUnit 8.5 is the last line that runs on PHP 7.2. Composer refuses
    releases with a known security advisory, so the lock holds 8.5.56.
- **Apache tests commit.** Apache runs the app in its own processes,
  which see only committed rows. `ApacheTestCase` therefore gives its
  tests no transaction. `World::removeAll` deletes their rows and the
  rows the app wrote for the World's members.
- **Session cookies.** Apache's cookies are kept by hand, not in
  Guzzle's jar. The jar would hold back a Secure cookie over plain
  HTTP, so the session would break on the day `session-cookie-secure`
  is fixed.
