<?php

// -----------------------------------------------------------
//  [*] CertController — the PDF certificate of completion
//
//  A member's certificate for one language, rendered by
//  dompdf (barryvdh/laravel-dompdf) from pdf/cert.blade.php
//  over the background image public/cert.png: the full name
//  the member stored, on the line the image draws for it,
//  and the date of their passed knowledge test. It exists
//  only for a member with a finished attempt above 74 in
//  that language and a full name; anyone else gets a 404,
//  and auth:mem sends visitors to the login form.
//
//  Used by:
//    - routes/web_user.php — cp/{lang}/certificate
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use FontLib\Font;
use Illuminate\Support\Facades\Auth;
use App\Models\KnowledgeUserTest;
use App\Models\Langs;
use App\Models\Members;








// -----------------------------------------------------------
// CertController
// -----------------------------------------------------------
//
// One action and the drawing behind it. The pass mark,
// above 74, is the one KnowledgeTestController applies, and
// the full name comes from
// ManagerMainController::change_fullname.
//
//   download    — the route's action
//   certificate — the PDF for a name and a date
//   name_style  — the font and size the name is drawn in
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
    // first one stored — which stays put however often the
    // attempt's result page is opened again (see
    // KnowledgeTestController::progress). It is written with
    // English month names whatever the language. The drawing
    // itself is certificate's.
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

            return CertController::certificate($fullname, $date)->stream('cert.pdf');
        } else {
            abort(404);
        }
    }






    // -----------------------------------------------------------
    // certificate
    // -----------------------------------------------------------
    //
    // The certificate for a full name and a date, as dompdf's
    // wrapper, ready to stream: pdf/cert.blade.php on A4
    // landscape over public/cert.png, the name in the style
    // name_style works out for it.
    //
    // Used by:
    //   - download (above)
    //   - CertificateTest (tests/Feature/Knowledge/) — draws
    //     names the way the download does
    // -----------------------------------------------------------

    public static function certificate($fullname, $date)
    {
        $pdf = PDF::loadView('pdf.cert', [
            'fullname' => $fullname,
            'date' => $date,
            'backgroundPath' => public_path('cert.png'),
            'nameStyle' => CertController::name_style($fullname)
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

        return $pdf;
    }






    // -----------------------------------------------------------
    // name_style
    // -----------------------------------------------------------
    //
    // The inline style of the name block. The font is the
    // template's Parisienne, a script with Latin letters only,
    // unless the name has a letter it lacks — a Greek name,
    // say — which DejaVu Serif Italic, shipped with dompdf,
    // draws instead. The size is the template's 62px unless
    // the name would then run wider than the line under it,
    // 620 of the 842 points the page is wide: then it shrinks
    // until it fits, so a long name or one in capitals stays
    // on one line instead of wrapping over the course's text.
    // A padding over a smaller name, or one in the other
    // font, gives the block the height the template's name
    // has, so it still stands on the line and the date the
    // template lays out under the block keeps its place. A
    // name the template drew well keeps its look exactly.
    //
    // Used by:
    //   - certificate (above)
    // -----------------------------------------------------------

    public static function name_style($fullname)
    {
        // STEP 1: the font — the fonts dompdf knows come from
        // storage/fonts (Parisienne) and from dompdf itself
        // ===================================================
        $metrics = app('dompdf.wrapper')->getDomPDF()->getFontMetrics();
        $parisienne = $metrics->getFont('parisienne');

        $font = Font::load($parisienne . '.ttf');
        $font->parse();
        $map = $font->getUnicodeCharMap();
        $font->close();

        $letters = preg_split('//u', str_replace(' ', '', $fullname), -1, PREG_SPLIT_NO_EMPTY);
        $lacking = array_filter($letters, function ($letter) use ($map) {
            return !isset($map[mb_ord($letter)]);
        });

        if (count($lacking) == 0) {
            $family = "'Parisienne', cursive";
            $style = 'normal';
            $file = $parisienne;
        } else {
            $family = "'DejaVu Serif'";
            $style = 'italic';
            $file = $metrics->getFont('DejaVu Serif', 'italic');
        }


        // STEP 2: the size — the width at 62px, 46.5 points,
        // against the 620 points of the line
        // ==================================================
        $width = $metrics->getTextWidth($fullname, $file, 46.5);
        $size = $width > 620 ? floor(62 * 620 / $width) : 62;


        // STEP 3: the place — dompdf makes the name's line 1.2
        // font heights tall, so the padding is what the line
        // comes short of the template's, Parisienne at 62px
        // ====================================================
        $padding = 1.2 * ($metrics->getFontHeight($parisienne, 46.5) - $metrics->getFontHeight($file, $size * 0.75));

        $css = "font-family: $family; font-style: $style; font-size: {$size}px";

        return $padding > 0 ? $css . sprintf('; padding-top: %.4fpt', $padding) : $css;
    }
}
