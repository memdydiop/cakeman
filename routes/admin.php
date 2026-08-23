<?php

use App\Http\Controllers\Admin\AdminPasskeyController;
use App\Models\AdminAuditLog;
use App\Services\InvoiceExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Console plateforme (schéma central `public`, Module 12)
|--------------------------------------------------------------------------
| Groupe guard `admin` — routes `routes/tenant.php` pour les boutiques.
*/

Route::post('/admin/logout', function () {
    Auth::guard('admin')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('admin.login');
})->name('admin.logout');

// Pages publiques (non authentifiées).
Route::middleware(['web', 'guest:admin'])->group(function () {
    Route::livewire('/admin/login', 'admin.login')->name('admin.login');
});

Route::livewire('/admin/invitations/{token}', 'admin.invitation-accept')->name('admin.invitations.accept');

Route::middleware('guest:admin')->group(function () {
    Route::livewire('/admin/reset-password/{token}', 'admin.password-reset')->name('admin.password.reset');
});

// Console authentifiée — RBAC via `admin.permission` (CdC § 2.3).
Route::middleware(['web', 'auth.admin', 'admin.permission:admin.tenant.view'])
    ->prefix('admin')
    ->group(function () {
        Route::livewire('/', 'admin.dashboard')->name('admin.dashboard');

        Route::livewire('/tenants', 'admin.tenants.index')->name('admin.tenants.index');

        Route::livewire('/tenants/{tenant}', 'admin.tenants.show')->name('admin.tenants.show');

        Route::livewire('/users', 'admin.users.index')
            ->middleware('admin.permission:admin.user.manage')
            ->name('admin.users.index');

        Route::livewire('/users/{user}', 'admin.users.show')
            ->middleware('admin.permission:admin.user.manage')
            ->name('admin.users.show');

        Route::livewire('/login-journal', 'admin.login-journal.index')
            ->middleware('admin.permission:admin.user.manage')
            ->name('admin.login-journal.index');

        Route::livewire('/roles', 'admin.roles.index')
            ->middleware('admin.permission:admin.role.manage')
            ->name('admin.roles.index');

        Route::livewire('/subscriptions', 'admin.subscriptions.index')
            ->middleware('admin.permission:admin.billing.manage')
            ->name('admin.subscriptions.index');

        Route::livewire('/plans', 'admin.plans.index')
            ->middleware('admin.permission:admin.billing.manage')
            ->name('admin.plans.index');

        Route::livewire('/invoices', 'admin.invoices.index')
            ->middleware('admin.permission:admin.billing.manage')
            ->name('admin.invoices.index');

        Route::livewire('/audit', 'admin.audit.index')
            ->middleware('admin.permission:admin.audit.view')
            ->name('admin.audit.index');

        Route::livewire('/support-sessions', 'admin.support-sessions.index')
            ->middleware('admin.permission:admin.support.session')
            ->name('admin.support-sessions.index');

        Route::livewire('/support-tickets', 'admin.support-tickets.index')
            ->middleware('admin.permission:admin.support.session')
            ->name('admin.support-tickets.index');

        Route::livewire('/webhooks', 'admin.webhooks.index')
            ->middleware('admin.permission:admin.webhook.replay')
            ->name('admin.webhooks.index');

        Route::livewire('/monitoring', 'admin.monitoring.index')
            ->middleware('admin.permission:admin.monitoring.view')
            ->name('admin.monitoring.index');

        Route::livewire('/leads', 'admin.leads.index')
            ->middleware('admin.permission:admin.lead.view')
            ->name('admin.leads.index');

        Route::livewire('/coupons', 'admin.coupons.index')
            ->middleware('admin.permission:admin.billing.manage')
            ->name('admin.coupons.index');

        Route::livewire('/credit-notes', 'admin.credit-notes.index')
            ->middleware('admin.permission:admin.billing.manage')
            ->name('admin.credit-notes.index');

        Route::livewire('/exports', 'admin.exports.index')
            ->middleware('admin.permission:admin.billing.manage')
            ->name('admin.exports.index');

        Route::get('/exports/invoices.csv', function (Request $request, InvoiceExportService $service) {
            abort_unless($request->user('admin')->hasPermissionTo('admin.billing.manage', 'admin'), 403);
            $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
            $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;
            AdminAuditLog::record('invoice.exported', ['from' => $from?->toDateString(), 'to' => $to?->toDateString()], null);

            return $service->toCsv($from, $to, $request->query('tenant_id'));
        })->middleware('admin.permission:admin.billing.manage')->name('admin.exports.invoices.csv');

        Route::livewire('/announcements', 'admin.announcements.index')
            ->middleware('admin.permission:admin.settings.manage')
            ->name('admin.announcements.index');

        Route::livewire('/api-tokens', 'admin.api-tokens.index')
            ->middleware('admin.permission:admin.settings.manage')
            ->name('admin.api-tokens.index');

        Route::livewire('/email-templates', 'admin.email-templates.index')
            ->middleware('admin.permission:admin.settings.manage')
            ->name('admin.email-templates.index');

        Route::livewire('/notifications', 'admin.notifications.index')
            ->middleware('admin.permission:admin.settings.manage')
            ->name('admin.notifications.index');

        Route::livewire('/settings', 'admin.settings.index')
            ->middleware('admin.permission:admin.settings.manage')
            ->name('admin.settings.index');

        Route::livewire('/security', 'admin.security.index')
            ->name('admin.security.index');

        Route::get('/passkeys/options', [AdminPasskeyController::class, 'options'])
            ->name('admin.passkeys.options');

        Route::post('/passkeys', [AdminPasskeyController::class, 'store'])
            ->name('admin.passkeys.store');

        Route::delete('/passkeys/{passkey}', [AdminPasskeyController::class, 'destroy'])
            ->name('admin.passkeys.destroy');
    });
