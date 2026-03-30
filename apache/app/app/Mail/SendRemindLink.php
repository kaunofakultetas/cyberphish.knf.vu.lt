<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendRemindLink extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {


        $url = env('APP_URL').'/reset/'.$this->data['uid'];

        return $this->view('emails.userpassreset')->subject(__('main.pass_reset_subject'))->with([ 'link_url'=>$url ]);
    }
}