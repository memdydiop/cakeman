<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white ">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50  ">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Console')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="building-storefront" :href="route('admin.tenants.index')" :current="request()->routeIs('admin.tenants.*')" wire:navigate>
                        {{ __('Boutiques') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.index')" wire:navigate>
                        {{ __('Équipe') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="power" href="#" x-data @click.prevent="$el.closest('form').submit()">
                    {{ __('Se déconnecter') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        <form method="POST" action="{{ route('admin.logout') }}" class="hidden">
            @csrf
        </form>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>
    </body>
</html>