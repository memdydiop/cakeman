<flux:dropdown position="bottom" align="start" offset="16">
    <flux:sidebar.profile
        :name="auth('admin')->user()->name"
        :initials="auth('admin')->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="admin-sidebar-menu-button"
        class="h-12! pl-2! flex items-center bg-white/5 hover:bg-white/5!"      
    />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :name="auth('admin')->user()->name"
                :initials="auth('admin')->user()->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth('admin')->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth('admin')->user()->email }}</flux:text>
            </div>
        </div>

        <flux:menu.separator />

        <flux:menu.item icon="lock-closed" :href="route('admin.security.index')" wire:navigate>
            {{ __('Sécurité') }}
        </flux:menu.item>

        <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
            @csrf
            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer"
                data-test="admin-logout-button"
            >
                {{ __('Se déconnecter') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
