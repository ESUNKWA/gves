<x-app-layout>
    <x-page-header :title="__('Ma paie')" :description="__('Historique de vos bulletins de salaire.')" />

    <div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Période
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Brut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Retenues
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Net</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($payslips as $payslip)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ ucfirst($payslip->periodLabel()) }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ number_format($payslip->gross_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            {{ number_format($payslip->net_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('portal.payslips.pdf', $payslip) }}" target="_blank"
                                class="text-brand hover:underline">Télécharger le PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucun bulletin disponible
                            pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
