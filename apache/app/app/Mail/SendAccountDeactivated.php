<?php

// -----------------------------------------------------------
//  [*] SendAccountDeactivated — the mail to an address whose
//      account an admin switched off
//
//  Sent when someone signs up with an address whose account
//  an admin switched off: the sign-up page answers as for a
//  new address, and this mail tells the address's owner
//  that the account exists but is deactivated, and whom to
//  ask. Every address in use gets one mail, as a new one
//  does, so none answers sooner; this one once got none.
//  Built and sent during the request, in the visitor's site
//  language.
//
//  Used by:
//    - RegisterController::register (Auth/)
//    - RegistrationTest (tests/Feature/Auth/) — checks the
//      mail and its text
// -----------------------------------------------------------


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;








// -----------------------------------------------------------
// SendAccountDeactivated
// -----------------------------------------------------------
//
// The view emails/useraccountdeactivated.blade.php and the
// subject main.account_off_subject; the view needs no
// variable. The mail goes out at once, as SendVerification
// does.
//
// Used by:
//   - RegisterController::register (Auth/) — POST
//     /register, for an address whose account an admin
//     switched off
//   - RegistrationTest (tests/Feature/Auth/) — renders the
//     mail
// -----------------------------------------------------------

class SendAccountDeactivated extends Mailable
{

    // Laravel's stock mailable traits
    use Queueable, SerializesModels;






    // -----------------------------------------------------------
    // build
    // -----------------------------------------------------------
    //
    // The mail itself: the view and the translated subject.
    //
    // Used by:
    //   - Laravel's mailer, when the sender sends the mail
    //   - RegistrationTest (tests/Feature/Auth/) — builds the
    //     mail to read its text
    // -----------------------------------------------------------

    public function build()
    {
        return $this->view('emails.useraccountdeactivated')->subject(__('main.account_off_subject'));
    }
}
