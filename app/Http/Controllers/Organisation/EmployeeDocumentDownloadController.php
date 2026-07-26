<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentDownloadController extends Controller
{
    public function __invoke(Employee $employee, EmployeeDocument $document)
    {
        abort_unless($document->employee_id === $employee->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->title);
    }
}
