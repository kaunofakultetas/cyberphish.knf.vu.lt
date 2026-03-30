<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $url = env('APP_URL').'/verify/'.$this->data['uid'];

        return $this->view('emails.userverification')->subject(__('main.account_verification_subject'))->with([ 'link_url'=>$url ]);
    }
}