<?php

// -----------------------------------------------------------
//  [*] Sheets — the simulation spreadsheet of the import
//
//  Builds the spreadsheet the panel's simulation import
//  (Admin\SimulationsController::simulations_import) reads,
//  in the project's layout: the texts in C19 to C30, the
//  categories and attributes marked "Yes" from row 34 and
//  the steps from row 58 down. A test replaces single cells
//  to bend the sheet to its case and leaves the rest valid.
//
//  Used by:
//    - AdminSimulationsTest, AdminDefectsTest (Feature/Admin/)
// -----------------------------------------------------------


namespace Tests\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;








// -----------------------------------------------------------
// Sheets
// -----------------------------------------------------------
//
// Mixed into the test classes that import simulations; the
// upload goes through TestCase::uploadedFile.
//
//   simulationSheet — the spreadsheet, as an uploaded file
//
// Used by:
//   - AdminSimulationsTest, AdminDefectsTest (Feature/Admin/)
// -----------------------------------------------------------

trait Sheets
{






    // -----------------------------------------------------------
    // simulationSheet
    // -----------------------------------------------------------
    //
    // A simulation spreadsheet the import accepts: the texts,
    // the category "Credential theft" and the attribute
    // "Urgency" marked Yes, and three steps — checking the
    // sender (Correct) with reporting it under it
    // (Semi-correct), and clicking the link (Incorrect). The
    // cells given replace the ones of that sheet.
    //
    // Used by:
    //   - AdminSimulationsTest::testSpreadsheetBecomesASimulation
    //   - AdminDefectsTest — testFailedImportLeavesNothingBehind
    //     and testImportFilesUnderTheCategoriesOfItsLanguage
    // -----------------------------------------------------------

    protected function simulationSheet(array $cells = []): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $texts = [
            'C19' => 'An urgent invoice arrives',
            'C20' => 'Spot the fake',
            'C21' => 'Finance clerk',
            'C22' => 'Email',
            'C26' => '1',
            'C27' => 'Phishing',
            'C30' => 'invoice',
            'B34' => 'Yes',
            'C34' => 'Credential theft',
            'D34' => 'Yes',
            'E34' => 'Urgency',
        ];
        foreach (array_merge($texts, $cells) as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $steps = [
            58 => [1, 'Check the sender', 'Correct', 'Good: the domain is wrong'],
            59 => [2, 'Report it to IT', 'Semi-correct', 'IT blocks the sender'],
            60 => [1, 'Click the link', 'Incorrect', 'That installs malware'],
        ];
        foreach ($steps as $row => list($level, $situation, $answer, $feedback)) {
            $sheet->setCellValue("A$row", $level);
            $sheet->setCellValue("C$row", $situation);
            $sheet->setCellValue("D$row", $answer);
            $sheet->setCellValue("E$row", $feedback);
        }

        $path = tempnam(sys_get_temp_dir(), 'sheet');
        (new Xlsx($spreadsheet))->save($path);
        return $this->uploadedFile('simulation.xlsx', file_get_contents($path));
    }
}
