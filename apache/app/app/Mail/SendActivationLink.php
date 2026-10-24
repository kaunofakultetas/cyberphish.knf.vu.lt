<?php

// -----------------------------------------------------------
//  [*] SendActivationLink — the mail to an address whose
//      account still waits for its verification
//
//  Sent when someone signs up again with an address whose
//  account was never activated. The link is the reset
//  page's, built on APP_URL: opening it and pressing its
//  button mails a password (SendNewPass) and switches the
//  account on. The account's own verification link is not
//  sent again, as it would switch the account on with the
//  password of whoever signed the address up first. The
//  text says all this in the owner's words, where a plain
//  password-reset mail once arrived. Built and sent during
//  the request, in the visitor's site language.
//
//  Used by:
//    - RegisterController::register (Auth/)
//    - RegistrationTest (tests/Feature/Auth/) — reads the
//      token and the link out of the sent mail
// -----------------------------------------------------------


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;








// -----------------------------------------------------------
// SendActivationLink
// -----------------------------------------------------------
//
// The view emails/useractivation.blade.php, the subject
// main.account_pending_subject, and link_url, the reset
// link, as the view's variable. The mail goes out at once,
// as SendRemindLink does.
//
// Used by:
//   - RegisterController::register (Auth/) — POST
//     /register, for an address whose account still waits
//     for its verification
//   - RegistrationTest (tests/Feature/Auth/) — reads the
//     token and the link out of the sent mail
// -----------------------------------------------------------

class SendActivationLink extends Mailable
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
    //   - RegisterController::register (Auth/)
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

        return $this->view('emails.useractivation')->subject(__('main.account_pending_subject'))->with([ 'link_url'=>$url ]);
    }
}
