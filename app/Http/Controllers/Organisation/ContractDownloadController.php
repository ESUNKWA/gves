<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;

class ContractDownloadController extends Controller
{
    public function __invoke(Employee $employee, Contract $contract)
    {
        abort_unless($contract->employee_id === $employee->id, 404);
        abort_unless($contract->document_path, 404);

        return Storage::disk('local')->download($contract->document_path);
    }
}
