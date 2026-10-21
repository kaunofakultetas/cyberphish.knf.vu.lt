<?php

// -----------------------------------------------------------
//  [*] LMFiles — the files attached to a lesson
//
//  One bei_lm_content_files row per file a lesson carries:
//  either embedded in the lesson page or offered as a
//  download. The panel stores the upload under
//  public/upload/lm/ with a random name.
//
//  Used by:
//    - LMController, Admin\LMController (Http/Controllers/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// LMFiles
// -----------------------------------------------------------
//
// Columns: content_id (bei_lm_content), name (the label the
// page shows), file_name (the path under public/upload),
// mime_type, file_size in bytes, and embed — 1 shows the
// file inside the lesson page, 0 lists it as a download.
//
// Used by:
//   - LMController::content — the lesson's embedded files
//     and downloads
//   - Admin\LMController — additional_files, files_form,
//     files_create, files_update, files_delete, and delete
//     (a lesson's files go with it)
// -----------------------------------------------------------

class LMFiles extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_lm_content_files";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns create() and fill() would accept; nothing
    // calls either on this model
    protected $fillable = [
        'id', 'content_id', 'name', 'file_name', 'mime_type', 'file_size', 'embed'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model
    protected $casts = [
        'id'=>'integer',
        'content_id'=>'integer',
        'name'=>'string',
        'file_name'=>'string',
        'mime_type'=>'string',
        'file_size'=>'integer',
        'embed'=>'integer'
    ];
}
