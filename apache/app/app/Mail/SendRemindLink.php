<?php

// -----------------------------------------------------------
//  [*] SendRemindLink — the password-reset link mail
//
//  The first mail of a password reset: the forgot-password
//  form mails the member a link to the reset page, built on
//  APP_URL. Opening the link makes up a new password and
//  mails it (SendNewPass). Built and sent during the
//  request, in the visitor's site language.
//
//  Used by:
//    - LoginController::forgotpassword (Auth/)
//    - PasswordResetTest (tests/Feature/Auth/) — reads the
//      token and the link out of the sent mail
// -----------------------------------------------------------


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;








// -----------------------------------------------------------
// SendRemindLink
// -----------------------------------------------------------
//
// The view emails/userpassreset.blade.php, the subject
// main.pass_reset_subject, and link_url, the reset link, as
// the view's variable. APP_URL comes straight from the
// environment, which the container sets. The mail goes out
// at once: the class imports ShouldQueue without
// implementing it.
//
// Used by:
//   - LoginController::forgotpassword (Auth/) — POST
//     /forgot-password, for an address that belongs to one
//     member
//   - PasswordResetTest (tests/Feature/Auth/) — reads the
//     token and the link out of the sent mail
// -----------------------------------------------------------

class SendRemindLink extends Mailable
{

    // Laravel's stock mailable traits
    use Queueable, SerializesModels;

    // What the sender passes: uid, the reset token, which the
    // sender also stores as the member's reset_token.
    // Public, so Laravel hands it to the view as well
    public $data;






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Keeps the sender's data for build.
    //
    // Used by:
    //   - LoginController::forgotpassword (Auth/)
    // -----------------------------------------------------------

    public function __construct($data)
    {
        $this->data = $data;
    }






    // -----------------------------------------------------------
    // build
    // -----------------------------------------------------------
    //
    // The mail itself: the link, APP_URL with /reset/ and the
    // token, as link_url, the view and the translated subject.
    //
    // Used by:
    //   - Laravel's mailer, when the sender sends the mail
    //   - TestCase::mailLink (tests/) — builds the faked mail
    //     to read its link
    // -----------------------------------------------------------

    public function build()
    {
        $url = env('APP_URL').'/reset/'.$this->data['uid'];

        return $this->view('emails.userpassreset')->subject(__('main.pass_reset_subject'))->with([ 'link_url'=>$url ]);
    }
}
