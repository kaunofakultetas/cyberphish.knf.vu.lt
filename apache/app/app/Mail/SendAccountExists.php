<?php

// -----------------------------------------------------------
//  [*] SendAccountExists — the mail to an address that signs
//      up again
//
//  Sent when someone signs up with an address whose account
//  is active. The sign-up page answers as it does for a new
//  address, so that it tells no one which addresses have
//  accounts, and this mail tells the address's owner
//  instead: a link to the login form, where a forgotten
//  password can be asked for again. Built and sent during
//  the request, in the visitor's site language.
//
//  Used by:
//    - RegisterController::register (Auth/)
//    - RegistrationTest (tests/Feature/Auth/) — checks the
//      mail and its link
// -----------------------------------------------------------


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;








// -----------------------------------------------------------
// SendAccountExists
// -----------------------------------------------------------
//
// The view emails/useraccountexists.blade.php, the subject
// main.account_exists_subject, and link_url, the login
// form's address on APP_URL, as the view's variable. The
// mail goes out at once, as SendVerification does.
//
// Used by:
//   - RegisterController::register (Auth/) — POST
//     /register, for an address whose account is active
//   - RegistrationTest (tests/Feature/Auth/) — reads the
//     link out of the sent mail
// -----------------------------------------------------------

class SendAccountExists extends Mailable
{

    // Laravel's stock mailable traits
    use Queueable, SerializesModels;






    // -----------------------------------------------------------
    // build
    // -----------------------------------------------------------
    //
    // The mail itself: the login form's address, APP_URL with
    // /login, as link_url, the view and the translated
    // subject.
    //
    // Used by:
    //   - Laravel's mailer, when the sender sends the mail
    //   - TestCase::mailLink (tests/) — builds the faked mail
    //     to read its link
    // -----------------------------------------------------------

    public function build()
    {
        $url = env('APP_URL').'/login';

        return $this->view('emails.useraccountexists')->subject(__('main.account_exists_subject'))->with([ 'link_url'=>$url ]);
    }
}
