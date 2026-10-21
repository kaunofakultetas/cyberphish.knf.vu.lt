<?php

// -----------------------------------------------------------
//  [*] SendVerification — the account verification mail
//
//  Sent when someone registers: a link to the verification
//  page, built on APP_URL, which switches the new account
//  on. Built and sent during the request, in the visitor's
//  site language.
//
//  Used by:
//    - RegisterController::register (Auth/)
// -----------------------------------------------------------


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;








// -----------------------------------------------------------
// SendVerification
// -----------------------------------------------------------
//
// The view emails/userverification.blade.php, the subject
// main.account_verification_subject, and link_url, the
// verification link, as the view's variable. APP_URL comes
// straight from the environment, which the container sets.
// The mail goes out at once, before the sender writes the
// account: the class imports ShouldQueue without
// implementing it.
//
// Used by:
//   - RegisterController::register (Auth/) — POST
//     /register, for an address no member has yet
// -----------------------------------------------------------

class SendVerification extends Mailable
{

    // Laravel's stock mailable traits
    use Queueable, SerializesModels;

    // What the sender passes: uid, the verification token,
    // which the sender also stores as the account's
    // verify_token. Public, so Laravel hands it to the view
    // as well
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
    // The mail itself: the link, APP_URL with /verify/ and
    // the token, as link_url, the view and the translated
    // subject.
    //
    // Used by:
    //   - Laravel's mailer, when the sender sends the mail
    // -----------------------------------------------------------

    public function build()
    {
        $url = env('APP_URL').'/verify/'.$this->data['uid'];

        return $this->view('emails.userverification')->subject(__('main.account_verification_subject'))->with([ 'link_url'=>$url ]);
    }
}
