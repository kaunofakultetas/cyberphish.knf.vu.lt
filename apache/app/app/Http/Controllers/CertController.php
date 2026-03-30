<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Models\KnowledgeUserTest;
use App\Models\Langs;
use App\Models\Members;

class CertController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

    }

    public function download($lang)
    {
        $num = KnowledgeUserTest::select('ended')
            ->where(['user_id' => Auth::guard('mem')->id(), 'finished' => 1, 'lang_id' => Langs::getLangId($lang)])
            ->where('results', '>', 74)
            ->count();

        $fullname = Members::select('fullname')
            ->where(['id' => Auth::guard('mem')->id()])
            ->get()
            ->pluck('fullname')
            ->first();

        if ($num > 0 && $fullname != '' && $fullname != NULL) {
            $date = date("d F, Y", strtotime(KnowledgeUserTest::select('ended')
                ->where(['user_id' => Auth::guard('mem')->id(), 'finished' => 1, 'lang_id' => Langs::getLangId($lang)])
                ->where('results', '>', 74)
                ->get()
                ->pluck('ended')
                ->first()));

            $pdf = PDF::loadView('pdf.cert', [
                'fullname' => $fullname,
                'date' => $date,
                'backgroundPath' => public_path('cert.png')  // Assuming cert.png is in the public folder
            ]);

            $pdf->setPaper('a4', 'landscape');

            // Configure DomPDF
            $pdf->getDomPDF()->setHttpContext(
                stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])
            );

            return $pdf->stream('cert.pdf');
        } else {
            abort(404);
        }
    }


}