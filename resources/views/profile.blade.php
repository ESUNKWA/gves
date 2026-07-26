<x-app-layout>
    <x-page-header :title="__('Profil')" :description="__('Gérez vos informations de compte et votre sécurité.')" />

    <div class="max-w-2xl space-y-6">
        <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6 sm:p-8">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6 sm:p-8">
            <livewire:profile.update-password-form />
        </div>

        <div class="rounded-xl border border-red-200 bg-surface p-6 dark:border-red-900/40 sm:p-8">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
