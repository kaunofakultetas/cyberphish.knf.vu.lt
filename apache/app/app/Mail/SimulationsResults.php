<?php

// -----------------------------------------------------------
//  [*] SimulationsResults — a finished simulation's score
//
//  Mails the member the points of a simulation run once its
//  last step is done. Built and sent during the request, in
//  the visitor's site language.
//
//  Used by:
//    - SimulationsController::simulation_do_stuff
// -----------------------------------------------------------


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;








// -----------------------------------------------------------
// SimulationsResults
// -----------------------------------------------------------
//
// The view emails/simulationsresults.blade.php, a subject
// of main.simulations and main.results joined by a space,
// and points as the view's variable. The end page sends it
// once, on the visit that closes the run. The mail goes out
// at once: the class imports ShouldQueue without
// implementing it.
//
// Used by:
//   - SimulationsController::simulation_do_stuff — GET
//     /{lang}/simulation/progress/{public_id}, once the run
//     has no step left
// -----------------------------------------------------------

class SimulationsResults extends Mailable
{

    // Laravel's stock mailable traits
    use Queueable, SerializesModels;

    // What the sender passes: points, the run's total.
    // Public, so Laravel hands it to the view as well
    public $data;






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Keeps the sender's data for build.
    //
    // Used by:
    //   - SimulationsController::simulation_do_stuff
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
    // the run's points as points.
    //
    // Used by:
    //   - Laravel's mailer, when the sender sends the mail
    // -----------------------------------------------------------

    public function build()
    {
        return $this->view('emails.simulationsresults')->subject(__('main.simulations').' '.__('main.results'))->with([ 'points'=>$this->data['points'] ]);
    }
}
