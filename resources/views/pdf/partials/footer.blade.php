<style>
    .company-footer {
        margin-top: 16px;
        padding-top: 6px;
        border-top: 1px solid #d1d5db;
        font-size: 8px;
        color: #9ca3af;
        text-align: center;
    }
</style>

<div class="company-footer">
    @isset($hash)
        Empreinte d'intégrité du document (SHA-256) : {{ $hash }}<br>
    @endisset
    {{ $company->name }} — {{ $company->addressLine() }}<br>
    Document confidentiel destiné exclusivement à son destinataire.
</div>
