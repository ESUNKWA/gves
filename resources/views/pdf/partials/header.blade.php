<style>
    .company-header {
        display: table;
        width: 100%;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 2px solid #1f2937;
    }

    .company-header .logo-cell {
        display: table-cell;
        width: 44px;
        vertical-align: middle;
    }

    .company-header .logo-cell img {
        max-width: 38px;
        max-height: 38px;
    }

    .company-header .name-cell {
        display: table-cell;
        vertical-align: middle;
        padding-left: 8px;
        font-size: 10px;
    }

    .company-header .name-cell strong {
        font-size: 12px;
    }

    .company-header .details-cell {
        display: table-cell;
        vertical-align: middle;
        text-align: right;
        font-size: 8px;
        color: #6b7280;
    }
</style>

<div class="company-header">
    @if ($company->logoDataUri())
        <div class="logo-cell">
            <img src="{{ $company->logoDataUri() }}">
        </div>
    @endif
    <div class="name-cell">
        <strong>{{ $company->name }}</strong>
        @if ($company->legal_name && $company->legal_name !== $company->name)
            <br>{{ $company->legal_name }}
        @endif
        @if ($company->registration_number)
            <br><span style="font-size: 8px; color: #6b7280;">RCCM/Immatriculation :
                {{ $company->registration_number }}</span>
        @endif
    </div>
    <div class="details-cell">
        @if ($company->addressLine())
            {{ $company->addressLine() }}<br>
        @endif
        @if ($company->phone)
            {{ $company->phone }}<br>
        @endif
        @if ($company->email)
            {{ $company->email }}
        @endif
    </div>
</div>
