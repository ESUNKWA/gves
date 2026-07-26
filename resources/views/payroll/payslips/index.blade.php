<x-app-layout>
    <x-page-header :title="__('Bulletins de paie')" :description="__('Générez et suivez la paie mensuelle.')">
        <x-slot:actions>
            <form method="POST" action="{{ route('payroll.payslips.run') }}" class="flex items-center gap-2"
                onsubmit="return confirm('Lancer la paie pour tous les employés actifs ayant une structure de rémunération ?');">
                @csrf
                <input type="hidden" name="period" value="{{ $period->format('Y-m') }}">
                <x-primary-button
                    type="submit">{{ __('Lancer la paie de ' . $period->translatedFormat('F Y')) }}</x-primary-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-6 p-3 rounded-lg bg-success-soft text-success text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="GET" action="{{ route('payroll.payslips.index') }}" class="mb-6 flex flex-wrap gap-3">
        <input type="month" name="period" value="{{ $period->format('Y-m') }}" onchange="this.form.submit()"
            class="rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
        <x-select name="status" onchange="this.form.submit()" class="max-w-xs">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
    </form>

    <div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Employé
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Brut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Retenues
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Net</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut
                    </th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($payslips as $payslip)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $payslip->employee->full_name }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ number_format($payslip->gross_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            {{ number_format($payslip->net_amount, 0, ',', ' ') }}</td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$payslip->status === 'validated' ? 'success' : 'warning'">
                                {{ $statuses[$payslip->status] ?? $payslip->status }}
                            </x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('payroll.payslips.show', $payslip) }}"
                                class="text-brand hover:underline">Voir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">
                            Aucun bulletin pour {{ $period->translatedFormat('F Y') }}. Cliquez sur "Lancer la paie"
                            pour en générer.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
