<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white ">
    <flux:sidebar sticky collapsible="mobile" class="pt-0! pb-4! px-2.5! gap-0! bg-sidebar-bg border-e border-white/10">
        
        <flux:sidebar.header class="py-0 px-2 h-15! flex items-center ">
            <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <div data-simplebar class="px-0 overflow-auto h-[calc(100vh-theme(spacing.32))]">
            <flux:sidebar.nav>
                @php
                    $pendingTenants = \Illuminate\Support\Facades\Cache::remember(
                        'admin.sidebar.pending_tenants',
                        60,
                        fn () => \App\Models\Tenant::where('status', \App\Enums\TenantStatus::Pending->value)->count(),
                    );
                    $failedJobs = \Illuminate\Support\Facades\Cache::remember(
                        'admin.sidebar.failed_jobs',
                        60,
                        fn () => \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
                    );
                    $newLeads = \Illuminate\Support\Facades\Cache::remember(
                        'admin.sidebar.new_leads',
                        60,
                        fn () => \App\Models\Lead::where('status', \App\Enums\LeadStatus::New->value)->count(),
                    );
                @endphp

                <flux:sidebar.group heading="Console" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')"
                        :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="building-storefront" :href="route('admin.tenants.index')"
                        :current="request()->routeIs('admin.tenants.*')"
                        :badge="$pendingTenants > 0 ? $pendingTenants : null" badge:color="amber" wire:navigate>
                        {{ __('Boutiques') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users" :href="route('admin.users.index')"
                        :current="request()->routeIs('admin.users.index')" wire:navigate>
                        {{ __('Équipe') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="shield-check" :href="route('admin.roles.index')"
                        :current="request()->routeIs('admin.roles.*')" wire:navigate>
                        {{ __('Rôles') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Facturation" class="grid">
                    <flux:sidebar.item icon="receipt-percent" :href="route('admin.invoices.index')"
                        :current="request()->routeIs('admin.invoices.*')" wire:navigate>
                        {{ __('Factures') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="credit-card" :href="route('admin.subscriptions.index')"
                        :current="request()->routeIs('admin.subscriptions.*')" wire:navigate>
                        {{ __('Abonnements') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="tag" :href="route('admin.plans.index')"
                        :current="request()->routeIs('admin.plans.*')" wire:navigate>
                        {{ __('Plans & add-ons') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="ticket" :href="route('admin.coupons.index')"
                        :current="request()->routeIs('admin.coupons.*')" wire:navigate>
                        {{ __('Coupons') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="receipt-refund" :href="route('admin.credit-notes.index')"
                        :current="request()->routeIs('admin.credit-notes.*')" wire:navigate>
                        {{ __('Avoirs') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="arrow-down-tray" :href="route('admin.exports.index')"
                        :current="request()->routeIs('admin.exports.*')" wire:navigate>
                        {{ __('Exports') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Supervision" class="grid">
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.audit.index')"
                        :current="request()->routeIs('admin.audit.*')" wire:navigate>
                        {{ __('Audit') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="finger-print" :href="route('admin.login-journal.index')"
                        :current="request()->routeIs('admin.login-journal.*')" wire:navigate>
                        {{ __('Connexions') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="user-circle" :href="route('admin.support-sessions.index')"
                        :current="request()->routeIs('admin.support-sessions.*')" wire:navigate>
                        {{ __('Sessions support') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="lifebuoy" :href="route('admin.support-tickets.index')"
                        :current="request()->routeIs('admin.support-tickets.*')" wire:navigate>
                        {{ __('Tickets') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="arrow-path" :href="route('admin.webhooks.index')"
                        :current="request()->routeIs('admin.webhooks.*')" wire:navigate>
                        {{ __('Webhooks') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="queue-list" :href="route('admin.monitoring.index')"
                        :current="request()->routeIs('admin.monitoring.*')"
                        :badge="$failedJobs > 0 ? $failedJobs : null" badge:color="red" wire:navigate>
                        {{ __('Monitoring') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Acquisition" class="grid">
                    <flux:sidebar.item icon="megaphone" :href="route('admin.leads.index')"
                        :current="request()->routeIs('admin.leads.*')" :badge="$newLeads > 0 ? $newLeads : null"
                        badge:color="blue" wire:navigate>
                        {{ __('Leads') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Paramètres" class="grid">
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings.index')"
                        :current="request()->routeIs('admin.settings.*')" wire:navigate>
                        {{ __('Configuration') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="lock-closed" :href="route('admin.security.index')"
                        :current="request()->routeIs('admin.security.index')" wire:navigate>
                        {{ __('Sécurité') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="speaker-wave" :href="route('admin.announcements.index')"
                        :current="request()->routeIs('admin.announcements.*')" wire:navigate>
                        {{ __('Annonces') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="key" :href="route('admin.api-tokens.index')"
                        :current="request()->routeIs('admin.api-tokens.*')" wire:navigate>
                        {{ __('Clés API') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="envelope" :href="route('admin.email-templates.index')"
                        :current="request()->routeIs('admin.email-templates.*')" wire:navigate>
                        {{ __('Templates email') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="bell" :href="route('admin.notifications.index')"
                        :current="request()->routeIs('admin.notifications.*')" wire:navigate>
                        {{ __('Notifications') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
        </div>

        <flux:spacer />

        <x-admin-desktop-user-menu class="hidden lg:block border-t " />

    </flux:sidebar>

    <flux:header class="bg-sidebar-bg text-white lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth('admin')->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth('admin')->user()->name"
                                :initials="auth('admin')->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth('admin')->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth('admin')->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="admin-mobile-logout-button">
                        {{ __('Se déconnecter') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    <x-admin-footer />

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

        @fluxScripts
        @wirechartsScripts
    </body>

</html>
