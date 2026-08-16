<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);

        $activeEmployees = Employee::where('status', Employee::STATUS_ACTIVE)
            ->with(['site', 'department'])
            ->get();

        $workforce = $this->workforceReport($activeEmployees);
        $attendance = $this->attendanceReport($year);
        $leaves = $this->leaveReport($activeEmployees, $year);
        $payroll = $this->payrollReport($year);
        $movements = $this->movementsReport($year);

        return view('reports.dashboard', [
            'year' => $year,
            'availableYears' => $this->availableYears(),
            'workforce' => $workforce,
            'attendance' => $attendance,
            'leaves' => $leaves,
            'payroll' => $payroll,
            'movements' => $movements,
            'chartData' => $this->chartPayload($year, $workforce, $attendance, $leaves, $payroll, $movements),
        ]);
    }

    private function chartPayload(int $year, array $workforce, array $attendance, array $leaves, array $payroll, array $movements): array
    {
        $monthLabels = collect(range(1, 12))
            ->map(fn (int $month) => Carbon::create($year, $month, 1)->translatedFormat('M'))
            ->values()
            ->all();

        return [
            'workforce' => [
                'byDepartment' => $workforce['by_department'],
                'bySite' => $workforce['by_site'],
                'byStatus' => $workforce['by_status'],
                'ageBrackets' => $workforce['age_brackets'],
            ],
            'attendance' => [
                'months' => $monthLabels,
                'lateCounts' => $attendance['by_month']->pluck('late_count')->values()->all(),
                'overtimeHours' => $attendance['by_month']->pluck('overtime_hours')->values()->all(),
            ],
            'leaves' => [
                'types' => $leaves['by_type']->pluck('name')->values()->all(),
                'accrued' => $leaves['by_type']->pluck('accrued')->values()->all(),
                'used' => $leaves['by_type']->pluck('used')->values()->all(),
                'remaining' => $leaves['by_type']->pluck('remaining')->values()->all(),
            ],
            'payroll' => [
                'months' => $monthLabels,
                'gross' => $payroll['by_month']->pluck('gross')->values()->all(),
                'net' => $payroll['by_month']->pluck('net')->values()->all(),
                'employerCharges' => $payroll['by_month']->pluck('employer_charges')->values()->all(),
            ],
            'movements' => [
                'months' => $monthLabels,
                'hires' => $movements['by_month']->pluck('hires')->values()->all(),
                'departures' => $movements['by_month']->pluck('departures')->values()->all(),
            ],
        ];
    }

    public function exportWorkforce(): StreamedResponse
    {
        $employees = Employee::where('status', Employee::STATUS_ACTIVE)
            ->with(['site', 'department', 'position'])
            ->orderBy('last_name')
            ->get();

        return $this->csvResponse('effectifs.csv', ['Matricule', 'Nom', 'Prénom', 'Site', 'Département', 'Poste', 'Date d\'embauche', 'Ancienneté'], function ($handle) use ($employees) {
            foreach ($employees as $employee) {
                fputcsv($handle, [
                    $employee->employee_number,
                    $employee->last_name,
                    $employee->first_name,
                    $employee->site?->name,
                    $employee->department?->name,
                    $employee->position?->name,
                    $employee->hire_date?->toDateString(),
                    $employee->seniorityLabel(now()),
                ]);
            }
        });
    }

    public function exportPayroll(Request $request): StreamedResponse
    {
        $year = (int) $request->input('year', now()->year);
        $months = $this->payrollReport($year)['by_month'];

        return $this->csvResponse("masse-salariale-{$year}.csv", ['Mois', 'Brut', 'Net', 'Charges patronales'], function ($handle) use ($months, $year) {
            foreach ($months as $month) {
                fputcsv($handle, [
                    Carbon::create($year, $month['month'], 1)->translatedFormat('F Y'),
                    $month['gross'],
                    $month['net'],
                    $month['employer_charges'],
                ]);
            }
        });
    }

    public function exportLeaves(Request $request): StreamedResponse
    {
        $year = (int) $request->input('year', now()->year);
        $activeEmployees = Employee::where('status', Employee::STATUS_ACTIVE)->get();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return $this->csvResponse("soldes-conges-{$year}.csv", ['Matricule', 'Nom', 'Prénom', 'Type de congé', 'Acquis', 'Pris', 'Restant'], function ($handle) use ($activeEmployees, $leaveTypes, $year) {
            foreach ($activeEmployees as $employee) {
                foreach ($leaveTypes as $type) {
                    $balance = LeaveBalance::forEmployee($employee, $type, $year);
                    fputcsv($handle, [
                        $employee->employee_number,
                        $employee->last_name,
                        $employee->first_name,
                        $type->name,
                        round($balance->accruedDays(), 2),
                        round($balance->usedDays(), 2),
                        round($balance->availableDays(), 2),
                    ]);
                }
            }
        });
    }

    private function csvResponse(string $filename, array $header, callable $writeRows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $writeRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $header);
            $writeRows($handle);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function availableYears(): array
    {
        $currentYear = (int) now()->year;

        return range($currentYear - 4, $currentYear);
    }

    private function workforceReport($activeEmployees): array
    {
        $bySite = $activeEmployees->groupBy(fn (Employee $e) => $e->site?->name ?? 'Non renseigné')
            ->map->count()
            ->sortDesc();

        $byDepartment = $activeEmployees->groupBy(fn (Employee $e) => $e->department?->name ?? 'Non renseigné')
            ->map->count()
            ->sortDesc();

        $byStatus = Employee::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $withHireDate = $activeEmployees->filter(fn (Employee $e) => $e->hire_date);
        $averageTenureYears = $withHireDate->isNotEmpty()
            ? round($withHireDate->avg(fn (Employee $e) => $e->hire_date->diffInDays(now()) / 365.25), 1)
            : 0.0;

        $ageBrackets = ['< 25 ans' => 0, '25-34 ans' => 0, '35-44 ans' => 0, '45-54 ans' => 0, '55 ans et +' => 0, 'Non renseigné' => 0];

        foreach ($activeEmployees as $employee) {
            if (! $employee->birth_date) {
                $ageBrackets['Non renseigné']++;

                continue;
            }

            $age = $employee->birth_date->age;
            $bracket = match (true) {
                $age < 25 => '< 25 ans',
                $age < 35 => '25-34 ans',
                $age < 45 => '35-44 ans',
                $age < 55 => '45-54 ans',
                default => '55 ans et +',
            };
            $ageBrackets[$bracket]++;
        }

        return [
            'total' => $activeEmployees->count(),
            'by_site' => $bySite,
            'by_department' => $byDepartment,
            'by_status' => $byStatus,
            'average_tenure_years' => $averageTenureYears,
            'age_brackets' => $ageBrackets,
        ];
    }

    private function attendanceReport(int $year): array
    {
        $months = collect(range(1, 12))->map(function (int $month) use ($year) {
            $entries = TimeEntry::whereYear('date', $year)
                ->whereMonth('date', $month)
                ->with('employee.workSchedule')
                ->get();

            $lateCount = $entries->filter(fn (TimeEntry $e) => $e->lateMinutes($e->employee->effectiveWorkSchedule()) > 0)->count();
            $overtimeHours = round($entries->sum(fn (TimeEntry $e) => $e->overtimeMinutes($e->employee->effectiveWorkSchedule())) / 60, 1);

            return [
                'month' => $month,
                'total_entries' => $entries->count(),
                'late_count' => $lateCount,
                'overtime_hours' => $overtimeHours,
            ];
        });

        $currentMonthData = $months->firstWhere('month', now()->month) ?? $months->last();
        $punctualityRate = $currentMonthData['total_entries'] > 0
            ? round((1 - $currentMonthData['late_count'] / $currentMonthData['total_entries']) * 100, 1)
            : null;

        return [
            'by_month' => $months,
            'punctuality_rate' => $punctualityRate,
        ];
    }

    private function leaveReport($activeEmployees, int $year): array
    {
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        $byType = $leaveTypes->map(function (LeaveType $type) use ($activeEmployees, $year) {
            $accrued = 0.0;
            $used = 0.0;
            $remaining = 0.0;

            foreach ($activeEmployees as $employee) {
                $balance = LeaveBalance::forEmployee($employee, $type, $year);
                $accrued += $balance->accruedDays();
                $used += $balance->usedDays();
                $remaining += $balance->availableDays();
            }

            return [
                'name' => $type->name,
                'accrued' => round($accrued, 2),
                'used' => round($used, 2),
                'remaining' => round($remaining, 2),
            ];
        });

        return ['by_type' => $byType];
    }

    private function payrollReport(int $year): array
    {
        $months = collect(range(1, 12))->map(function (int $month) use ($year) {
            $payslips = Payslip::where('status', Payslip::STATUS_VALIDATED)
                ->whereYear('period', $year)
                ->whereMonth('period', $month)
                ->get();

            return [
                'month' => $month,
                'gross' => round((float) $payslips->sum('gross_amount'), 2),
                'net' => round((float) $payslips->sum('net_amount'), 2),
                'employer_charges' => round((float) $payslips->sum('employer_charges_amount'), 2),
            ];
        });

        return [
            'by_month' => $months,
            'total_net_ytd' => round($months->sum('net'), 2),
            'total_gross_ytd' => round($months->sum('gross'), 2),
        ];
    }

    private function movementsReport(int $year): array
    {
        $months = collect(range(1, 12))->map(function (int $month) use ($year) {
            return [
                'month' => $month,
                'hires' => Employee::withTrashed()->whereYear('hire_date', $year)->whereMonth('hire_date', $month)->count(),
                'departures' => Employee::withTrashed()->whereYear('termination_date', $year)->whereMonth('termination_date', $month)->count(),
            ];
        });

        return [
            'by_month' => $months,
            'total_hires' => $months->sum('hires'),
            'total_departures' => $months->sum('departures'),
        ];
    }
}
