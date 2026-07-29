<?php

use App\Http\Controllers\Administration\CompanySettingsController;
use App\Http\Controllers\Administration\CountryController;
use App\Http\Controllers\Administration\HolidayController;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Attendance\TimeEntryController as AttendanceTimeEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Documents\DocumentRequestController;
use App\Http\Controllers\Documents\DocumentTemplateController;
use App\Http\Controllers\Documents\GeneratedDocumentController;
use App\Http\Controllers\Leaves\LeaveRequestController;
use App\Http\Controllers\Leaves\LeaveTypeController;
use App\Http\Controllers\OnboardingRequestController;
use App\Http\Controllers\Organisation\ContractController;
use App\Http\Controllers\Organisation\ContractDownloadController;
use App\Http\Controllers\Organisation\DepartmentController;
use App\Http\Controllers\Organisation\EmployeeAccountController;
use App\Http\Controllers\Organisation\EmployeeController;
use App\Http\Controllers\Organisation\EmployeeDocumentController;
use App\Http\Controllers\Organisation\EmployeeDocumentDownloadController;
use App\Http\Controllers\Organisation\EmployeeDocumentRequestController;
use App\Http\Controllers\Organisation\EmployeeDocumentViewController;
use App\Http\Controllers\Organisation\EmployeeLeaveRequestController;
use App\Http\Controllers\Organisation\EmployeeOnboardingRequestController;
use App\Http\Controllers\Organisation\EmployeePayComponentController;
use App\Http\Controllers\Organisation\EmployeeTimeEntryController;
use App\Http\Controllers\Organisation\EmployeeWorkScheduleController;
use App\Http\Controllers\Organisation\OnboardingSettingsController;
use App\Http\Controllers\Organisation\PositionController;
use App\Http\Controllers\Organisation\SiteController;
use App\Http\Controllers\Payroll\PayrollComponentController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\PayslipVerificationController;
use App\Http\Controllers\Portal\DocumentController as PortalDocumentController;
use App\Http\Controllers\Portal\DocumentRequestController as PortalDocumentRequestController;
use App\Http\Controllers\Portal\DocumentSignatureController;
use App\Http\Controllers\Portal\LeaveController as PortalLeaveController;
use App\Http\Controllers\Portal\PayslipController as PortalPayslipController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\Portal\TimeClockController;
use App\Http\Controllers\Reports\DashboardController as ReportsDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('verification/bulletin/{reference}', PayslipVerificationController::class)->name('verification.payslip');

Route::get('rejoindre', [OnboardingRequestController::class, 'create'])->name('onboarding.create');
Route::post('rejoindre', [OnboardingRequestController::class, 'store'])->middleware('throttle:10,1')->name('onboarding.store');
Route::get('rejoindre/merci', [OnboardingRequestController::class, 'thanks'])->name('onboarding.thanks');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])
    ->prefix('organisation')
    ->name('organisation.')
    ->group(function () {
        Route::middleware('permission:organisation.view')->group(function () {
            Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
            Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::get('positions', [PositionController::class, 'index'])->name('positions.index');
        });

        Route::middleware('permission:organisation.manage')->group(function () {
            Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
            Route::put('sites/{site}', [SiteController::class, 'update'])->name('sites.update');
            Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');

            Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
            Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

            Route::post('positions', [PositionController::class, 'store'])->name('positions.store');
            Route::put('positions/{position}', [PositionController::class, 'update'])->name('positions.update');
            Route::delete('positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
        });

        Route::middleware('permission:employees.view')->group(function () {
            Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::get('employees/{employee}/contracts/{contract}/download', ContractDownloadController::class)->name('employees.contracts.download');
            Route::get('employees/{employee}/documents/{document}/download', EmployeeDocumentDownloadController::class)->name('employees.documents.download');
            Route::get('employees/{employee}/documents/{document}/view', EmployeeDocumentViewController::class)->name('employees.documents.view');
        });

        Route::middleware('permission:employees.manage')->group(function () {
            Route::get('employees-create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

            Route::post('employees/{employee}/contracts', [ContractController::class, 'store'])->name('employees.contracts.store');
            Route::put('employees/{employee}/contracts/{contract}', [ContractController::class, 'update'])->name('employees.contracts.update');
            Route::delete('employees/{employee}/contracts/{contract}', [ContractController::class, 'destroy'])->name('employees.contracts.destroy');

            Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
            Route::delete('employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employees.documents.destroy');

            Route::post('employees/{employee}/account', [EmployeeAccountController::class, 'store'])->name('employees.account.store');
            Route::post('employees/{employee}/account/resend', [EmployeeAccountController::class, 'resend'])->name('employees.account.resend');

            Route::post('employees/{employee}/leave-requests', [EmployeeLeaveRequestController::class, 'store'])->name('employees.leave-requests.store');
            Route::delete('employees/{employee}/leave-requests/{leaveRequest}', [EmployeeLeaveRequestController::class, 'destroy'])->name('employees.leave-requests.destroy');

            Route::put('employees/{employee}/work-schedule', [EmployeeWorkScheduleController::class, 'update'])->name('employees.work-schedule.update');
            Route::post('employees/{employee}/time-entries', [EmployeeTimeEntryController::class, 'store'])->name('employees.time-entries.store');
            Route::delete('employees/{employee}/time-entries/{timeEntry}', [EmployeeTimeEntryController::class, 'destroy'])->name('employees.time-entries.destroy');

            Route::get('demandes-embauche', [EmployeeOnboardingRequestController::class, 'index'])->name('employees.onboarding-requests.index');
            Route::post('demandes-embauche/{onboardingRequest}/approve', [EmployeeOnboardingRequestController::class, 'approve'])->name('employees.onboarding-requests.approve');
            Route::post('demandes-embauche/{onboardingRequest}/reject', [EmployeeOnboardingRequestController::class, 'reject'])->name('employees.onboarding-requests.reject');
            Route::put('demandes-embauche/parametres', [OnboardingSettingsController::class, 'update'])->name('employees.onboarding-settings.update');
        });

        Route::middleware('permission:documents.manage')->group(function () {
            Route::post('employees/{employee}/document-requests', [EmployeeDocumentRequestController::class, 'store'])->name('employees.document-requests.store');
            Route::delete('employees/{employee}/document-requests/{generatedDocument}', [EmployeeDocumentRequestController::class, 'destroy'])->name('employees.document-requests.destroy');
        });

        Route::middleware('permission:payroll.manage')->group(function () {
            Route::post('employees/{employee}/pay-components', [EmployeePayComponentController::class, 'store'])->name('employees.pay-components.store');
            Route::put('employees/{employee}/pay-components/{employeePayComponent}', [EmployeePayComponentController::class, 'update'])->name('employees.pay-components.update');
            Route::delete('employees/{employee}/pay-components/{employeePayComponent}', [EmployeePayComponentController::class, 'destroy'])->name('employees.pay-components.destroy');
        });

        Route::middleware('permission:employees.anonymize')->group(function () {
            Route::post('employees/{employee}/anonymize', [EmployeeController::class, 'anonymize'])->name('employees.anonymize');
        });
    });

Route::middleware(['auth', 'verified'])
    ->prefix('conges')
    ->name('leaves.')
    ->group(function () {
        Route::middleware('permission:leaves.manage')->group(function () {
            Route::get('types', [LeaveTypeController::class, 'index'])->name('types.index');
            Route::post('types', [LeaveTypeController::class, 'store'])->name('types.store');
            Route::put('types/{leaveType}', [LeaveTypeController::class, 'update'])->name('types.update');
            Route::delete('types/{leaveType}', [LeaveTypeController::class, 'destroy'])->name('types.destroy');
        });

        // Accessible to HR (leaves.manage) and to managers reviewing their own team's
        // requests — the finer-grained scoping happens inside the controller since a
        // route-level permission can't express "only if you have direct reports".
        Route::get('demandes', [LeaveRequestController::class, 'index'])->name('requests.index');
        Route::post('demandes/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('requests.approve');
        Route::post('demandes/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('requests.reject');
    });

Route::middleware(['auth', 'verified'])
    ->prefix('presences')
    ->name('attendance.')
    ->group(function () {
        // Accessible to HR (attendance.manage) and to managers viewing their own
        // team's attendance — scoping happens inside the controller, same pattern
        // as the Congés "demandes" route above.
        Route::get('suivi', [AttendanceTimeEntryController::class, 'index'])->name('requests.index');
    });

Route::middleware(['auth', 'verified', 'has-employee-profile'])
    ->prefix('mon-espace')
    ->name('portal.')
    ->group(function () {
        Route::get('profil', [PortalProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [PortalProfileController::class, 'update'])->name('profile.update');

        Route::get('pointage', [TimeClockController::class, 'index'])->name('time-clock.index');
        Route::post('pointage/entree', [TimeClockController::class, 'clockIn'])->name('time-clock.clock-in');
        Route::post('pointage/sortie', [TimeClockController::class, 'clockOut'])->name('time-clock.clock-out');

        Route::get('conges', [PortalLeaveController::class, 'index'])->name('leaves.index');
        Route::post('conges', [PortalLeaveController::class, 'store'])->name('leaves.store');
        Route::delete('conges/{leaveRequest}', [PortalLeaveController::class, 'destroy'])->name('leaves.destroy');

        Route::get('documents', [PortalDocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}/download', [PortalDocumentController::class, 'download'])->name('documents.download');
        Route::get('documents/{document}/view', [PortalDocumentController::class, 'view'])->name('documents.view');

        Route::get('documents-a-signer/{generatedDocument}', [DocumentSignatureController::class, 'show'])->name('document-requests.show');
        Route::post('documents-a-signer/{generatedDocument}/signer', [DocumentSignatureController::class, 'sign'])->name('document-requests.sign');

        Route::post('documents/demandes', [PortalDocumentRequestController::class, 'store'])->name('document-requests.store');
        Route::delete('documents/demandes/{documentRequest}', [PortalDocumentRequestController::class, 'destroy'])->name('document-requests.destroy');

        Route::get('ma-paie', [PortalPayslipController::class, 'index'])->name('payslips.index');
        Route::get('ma-paie/{payslip}/voir', [PortalPayslipController::class, 'view'])->name('payslips.view');
        Route::get('ma-paie/{payslip}/telecharger', [PortalPayslipController::class, 'download'])->name('payslips.download');
    });

Route::middleware(['auth', 'verified', 'permission:administration.manage'])
    ->prefix('administration')
    ->name('administration.')
    ->group(function () {
        Route::get('company', [CompanySettingsController::class, 'edit'])->name('company.edit');
        Route::put('company', [CompanySettingsController::class, 'update'])->name('company.update');

        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('jours-feries', [HolidayController::class, 'index'])->name('holidays.index');
        Route::post('jours-feries', [HolidayController::class, 'store'])->name('holidays.store');
        Route::put('jours-feries/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
        Route::delete('jours-feries/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
    });

Route::middleware(['auth', 'verified', 'permission:documents.manage'])
    ->prefix('documents')
    ->name('documents.')
    ->group(function () {
        Route::get('gabarits', [DocumentTemplateController::class, 'index'])->name('templates.index');
        Route::post('gabarits', [DocumentTemplateController::class, 'store'])->name('templates.store');
        Route::put('gabarits/{documentTemplate}', [DocumentTemplateController::class, 'update'])->name('templates.update');
        Route::delete('gabarits/{documentTemplate}', [DocumentTemplateController::class, 'destroy'])->name('templates.destroy');

        Route::get('suivi', [GeneratedDocumentController::class, 'index'])->name('requests.index');

        Route::get('demandes', [DocumentRequestController::class, 'index'])->name('document-requests.index');
        Route::get('demandes/{documentRequest}', [DocumentRequestController::class, 'show'])->name('document-requests.show');
        Route::post('demandes/{documentRequest}/approve', [DocumentRequestController::class, 'approve'])->name('document-requests.approve');
        Route::post('demandes/{documentRequest}/reject', [DocumentRequestController::class, 'reject'])->name('document-requests.reject');
    });

Route::middleware(['auth', 'verified', 'permission:payroll.manage'])
    ->prefix('paie')
    ->name('payroll.')
    ->group(function () {
        Route::get('rubriques', [PayrollComponentController::class, 'index'])->name('components.index');
        Route::post('rubriques', [PayrollComponentController::class, 'store'])->name('components.store');
        Route::put('rubriques/{payrollComponent}', [PayrollComponentController::class, 'update'])->name('components.update');
        Route::delete('rubriques/{payrollComponent}', [PayrollComponentController::class, 'destroy'])->name('components.destroy');
        Route::post('rubriques/{payrollComponent}/assigner', [PayrollComponentController::class, 'bulkAssign'])->name('components.bulk-assign');

        Route::get('bulletins', [PayslipController::class, 'index'])->name('payslips.index');
        Route::post('bulletins/lancer', [PayslipController::class, 'run'])->name('payslips.run');
        Route::get('bulletins/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::post('bulletins/{payslip}/lignes', [PayslipController::class, 'addLine'])->name('payslips.lines.store');
        Route::post('bulletins/{payslip}/recalculer', [PayslipController::class, 'recalculate'])->name('payslips.recalculate');
        Route::post('bulletins/{payslip}/valider', [PayslipController::class, 'validatePayslip'])->name('payslips.validate');
        Route::delete('bulletins/{payslip}', [PayslipController::class, 'destroy'])->name('payslips.destroy');
        Route::get('bulletins/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');
    });

Route::middleware(['auth', 'verified', 'permission:reports.view'])
    ->prefix('rapports')
    ->name('reports.')
    ->group(function () {
        Route::get('/', [ReportsDashboardController::class, 'index'])->name('dashboard');
        Route::get('export/effectifs', [ReportsDashboardController::class, 'exportWorkforce'])->name('export.workforce');
        Route::get('export/paie', [ReportsDashboardController::class, 'exportPayroll'])->name('export.payroll');
        Route::get('export/conges', [ReportsDashboardController::class, 'exportLeaves'])->name('export.leaves');
    });

// Unified inbox for the configurable document approval chain (manager,
// RH, direction, payroll, requester). Eligibility is per-row and dynamic
// (see GeneratedDocumentApproval::canBeActedOnBy()), so there's no single
// permission to gate the whole group on — every action re-checks it.
Route::middleware(['auth', 'verified'])
    ->prefix('validations')
    ->name('approvals.')
    ->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('{approval}', [ApprovalController::class, 'show'])->name('show');
        Route::post('{approval}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('{approval}/reject', [ApprovalController::class, 'reject'])->name('reject');
    });

require __DIR__.'/auth.php';
