<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ImportEmployeesRequest;
use App\Modules\Core\Imports\EmployeeImport;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportController extends Controller
{
    public function template(): StreamedResponse
    {
        $headers = [
            'First Name',
            'Last Name',
            'Middle Name',
            'Email',
            'Date of Birth',
            'Gender',
            'Phone',
            'Department',
            'Position',
            'Employment Type',
            'Employment Status',
            'Employee ID',
            'Salary',
            'Salary Type',
            'TIN',
            'SSS Number',
            'PhilHealth Number',
            'PagIBIG Number',
        ];

        $notes = [
            'Required. Employee first name.',
            'Required. Employee last name.',
            'Optional.',
            'Required. Must be unique.',
            'Optional. Format: YYYY-MM-DD',
            'Optional. male / female',
            'Optional.',
            'Optional. Must match existing department name.',
            'Optional. Must match existing position name.',
            'Optional. full-time / part-time / contractual / probationary',
            'Optional. active (default) / inactive',
            'Optional. Auto-generated if blank (EMP-XXXX).',
            'Optional. Numeric.',
            'Optional. monthly (default) / daily',
            'Optional.',
            'Optional.',
            'Optional.',
            'Optional.',
        ];

        $example = [
            'Juan', 'Dela Cruz', 'Santos', 'juan.delacruz@company.com',
            '1990-01-15', 'male', '09171234567',
            'IT Department', 'Developer', 'full-time', 'active',
            '', '35000', 'monthly',
            '123-456-789', '12-3456789-0', '123456789012', '1234567890123456',
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        foreach ($headers as $col => $header) {
            $cell = $sheet->getCell([$col + 1, 1]);
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF3B82F6');
            $cell->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        foreach ($notes as $col => $note) {
            $noteCell = $sheet->getCell([$col + 1, 2]);
            $noteCell->setValue($note);
            $noteCell->getStyle()->getFont()->setItalic(true);
            $noteCell->getStyle()->getFont()->getColor()->setARGB('FF6B7280');
        }

        foreach ($example as $col => $value) {
            $sheet->getCell([$col + 1, 3])->setValue($value);
        }

        $colCount = \count($headers);
        foreach (range(1, $colCount) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'employee-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(ImportEmployeesRequest $request): JsonResponse
    {
        $import = new EmployeeImport($request->user()->company_id);
        Excel::import($import, $request->file('file'));

        return response()->json([
            'results' => $import->getResults(),
        ]);
    }
}
