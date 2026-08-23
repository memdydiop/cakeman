<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white ">
        <flux:sidebar sticky collapsible="mobile" class="bg-sidebar-bg border-e border-zinc-200">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Catalogue" class="grid">
                    <flux:sidebar.item icon="tag" :href="route('tenant.categories.index')" :current="request()->routeIs('tenant.categories.*')" wire:navigate>
                        {{ __('Catégories') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cube" :href="route('tenant.products.index')" :current="request()->routeIs('tenant.products.*')" wire:navigate>
                        {{ __('Produits') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Stock" class="grid">
                    <flux:sidebar.item icon="beaker" :href="route('tenant.ingredients.index')" :current="request()->routeIs('tenant.ingredients.*')" wire:navigate>
                        {{ __('Ingrédients') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" :href="route('tenant.recipes.index')" :current="request()->routeIs('tenant.recipes.*')" wire:navigate>
                        {{ __('Recettes') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Ventes" class="grid">
                    <flux:sidebar.item icon="users" :href="route('tenant.clients.index')" :current="request()->routeIs('tenant.clients.*')" wire:navigate>
                        {{ __('Clients') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shopping-cart" :href="route('tenant.orders.index')" :current="request()->routeIs('tenant.orders.*')" wire:navigate>
                        {{ __('Commandes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="ticket" :href="route('tenant.coupons.index')" :current="request()->routeIs('tenant.coupons.*')" wire:navigate>
                        {{ __('Promos') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Facturation" class="grid">
                    <flux:sidebar.item icon="receipt-percent" :href="route('tenant.invoices.index')" :current="request()->routeIs('tenant.invoices.*')" wire:navigate>
                        {{ __('Factures') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Reporting" class="grid">
                    <flux:sidebar.item icon="chart-bar-square" :href="route('tenant.reports.index')" :current="request()->routeIs('tenant.reports.*')" wire:navigate>
                        {{ __('Rapports') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Achats" class="grid">
                    <flux:sidebar.item icon="truck" :href="route('tenant.suppliers.index')" :current="request()->routeIs('tenant.suppliers.*')" wire:navigate>
                        {{ __('Fournisseurs') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shopping-bag" :href="route('tenant.purchase-orders.index')" :current="request()->routeIs('tenant.purchase-orders.*')" wire:navigate>
                        {{ __('Commandes achat') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        @wirechartsScripts
    </body>

</html>
