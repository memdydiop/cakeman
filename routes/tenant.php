<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureTenantActive;
use App\Models\Announcement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
| Boutiques : initialisation du tenancy par sous-domaine, accès bloqué hors
| suspension (`EnsureTenantActive`, CdC § 5.1). Les routes Fortify (login,
| reset, 2FA…) sont enregistrées séparément par le starter kit et restent
| accessibles sur les sous-domaines.
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Activation d'une boutique en attente : accessible sans compte et sans
    // `EnsureTenantActive` (le tenant est encore `pending`).
    Route::middleware('guest')->group(function () {
        Route::livewire('/tenant-invitations/{token}', 'onboarding.invitation-accept')->name('tenant-invitations.accept');

        Route::view('/activation-sent', 'activation-sent')->name('activation.sent');
    });

    Route::get('/impersonate/{token}', function (string $token) {
        /** @var array{tenant_id: string, user_id: int, admin_id: int}|null $data */
        $data = Cache::pull('impersonate:'.$token);

        abort_unless($data !== null && $data['tenant_id'] === tenant('id'), 404);

        $user = User::query()->findOrFail($data['user_id']);

        Auth::login($user);

        session(['impersonated' => true, 'impersonated_admin_id' => $data['admin_id']]);

        return redirect()->route('dashboard');
    })->name('tenant.impersonate');

    Route::post('/impersonation/leave', function () {
        Auth::logout();
        session()->forget(['impersonated', 'impersonated_admin_id']);

        return redirect()->away('http://'.config('tenancy.root_domain').'/admin/tenants');
    })->middleware('auth')->name('tenant.impersonation.leave');
});

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    EnsureTenantActive::class,
])->group(function () {
    // Pas de route `/` côté tenant au S0 : la racine d'un sous-domaine tenant
    // redirige vers le tableau de bord (`web.php` → `home` + tenancy), le
    // point d'entrée de la boutique.

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');

        Route::livewire('/categories', 'tenant.categories.index')->name('tenant.categories.index');
        Route::livewire('/products', 'tenant.products.index')->name('tenant.products.index');
        Route::livewire('/ingredients', 'tenant.ingredients.index')->name('tenant.ingredients.index');
        Route::livewire('/recipes', 'tenant.recipes.index')->name('tenant.recipes.index');
        Route::livewire('/clients', 'tenant.clients.index')->name('tenant.clients.index');
        Route::livewire('/orders', 'tenant.orders.index')->name('tenant.orders.index');
        Route::livewire('/invoices', 'tenant.invoices.index')->name('tenant.invoices.index');
        Route::livewire('/suppliers', 'tenant.suppliers.index')->name('tenant.suppliers.index');
        Route::livewire('/purchase-orders', 'tenant.purchase-orders.index')->name('tenant.purchase-orders.index');
        Route::livewire('/reports', 'tenant.reports.index')->name('tenant.reports.index');
        Route::livewire('/coupons', 'tenant.coupons.index')->name('tenant.coupons.index');

        Route::get('/orders/{order}/ticket', function (Order $order) {
            $order->load(['client', 'items.product']);

            return view('tenant.orders.ticket', ['order' => $order]);
        })->name('tenant.orders.ticket');

        Route::livewire('/orders/{order}', 'tenant.orders.show')->name('tenant.orders.show');

        Route::post('/announcements/{announcement}/ack', function (Announcement $announcement) {
            $announcementId = $announcement->id;

            DB::table('announcement_acks')->updateOrInsert(
                ['user_id' => auth()->id(), 'announcement_id' => $announcementId],
                ['acked_at' => now()],
            );

            return back();
        })->name('announcements.ack');
    });

    require __DIR__.'/settings.php';
});
