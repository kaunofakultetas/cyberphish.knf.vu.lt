<?php

// -----------------------------------------------------------
//  [*] CertController — the PDF certificate of completion
//
//  A member's certificate for one language, rendered by
//  dompdf (barryvdh/laravel-dompdf) from pdf/cert.blade.php
//  over the background image public/cert.png: the full name
//  the member stored and the date of their passed knowledge
//  test. It exists only for a member with a finished
//  attempt above 74 in that language and a full name;
//  anyone else gets a 404, and auth:mem sends visitors to
//  the login form.
//
//  Used by:
//    - routes/web_user.php — cp/{lang}/certificate
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Models\KnowledgeUserTest;
use App\Models\Langs;
use App\Models\Members;








// -----------------------------------------------------------
// CertController
// -----------------------------------------------------------
//
// One action. The pass mark, above 74, is the one
// KnowledgeTestController applies, and the full name comes
// from ManagerMainController::change_fullname.
//
// Used by:
//   - routes/web_user.php — cp/{lang}/certificate
// -----------------------------------------------------------

class CertController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's constructor and nothing else: it
    // registers the middleware that sets the language and
    // shares the member's data with every view.
    //
    // Used by:
    //   - Laravel, when it builds the controller to dispatch
    //     its route
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // download
    // -----------------------------------------------------------
    //
    // GET /cp/{lang}/certificate
    //
    // Streams the certificate, cert.pdf on A4 landscape,
    // behind auth:mem. The member needs a finished knowledge
    // test above 74 in the language and a full name, or the
    // answer is a 404. The date is the end of a passed
    // attempt — the query sets no order, so in practice the
    // first one stored — and it moves whenever that attempt's
    // result page is opened again (see
    // KnowledgeTestController::progress). It is written with
    // English month names whatever the language. The name has
    // room for about 43 characters; a longer one would wrap
    // over the text of the background image.
    // change_fullname stops at 20 today (pinned as
    // fullname-length), so a fix that allows more than 43
    // has to make long names fit.
    //
    // Known bug (medium): the name is drawn in Parisienne,
    // which has no Greek letters, so a Greek member's name
    // cannot print even once fullname-latin1 is fixed. Pinned
    // as certificate-greek-name. Fix: draw such names in a
    // font that has Greek letters (dompdf ships DejaVu Sans).
    //
    // Used by:
    //   - parts/change_fullname.blade.php — the download
    //     button, shown once a full name is stored, on the
    //     knowledge-test page and on the result page of a
    //     passed attempt
    // -----------------------------------------------------------

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
                'backgroundPath' => public_path('cert.png')
            ]);

            $pdf->setPaper('a4', 'landscape');

            // TLS certificate checks are off for anything dompdf
            // fetches over HTTPS. The template names no remote
            // file and prints the full name escaped, so nothing
            // a member types turns into a fetch.
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
