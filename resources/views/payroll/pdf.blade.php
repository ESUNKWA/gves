<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 12mm 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 10px 0;
        }

        table.employee-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.employee-info td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            width: 33.33%;
            vertical-align: top;
        }

        table.employee-info .label {
            display: block;
            font-size: 8px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .payment-line {
            margin-bottom: 10px;
            font-size: 9px;
            color: #4b5563;
        }

        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.lines th,
        table.lines td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
        }

        table.lines th {
            background: #f3f4f6;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }

        table.lines td.amount,
        table.lines th.amount {
            text-align: right;
            width: 25%;
        }

        table.lines tr.subtotal td {
            font-weight: bold;
            background: #f9fafb;
        }

        .net-box {
            margin: 12px 0;
            padding: 8px;
            border: 2px solid #1f2937;
            text-align: center;
        }

        .net-box .label {
            font-size: 9px;
            text-transform: uppercase;
        }

        .net-box .value {
            font-size: 16px;
            font-weight: bold;
        }

        .employer-charges {
            margin-bottom: 10px;
            font-size: 9px;
            color: #4b5563;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        table.leave {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.leave th,
        table.leave td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: center;
        }

        table.leave th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 16px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>

<body>
    @include('pdf.partials.header')

    <div class="title">Bulletin de paie — {{ ucfirst($payslip->periodLabel()) }}</div>

    <table class="employee-info">
        <tr>
            <td>
                <span class="label">Matricule</span>
                {{ $employee->employee_number }}
            </td>
            <td>
                <span class="label">Nom</span>
                {{ $employee->full_name }}
            </td>
            <td>
                <span class="label">Poste</span>
                {{ $employee->position?->title ?? '—' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Date d'embauche</span>
                {{ $employee->hire_date->format('d/m/Y') }}
            </td>
            <td>
                <span class="label">Département</span>
                {{ $employee->department?->name ?? '—' }}
            </td>
            <td>
                <span class="label">Site</span>
                {{ $employee->site?->name ?? '—' }}
            </td>
        </tr>
    </table>

    <p class="payment-line">
        Paiement le <strong>{{ $payslip->payment_date?->format('d/m/Y') ?? '—' }}</strong>
        par <strong>{{ $payslip->payment_method ?? '—' }}</strong>
    </p>

    <table class="lines">
        <thead>
            <tr>
                <th>Gains</th>
                <th class="amount">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslip->lines->where('type', 'gain') as $line)
                <tr>
                    <td>{{ $line->label }}</td>
                    <td class="amount">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal">
                <td>Total brut</td>
                <td class="amount">{{ number_format($payslip->gross_amount, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Retenues</th>
                <th class="amount">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payslip->lines->where('type', 'deduction') as $line)
                <tr>
                    <td>{{ $line->label }}</td>
                    <td class="amount">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Aucune retenue.</td>
                </tr>
            @endforelse
            <tr class="subtotal">
                <td>Total retenues</td>
                <td class="amount">{{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="net-box">
        <div class="label">Net à payer</div>
        <div class="value">{{ number_format($payslip->net_amount, 0, ',', ' ') }} {{ $company->currency }}</div>
    </div>

    @if ($payslip->lines->where('type', 'employer_charge')->isNotEmpty())
        <p class="employer-charges">
            Charges patronales : <strong>{{ number_format($payslip->employer_charges_amount, 0, ',', ' ') }}
                {{ $company->currency }}</strong>
            — Coût total employeur : <strong>{{ number_format($payslip->employerCost(), 0, ',', ' ') }}
                {{ $company->currency }}</strong>
        </p>
    @endif

    @if ($leave)
        <div class="section-title">Congés</div>
        <table class="leave">
            <tr>
                <th>Acquis (mois)</th>
                <th>Pris (mois)</th>
                <th>Solde restant</th>
            </tr>
            <tr>
                <td>{{ rtrim(rtrim(number_format($leave['accrued_this_month'], 2), '0'), '.') }} j.</td>
                <td>{{ rtrim(rtrim(number_format($leave['taken_this_month'], 2), '0'), '.') }} j.</td>
                <td><strong>{{ rtrim(rtrim(number_format($leave['remaining'], 2), '0'), '.') }} j.</strong></td>
            </tr>
        </table>
    @endif

    <div class="footer">
        {{ $company->name }} — {{ $company->addressLine() }}<br>
        Document confidentiel destiné exclusivement à son destinataire.
    </div>
</body>

</html>
