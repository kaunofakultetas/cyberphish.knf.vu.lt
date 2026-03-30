<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information as Information;

class InfoController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

    }

    public function content($lang=null, $slug, $uid){

        return view('information', ['content'=>Information::where(['id'=>$uid])->get()->first()]);
    }


}