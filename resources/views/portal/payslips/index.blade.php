<x-app-layout>
    <x-page-header :title="__('Ma paie')" :description="__('Historique de vos bulletins de salaire.')" />

    <x-portal-tabs />

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Période</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Brut</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Retenues</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Net</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($payslips as $payslip)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg"
                            data-sort-value="{{ $payslip->period->timestamp }}">
                            {{ ucfirst($payslip->periodLabel()) }}</td>
                        <td class="px-6 py-4 text-sm text-muted" data-sort-value="{{ $payslip->gross_amount }}">
                            {{ number_format($payslip->gross_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-sm text-muted" data-sort-value="{{ $payslip->deductions_amount }}">
                            {{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-fg" data-sort-value="{{ $payslip->net_amount }}">
                            {{ number_format($payslip->net_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'view-payslip-{{ $payslip->id }}')"
                                class="text-brand hover:underline">Voir</button>
                            <a href="{{ route('portal.payslips.download', $payslip) }}"
                                class="text-brand hover:underline">Télécharger</a>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucun bulletin disponible
                            pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    @foreach ($payslips as $payslip)
        <x-modal name="view-payslip-{{ $payslip->id }}" maxWidth="4xl">
            <div class="p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-medium text-fg">
                        {{ __('Bulletin — :period', ['period' => ucfirst($payslip->periodLabel())]) }}</h2>
                    <button type="button" x-on:click="$dispatch('close')" class="text-muted hover:text-fg">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <iframe :src="show ? '{{ route('portal.payslips.view', $payslip) }}#toolbar=0&navpanes=0' : ''"
                    class="w-full rounded-lg border border-line-soft bg-surface-2" style="height: 75vh;"></iframe>
            </div>
        </x-modal>
    @endforeach
</x-app-layout>
