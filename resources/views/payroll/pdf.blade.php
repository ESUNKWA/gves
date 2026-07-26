<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 7mm 9mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #1f2937;
            line-height: 1.2;
        }

        .top-bar {
            display: table;
            width: 100%;
            border: 1px solid #1f2937;
            margin-bottom: 4px;
        }

        .top-bar .cell {
            display: table-cell;
            padding: 4px 6px;
            vertical-align: middle;
            border-right: 1px solid #1f2937;
        }

        .top-bar .cell:last-child {
            border-right: none;
        }

        .top-bar .doc-title {
            font-size: 13px;
            font-weight: bold;
            width: 26%;
        }

        .top-bar .period {
            width: 40%;
        }

        .top-bar .payment {
            width: 34%;
            text-align: right;
        }

        table.info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.info-grid td {
            border: 1px solid #d1d5db;
            padding: 2px 5px;
            font-size: 7.5px;
        }

        table.info-grid td.label {
            background: #f3f4f6;
            color: #6b7280;
            width: 16%;
        }

        table.info-grid td.value {
            width: 17.33%;
            font-weight: bold;
        }

        .identity-block {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .identity-block .logo-col {
            display: table-cell;
            width: 22%;
            vertical-align: top;
        }

        .identity-block .logo-col img {
            max-width: 90px;
            max-height: 60px;
        }

        .identity-block .registry-col {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            padding-left: 6px;
            font-size: 7px;
            color: #4b5563;
        }

        .identity-block .name-col {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            text-align: right;
        }

        .identity-block .matricule {
            font-size: 16px;
            font-weight: bold;
            color: #7c2d12;
        }

        .identity-block .employee-name {
            font-size: 11px;
            font-weight: bold;
            margin-top: 2px;
        }

        .leave-block {
            display: table;
            width: 100%;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            margin-bottom: 4px;
        }

        .leave-block .col {
            display: table-cell;
            vertical-align: top;
            padding: 4px 6px;
        }

        .leave-block table.mini {
            width: 100%;
            border-collapse: collapse;
        }

        .leave-block table.mini th,
        .leave-block table.mini td {
            border: 1px solid #d1d5db;
            padding: 2px 4px;
            font-size: 7px;
            text-align: center;
        }

        .leave-block table.mini th {
            background: #e5e7eb;
        }

        .leave-block .rib-col {
            width: 40%;
            font-size: 7px;
        }

        .title {
            font-size: 11px;
            font-weight: bold;
            margin: 5px 0;
            text-align: center;
            text-transform: uppercase;
        }

        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.lines th,
        table.lines td {
            border: 1px solid #d1d5db;
            padding: 2px 4px;
            font-size: 7px;
        }

        table.lines th {
            background: #f3f4f6;
            text-align: left;
        }

        table.lines td.num,
        table.lines th.num {
            text-align: right;
        }

        table.lines tr.subtotal td {
            font-weight: bold;
            background: #f9fafb;
        }

        .net-box {
            margin: 5px 0;
            padding: 5px;
            border: 2px solid #1f2937;
            text-align: center;
        }

        .net-box .label {
            font-size: 8px;
            text-transform: uppercase;
        }

        .net-box .value {
            font-size: 13px;
            font-weight: bold;
        }

        table.cumuls {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.cumuls th,
        table.cumuls td {
            border: 1px solid #d1d5db;
            padding: 2px 4px;
            font-size: 7px;
            text-align: right;
        }

        table.cumuls th {
            background: #f3f4f6;
        }

        table.cumuls td.row-label,
        table.cumuls th.row-label {
            text-align: left;
            background: #f3f4f6;
            font-weight: bold;
        }

        .footer-block {
            display: table;
            width: 100%;
            margin-top: 8px;
        }

        .footer-block .col {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
        }

        .signature-box {
            margin-top: 14px;
            border-top: 1px solid #9ca3af;
            padding-top: 2px;
            font-size: 7px;
            text-align: center;
        }

        .qr-box {
            text-align: center;
        }

        .qr-box img {
            width: 42px;
            height: 42px;
        }

        .qr-box p {
            font-size: 6px;
            color: #6b7280;
            margin-top: 1px;
        }

        .reminder {
            margin-top: 6px;
            text-align: center;
            font-size: 7px;
            font-style: italic;
            color: #4b5563;
        }

        .confidentiality {
            margin-top: 4px;
            padding-top: 3px;
            border-top: 1px solid #d1d5db;
            font-size: 6px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="top-bar">
        <div class="cell doc-title">BULLETIN DE PAIE</div>
        <div class="cell period">
            Période du : <strong>{{ $payslip->period->format('d/m/y') }}</strong>
            au : <strong>{{ $payslip->period->copy()->endOfMonth()->format('d/m/y') }}</strong>
        </div>
        <div class="cell payment">
            Paiement le : <strong>{{ $payslip->payment_date?->format('d/m/y') }}</strong>
            par : <strong>{{ $payslip->payment_method }}</strong>
        </div>
    </div>

    <table class="info-grid">
        <tr>
            <td class="label">Matricule</td>
            <td class="value">{{ $employee->employee_number }}</td>
            <td class="label">Parts fiscales</td>
            <td class="value">
                {{ $employee->tax_shares ? rtrim(rtrim(number_format($employee->tax_shares, 2), '0'), '.') : '—' }}</td>
            <td class="label">N° sécurité sociale</td>
            <td class="value">{{ $employee->social_security_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Date d'embauche</td>
            <td class="value">{{ $employee->hire_date->format('d/m/Y') }}</td>
            <td class="label">Ancienneté</td>
            <td class="value">{{ $employee->seniorityLabel($payslip->period) ?? '—' }}</td>
            <td class="label">Horaire mensuel</td>
            <td class="value">{{ $employee->workSchedule?->monthlyContractualHours() ?? '—' }} h</td>
        </tr>
        <tr>
            <td class="label">Catégorie</td>
            <td class="value">{{ $employee->category ?? '—' }}</td>
            <td class="label">Qualification</td>
            <td class="value">{{ $employee->qualification ?? '—' }}</td>
            <td class="label">Convention collective</td>
            <td class="value">{{ $company->collective_agreement ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Poste</td>
            <td class="value">{{ $employee->position?->title ?? '—' }}</td>
            <td class="label">Département</td>
            <td class="value">{{ $employee->department?->name ?? '—' }}</td>
            <td class="label">Site</td>
            <td class="value">{{ $employee->site?->name ?? '—' }}</td>
        </tr>
    </table>

    <div class="identity-block">
        <div class="logo-col">
            @if ($company->logoDataUri())
                <img src="{{ $company->logoDataUri() }}">
            @endif
        </div>
        <div class="registry-col">
            <strong>{{ $company->name }}</strong><br>
            @if ($company->registration_number)
                N° RC : {{ $company->registration_number }}<br>
            @endif
            @if ($company->tax_id)
                N° contribuable : {{ $company->tax_id }}<br>
            @endif
            @if ($company->social_security_number)
                N° sécurité sociale : {{ $company->social_security_number }}
            @endif
        </div>
        <div class="name-col">
            <div class="matricule">{{ $employee->employee_number }}</div>
            <div class="employee-name">{{ $employee->full_name }}</div>
        </div>
    </div>

    <div class="leave-block">
        <div class="col" style="width: 60%;">
            @if ($leave)
                <table class="mini">
                    <tr>
                        <th>Congé</th>
                        <th>Acquis</th>
                        <th>Pris</th>
                        <th>Reste à prendre</th>
                    </tr>
                    <tr>
                        <td>Jours</td>
                        <td>{{ rtrim(rtrim(number_format($leave['accrued_this_month'], 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($leave['taken_this_month'], 2), '0'), '.') }}</td>
                        <td><strong>{{ rtrim(rtrim(number_format($leave['remaining'], 2), '0'), '.') }}</strong></td>
                    </tr>
                </table>
            @endif
        </div>
        <div class="col rib-col">
            @if ($employee->maskedBankAccount())
                RIB : {{ $employee->maskedBankAccount() }}
            @endif
        </div>
    </div>

    <div class="title">Bulletin de paie — {{ ucfirst($payslip->periodLabel()) }}</div>

    <table class="lines">
        <thead>
            <tr>
                <th>N°</th>
                <th>Désignation</th>
                <th class="num">Nombre</th>
                <th class="num">Base</th>
                <th class="num">Taux</th>
                <th class="num">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslip->lines->where('type', 'gain') as $line)
                <tr>
                    <td>{{ $line->payrollComponent?->code }}</td>
                    <td>{{ $line->label }}</td>
                    <td class="num">
                        {{ $line->quantity ? rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') : '' }}</td>
                    <td class="num">{{ $line->base_amount ? number_format($line->base_amount, 0, ',', ' ') : '' }}
                    </td>
                    <td class="num">
                        {{ $line->payrollComponent?->rate ? rtrim(rtrim(number_format($line->payrollComponent->rate, 3), '0'), '.') . '%' : '' }}
                    </td>
                    <td class="num">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="5">Total soumis à cotisations</td>
                <td class="num">{{ number_format($payslip->totalSubjectToContributions(), 0, ',', ' ') }}</td>
            </tr>
            @forelse ($payslip->lines->where('type', 'deduction') as $line)
                <tr>
                    <td>{{ $line->payrollComponent?->code }}</td>
                    <td>{{ $line->label }}</td>
                    <td class="num">
                        {{ $line->quantity ? rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') : '' }}</td>
                    <td class="num">{{ $line->base_amount ? number_format($line->base_amount, 0, ',', ' ') : '' }}
                    </td>
                    <td class="num">
                        {{ $line->payrollComponent?->rate ? rtrim(rtrim(number_format($line->payrollComponent->rate, 3), '0'), '.') . '%' : '' }}
                    </td>
                    <td class="num">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Aucune retenue.</td>
                </tr>
            @endforelse
            <tr class="subtotal">
                <td colspan="5">Total retenues</td>
                <td class="num">{{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="net-box">
        <div class="label">Net à payer</div>
        <div class="value">{{ number_format($payslip->net_amount, 0, ',', ' ') }} {{ $company->currency }}</div>
    </div>

    @if ($payslip->lines->where('type', 'employer_charge')->isNotEmpty())
        <table class="lines">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Charges patronales (non déduites du net)</th>
                    <th class="num">Taux</th>
                    <th class="num" colspan="3">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payslip->lines->where('type', 'employer_charge') as $line)
                    <tr>
                        <td>{{ $line->payrollComponent?->code }}</td>
                        <td>{{ $line->label }}</td>
                        <td class="num">
                            {{ $line->payrollComponent?->rate ? rtrim(rtrim(number_format($line->payrollComponent->rate, 3), '0'), '.') . '%' : '' }}
                        </td>
                        <td class="num" colspan="3">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="2">Coût total employeur</td>
                    <td class="num" colspan="4">{{ number_format($payslip->employerCost(), 0, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <table class="cumuls">
        <tr>
            <th class="row-label">Cumuls</th>
            <th>Salaire soumis</th>
            <th>Gain</th>
            <th>Charges salariales</th>
            <th>Charges patronales</th>
            <th>Congés restants</th>
            <th>Net</th>
        </tr>
        <tr>
            <td class="row-label">Période</td>
            <td>{{ number_format($payslip->totalSubjectToContributions(), 0, ',', ' ') }}</td>
            <td>{{ number_format($payslip->gross_amount, 0, ',', ' ') }}</td>
            <td>{{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
            <td>{{ number_format($payslip->employer_charges_amount, 0, ',', ' ') }}</td>
            <td>{{ $leave ? rtrim(rtrim(number_format($leave['remaining'], 2), '0'), '.') : '—' }}</td>
            <td>{{ number_format($payslip->net_amount, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="row-label">Année {{ $payslip->period->year }}</td>
            <td>—</td>
            <td>{{ number_format($yearToDate['gross_cumulative'], 0, ',', ' ') }}</td>
            <td>{{ number_format($yearToDate['contributions_cumulative'] + $yearToDate['tax_cumulative'], 0, ',', ' ') }}
            </td>
            <td>—</td>
            <td>{{ $leave ? rtrim(rtrim(number_format($yearToDate['leave_remaining'], 2), '0'), '.') : '—' }}</td>
            <td>{{ number_format($yearToDate['net_cumulative'], 0, ',', ' ') }}</td>
        </tr>
    </table>

    <table class="cumuls">
        <tr>
            <th class="row-label">Cotisations</th>
            <th>Cotisations sociales</th>
            <th>Impôts</th>
            <th>Primes cumulées</th>
            <th>Heures sup. cumulées</th>
        </tr>
        <tr>
            <td class="row-label">Période / Année</td>
            <td>{{ number_format($payslip->deductions_amount, 0, ',', ' ') }} /
                {{ number_format($yearToDate['contributions_cumulative'], 0, ',', ' ') }}</td>
            <td>0 / {{ number_format($yearToDate['tax_cumulative'], 0, ',', ' ') }}</td>
            <td>{{ number_format($yearToDate['bonus_cumulative'], 0, ',', ' ') }}</td>
            <td>{{ $attendance['overtime_hours'] }} h / {{ $yearToDate['overtime_hours_cumulative'] }} h</td>
        </tr>
    </table>

    <div class="reminder">Pour vous aider à faire valoir vos droits, conservez ce bulletin de paie sans limitation de
        durée.</div>

    <div class="footer-block">
        <div class="col">
            <div class="signature-box">Signature / cachet employeur</div>
        </div>
        <div class="col">
            <div class="signature-box">Signature employé</div>
        </div>
        <div class="col">
            <div class="qr-box">
                <img src="{{ $payslip->qrCodeDataUri() }}">
                <p>Scanner pour vérifier l'authenticité — N° {{ $payslip->reference }}</p>
            </div>
        </div>
    </div>

    <div class="confidentiality">
        {{ $company->name }} — {{ $company->addressLine() }}<br>
        Document confidentiel destiné exclusivement à son destinataire. Toute diffusion sans autorisation est interdite.
    </div>
</body>

</html>
