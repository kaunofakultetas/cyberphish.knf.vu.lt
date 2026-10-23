<?php

// -----------------------------------------------------------
//  [*] en/pagination — the paginator's labels, in English
//
//  Laravel's stock pagination file: previous and next label
//  the arrows around the page numbers that links() renders.
//  The admin panel's lists always read this file, since the
//  panel never switches the locale; the public news list
//  reads it when the site is in English.
//
//  Used by:
//    - Laravel's paginator — links() in news.blade.php and in
//      the admin lists of members, information pages, a
//      lesson's files, a category's self-evaluation and
//      knowledge test questions, and a member's histories
// -----------------------------------------------------------


return [

    'previous' => '&laquo; Previous',
    'next' => 'Next &raquo;',

];
