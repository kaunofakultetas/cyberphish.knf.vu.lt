<?php

// -----------------------------------------------------------
//  [*] PlatformDefectsTest — known defects of the image
//
//  Each test states what the image as shipped should provide
//  and pins the defect that breaks it today (KnownDefects):
//  the run reports them as incomplete, tagged with their
//  ids, until a fix makes one pass — then the test fails as
//  the cue to remove its pin and move it into its home file.
//
//    upload-limit  — PHP refuses uploads above 2 MB, below
//                    most of the course's lesson files
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Tests\TestCase;








// -----------------------------------------------------------
// PlatformDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; reads the
// PHP configuration the image ships.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class PlatformDefectsTest extends TestCase
{






    // -----------------------------------------------------------
    // testUploadsTakeTheCoursesLessonFiles
    // -----------------------------------------------------------
    //
    // The image installs php.ini-production as it comes, with
    // upload_max_filesize at 2M and post_max_size at 8M, while
    // 68 of production's 83 lesson files weigh 2.1 to 5.4 MB:
    // such an upload reaches the panel as no file at all, "No
    // file was uploaded!". Wanted: room for a 6 MB file, with
    // the post limit above it.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUploadsTakeTheCoursesLessonFiles(): void
    {
        $upload = ini_parse_quantity(ini_get('upload_max_filesize'));
        $post = ini_parse_quantity(ini_get('post_max_size'));

        $this->pinDefect('upload-limit', function () use ($upload, $post) {
            $this->assertGreaterThanOrEqual(6 * 1024 * 1024, $upload, 'upload_max_filesize is below 6 MB');
            $this->assertGreaterThan($upload, $post, 'post_max_size does not leave room above the file');
        });
    }
}
