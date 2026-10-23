<?php

// -----------------------------------------------------------
//  [*] SendNewPass — the mail with a new password
//
//  The second mail of a password reset: when a member opens
//  the reset link, the site makes up a new password and
//  mails it in plain text, with a link to the site to sign
//  in with it. Built and sent during the request, in the
//  visitor's site language.
//
//  Used by:
//    - LoginController::reset_pass (Auth/)
//    - PasswordResetTest (tests/Feature/Auth/) — reads the
//      new password out of the sent mail
// -----------------------------------------------------------


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;








// -----------------------------------------------------------
// SendNewPass
// -----------------------------------------------------------
//
// The view emails/usernewpass.blade.php, the subject
// main.new_pass_email_subject, and new_pass, the password
// in plain text, as the view's variable. The mail goes out
// at once: the class imports ShouldQueue without
// implementing it.
//
// Used by:
//   - LoginController::reset_pass (Auth/) — GET
//     /reset/{reset_token}, the link SendRemindLink carries
//   - PasswordResetTest (tests/Feature/Auth/) — reads the
//     new password out of the sent mail
// -----------------------------------------------------------

class SendNewPass extends Mailable
{

    // Laravel's stock mailable traits
    use Queueable, SerializesModels;

    // What the sender passes: new_pass, the new password.
    // Public, so Laravel hands it to the view as well
    public $data;






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Keeps the sender's data for build.
    //
    // Used by:
    //   - LoginController::reset_pass (Auth/)
    // -----------------------------------------------------------

    public function __construct($data)
    {
        $this->data = $data;
    }






    // -----------------------------------------------------------
    // build
    // -----------------------------------------------------------
    //
    // The mail itself: the view, the translated subject and
    // the new password as new_pass.
    //
    // Used by:
    //   - Laravel's mailer, when the sender sends the mail
    // -----------------------------------------------------------

    public function build()
    {
        return $this->view('emails.usernewpass')->subject(__('main.new_pass_email_subject'))->with([ 'new_pass'=>$this->data['new_pass'] ]);
    }
}
