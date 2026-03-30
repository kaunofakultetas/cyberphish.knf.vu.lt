<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SimulationsResults extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    { 

        return $this->view('emails.simulationsresults')->subject(__('main.simulations').' '.__('main.results'))->with([ 'points'=>$this->data['points'] ]);
    }
}