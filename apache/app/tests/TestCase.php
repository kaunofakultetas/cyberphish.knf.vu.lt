<?php

// -----------------------------------------------------------
//  [*] TestCase — the base of every test in the suite
//
//  Boots the app for every test, fakes the mailer so no
//  mail ever leaves for an SMTP server, and gives the test
//  a World to build its rows with. FeatureTestCase wraps
//  each test in a transaction on top of it; ApacheTestCase
//  adds an HTTP client for the real Apache instead.
//
//  Used by:
//    - FeatureTestCase, ApacheTestCase (this folder)
//    - the Platform suite (Platform/)
// -----------------------------------------------------------


namespace Tests;

use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
use App\Models\Managers;
use App\Models\Members;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Tests\Support\KnownDefects;
use Tests\Support\World;








// -----------------------------------------------------------
// TestCase
// -----------------------------------------------------------
//
// The shared setup and the helpers every suite leans on.
// The reCAPTCHA check would call Google, which the test
// container cannot reach, so a test that submits a form
// carrying the rule answers it itself. Signing in goes
// straight to the guards — the member guard is mem, the
// manager guard man — with the user object a real login
// leaves there: both guards read their users through
// Laravel's database provider, which hands out a
// GenericUser rather than the app's model. A manager's
// session also gets the main and country values
// AdminLoginController stores at login, because the Perms
// middleware reads main from the session, not from the
// database.
//
//   setUp         — the fresh app, the mail fake, the World
//   solveCaptcha  — answers the reCAPTCHA check
//   signInMember  — a member session for the next requests
//   signInManager — a manager session, main admin or not
//   mailLink      — the link a sent mail carries
//   uploadedFile  — a file with real content to upload
//
// Used by:
//   - FeatureTestCase, ApacheTestCase (this folder)
//   - the Platform suite (Platform/)
// -----------------------------------------------------------

abstract class TestCase extends LaravelTestCase
{

    // The app for every test, and pinDefect
    use CreatesApplication;
    use KnownDefects;

    // The rows of the test running now are built through it
    protected $world;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A fresh app (Laravel's own setUp), the mail fake, and a
    // fresh World. SettingsController::getUserIP — which
    // BlockIpMiddleware runs on every request — reads
    // REMOTE_ADDR from the $_SERVER superglobal instead of the
    // request, and PHP's command line never sets it, so the
    // test sets the loopback address Apache would.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        parent::setUp();
        Mail::fake();
        $this->world = new World();
    }






    // -----------------------------------------------------------
    // solveCaptcha
    // -----------------------------------------------------------
    //
    // Decides what the reCAPTCHA check answers for the rest of
    // the test: solved, or refused when the test passes false.
    // The rule asks the NoCaptcha service through its facade,
    // which Mockery replaces here.
    //
    // Used by:
    //   - RegistrationTest, PasswordResetTest (Feature/Auth/)
    // -----------------------------------------------------------

    protected function solveCaptcha(bool $solved = true): void
    {
        NoCaptcha::shouldReceive('verifyResponse')->andReturn($solved);
    }






    // -----------------------------------------------------------
    // signInMember
    // -----------------------------------------------------------
    //
    // Puts the member on the mem guard for the requests that
    // follow, as the GenericUser a successful member login
    // leaves there.
    //
    // Used by:
    //   - the Feature suite — every test of a member page
    // -----------------------------------------------------------

    protected function signInMember(Members $member): self
    {
        $user = $this->app['auth']->guard('mem')->getProvider()->retrieveById($member->id);
        return $this->actingAs($user, 'mem');
    }






    // -----------------------------------------------------------
    // signInManager
    // -----------------------------------------------------------
    //
    // Puts the manager on the man guard and gives the session
    // the main and country values of the manager's row, as the
    // admin login does: a main admin passes the Perms
    // middleware, a country manager gets its 404.
    //
    // Used by:
    //   - the Feature suite — every test of an admin page
    // -----------------------------------------------------------

    protected function signInManager(Managers $manager): self
    {
        $user = $this->app['auth']->guard('man')->getProvider()->retrieveById($manager->id);
        return $this->actingAs($user, 'man')
            ->withSession(['main' => $manager->main, 'country' => $manager->country]);
    }






    // -----------------------------------------------------------
    // mailLink
    // -----------------------------------------------------------
    //
    // The link a verification or reset mail carries: the
    // mailable's build puts it into the view data as link_url,
    // and the rendered view must print it. Built here rather
    // than through Mailable::render, which needs the real
    // mailer the mail fake replaces.
    //
    // Used by:
    //   - RegistrationTest, PasswordResetTest (Feature/Auth/)
    // -----------------------------------------------------------

    protected function mailLink(Mailable $mail): string
    {
        $mail->build();
        $link = $mail->viewData['link_url'];
        $this->assertStringContainsString($link, view($mail->view, $mail->viewData)->render());
        return $link;
    }






    // -----------------------------------------------------------
    // uploadedFile
    // -----------------------------------------------------------
    //
    // A file upload with the given name and real content —
    // the imports read what is inside, which Laravel's fake
    // files leave empty. The content goes to a temporary file
    // in /tmp, the container's tmpfs.
    //
    // Used by:
    //   - AdminContentTest, AdminLearningTest,
    //     AdminSimulationsTest (Feature/Admin/)
    //   - Sheets::simulationSheet (Support/)
    // -----------------------------------------------------------

    protected function uploadedFile(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);
        return new UploadedFile($path, $name, null, null, true);
    }
}
