<?php

use App\Enums\AdminPermission;
use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Mail\ManagerInvitationMail;
use App\Models\AdminAuditLog;
use App\Models\AdminSupportSession;
use App\Models\AdminUser;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantUserInvitation;
use App\Models\User;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\PlansSeeder;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
    (new PlansSeeder)->run();
});

test('admin console requires authentication', function () {
    $this->get(route('admin.tenants.index'))->assertRedirect(route('admin.login'));
});

test('super admin can access the tenants list', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.index')
        ->assertOk()
        ->assertSee('$flux.modal')
        ->assertSee('create-tenant')
        ->dispatch('tenant-created')
        ->assertHasNoErrors();
});

test('tenants list can be filtered by status', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $this->createTenant(['name' => 'Pâtisserie Active']);
    $this->createTenant(['name' => 'Boutique En attente', 'status' => TenantStatus::Pending]);
    $this->createTenant(['name' => 'Boutique Suspendue', 'status' => TenantStatus::Suspended]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.index')
        ->assertSee('Pâtisserie Active')
        ->assertSee('Boutique En attente')
        ->set('filters.status', 'pending')
        ->assertSee('Boutique En attente')
        ->assertDontSee('Pâtisserie Active')
        ->assertDontSee('Boutique Suspendue');
});

test('tenants list page size can be changed', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.index')
        ->assertSet('perPage', 10)
        ->set('perPage', 25)
        ->assertSet('perPage', 25)
        ->assertHasNoErrors();
});

test('support can view tenants but cannot manage them', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    expect($support->hasPermissionTo(AdminPermission::TenantView->value, 'admin'))->toBeTrue();
    expect($support->hasPermissionTo(AdminPermission::TenantManage->value, 'admin'))->toBeFalse();

    $tenant = $this->createTenant();

    Livewire::actingAs($support, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('toggleStatus')
        ->assertForbidden();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});

test('support cannot access the team page', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.users.index')
        ->assertForbidden();
});

test('super admin can create a role with permissions', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.roles.index')
        ->assertOk()
        ->assertSee('Super-Admin')
        ->assertSee('$flux.modal')
        ->set('name', 'Auditeur')
        ->set('permissions', [AdminPermission::AuditView->value])
        ->call('createRole')
        ->assertHasNoErrors()
        ->assertDispatched('modal-close', name: 'create-role');

    $role = Role::where('name', 'Auditeur')->where('guard_name', 'admin')->firstOrFail();

    expect($role->hasPermissionTo(AdminPermission::AuditView->value))->toBeTrue();
});

test('super admin can edit role permissions', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $support = Role::where('name', AdminRolesSeeder::ROLE_SUPPORT)->firstOrFail();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.roles.index')
        ->call('startEditing', $support->id)
        ->set('editName', 'Support Étendu')
        ->set('editPermissions', [AdminPermission::TenantView->value, AdminPermission::SupportSession->value])
        ->call('saveEditing')
        ->assertHasNoErrors();

    $support->refresh();

    expect($support->name)->toBe('Support Étendu')
        ->and($support->permissions->pluck('name')->sort()->values()->all())
        ->toBe([AdminPermission::SupportSession->value, AdminPermission::TenantView->value]);
});

test('super admin role cannot be deleted', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $superAdmin = Role::where('name', AdminRolesSeeder::ROLE_SUPER_ADMIN)->firstOrFail();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.roles.index')
        ->call('deleteRole', $superAdmin->id)
        ->assertHasErrors(['role']);

    expect(Role::where('name', AdminRolesSeeder::ROLE_SUPER_ADMIN)->exists())->toBeTrue();
});

test('support cannot access the roles page', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.roles.index')
        ->assertForbidden();
});

test('manual tenant creation from the console works and is audited', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.create')
        ->set('name', 'Boutique Manuelle')
        ->set('subdomain', 'manuelle')
        ->set('managerName', 'Aya Kouassi')
        ->set('managerEmail', 'aya@manuelle.ci')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('tenant-created')
        ->assertDispatched('modal-close', name: 'create-tenant');

    $tenant = Tenant::where('name', 'Boutique Manuelle')->firstOrFail();

    expect($tenant->status)->toBe(TenantStatus::Pending);

    expect($tenant->domains->pluck('domain'))->toContain('manuelle.cakeman.test');

    $tenant->run(function () {
        $invitation = TenantUserInvitation::where('email', 'aya@manuelle.ci')->firstOrFail();

        expect($invitation->name)->toBe('Aya Kouassi')
            ->and($invitation->isValid())->toBeTrue();
    });

    Mail::assertQueued(ManagerInvitationMail::class, function (ManagerInvitationMail $mail): bool {
        return $mail->managerName === 'Aya Kouassi'
            && $mail->shopName === 'Boutique Manuelle'
            && str_contains($mail->acceptUrl, '/tenant-invitations/')
            && $mail->hasTo('aya@manuelle.ci');
    });

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.created',
        'tenant_id' => $tenant->id,
    ]);
});

test('duplicate subdomain is rejected in the console', function () {
    $this->createTenant(domain: 'manuelle.cakeman.test');

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.create')
        ->set('name', 'Doublon')
        ->set('subdomain', 'manuelle')
        ->set('managerName', 'Aya Kouassi')
        ->set('managerEmail', 'aya@manuelle.ci')
        ->call('create')
        ->assertHasErrors(['subdomain']);

    expect(Tenant::where('name', 'Doublon')->exists())->toBeFalse();
});

test('duplicate manager email is rejected in the console', function () {
    $tenant = $this->createTenant(domain: 'premiere.cakeman.test');

    $tenant->run(function () {
        User::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'password' => 'mot-de-passe',
        ]);
    });

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.create')
        ->set('name', 'Doublon E-mail')
        ->set('subdomain', 'doublon-email')
        ->set('managerName', 'Aya Kouassi')
        ->set('managerEmail', 'aya@manuelle.ci')
        ->call('create')
        ->assertHasErrors(['managerEmail']);

    expect(Tenant::where('name', 'Doublon E-mail')->exists())->toBeFalse();
});

test('pending tenant invitation can be resent from the show page', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->subHour(),
        ]);

        $token = $invitation->token;
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('resendInvitation')
        ->assertHasNoErrors();

    $tenant->run(function () use ($token) {
        $invitation = TenantUserInvitation::firstOrFail();

        expect($invitation->token)->not->toBe($token)
            ->and($invitation->isValid())->toBeTrue();
    });

    Mail::assertQueued(ManagerInvitationMail::class, function (ManagerInvitationMail $mail): bool {
        return $mail->hasTo('aya@manuelle.ci')
            && str_contains($mail->acceptUrl, '/tenant-invitations/');
    });

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.invitation_resent',
        'tenant_id' => $tenant->id,
    ]);
});

test('resending invitation fails when no invitation is pending', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('resendInvitation')
        ->assertHasErrors(['invitation']);
});

test('show page lists both consumed and pending invitations', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $tenant->run(function () {
        TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
            'accepted_at' => now()->subDay(),
        ]);

        TenantUserInvitation::create([
            'name' => 'Koffi Brou',
            'email' => 'koffi@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->assertSee('aya@manuelle.ci')
        ->assertSee('koffi@manuelle.ci')
        ->assertSee('Consommée le')
        ->assertSeeHtml('wire:click="resendInvitation')
        ->assertSeeHtml('wire:click="revokeInvitation')
        ->assertSeeHtml('title="Renvoyer"')
        ->assertSeeHtml('title="Révoquer"');
});

test('resending a specific pending invitation regenerates its link', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $pendingId = null;
    $consumedId = null;
    $pendingToken = null;

    $tenant->run(function () use (&$pendingId, &$consumedId, &$pendingToken) {
        $consumed = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
            'accepted_at' => now()->subDay(),
        ]);

        $consumedId = $consumed->id;

        $pending = TenantUserInvitation::create([
            'name' => 'Koffi Brou',
            'email' => 'koffi@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        $pendingId = $pending->id;
        $pendingToken = $pending->token;
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('resendInvitation', $pendingId)
        ->assertHasNoErrors();

    $tenant->run(function () use ($pendingId, $consumedId, $pendingToken) {
        $pending = TenantUserInvitation::findOrFail($pendingId);

        expect($pending->token)->not->toBe($pendingToken)
            ->and($pending->isValid())->toBeTrue()
            ->and(TenantUserInvitation::findOrFail($consumedId)->accepted_at)->not->toBeNull();
    });

    Mail::assertQueued(ManagerInvitationMail::class, fn (ManagerInvitationMail $mail): bool => $mail->hasTo('koffi@manuelle.ci'));
});

test('revoking a pending invitation deletes it and the inactive account', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $pendingId = null;
    $tenant->run(function () use (&$pendingId) {
        TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        $pendingId = TenantUserInvitation::create([
            'name' => 'Koffi Brou',
            'email' => 'koffi@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ])->id;

        User::create([
            'name' => 'Koffi Brou',
            'email' => 'koffi@manuelle.ci',
            'password' => Str::password(24),
            'is_active' => false,
        ]);

        User::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'password' => Str::password(24),
            'is_active' => true,
        ]);
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('revokeInvitation', $pendingId)
        ->assertHasNoErrors();

    $tenant->run(function () use ($pendingId) {
        expect(TenantUserInvitation::find($pendingId))->toBeNull()
            ->and(User::where('email', 'koffi@manuelle.ci')->where('is_active', false)->exists())->toBeFalse()
            ->and(User::where('email', 'aya@manuelle.ci')->exists())->toBeTrue();
    });

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.invitation_revoked',
        'tenant_id' => $tenant->id,
    ]);
});

test('a consumed invitation cannot be revoked from the show page', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $consumedId = null;
    $tenant->run(function () use (&$consumedId) {
        $consumedId = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
            'accepted_at' => now()->subDay(),
        ])->id;
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('revokeInvitation', $consumedId)
        ->assertStatus(404);

    $tenant->run(function () use ($consumedId) {
        expect(TenantUserInvitation::find($consumedId))->not->toBeNull();
    });
});

test('manager invitation can be sent to a tenant without one', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('inviteManagerName', 'Aya Kouassi')
        ->set('inviteManagerEmail', 'aya@manuelle.ci')
        ->call('inviteManager')
        ->assertHasNoErrors()
        ->assertDispatched('modal-close', name: 'invite-manager');

    $tenant->run(function () {
        $invitation = TenantUserInvitation::firstOrFail();

        expect($invitation->name)->toBe('Aya Kouassi')
            ->and($invitation->email)->toBe('aya@manuelle.ci')
            ->and($invitation->isValid())->toBeTrue();
    });

    Mail::assertQueued(ManagerInvitationMail::class, function (ManagerInvitationMail $mail): bool {
        return $mail->hasTo('aya@manuelle.ci')
            && str_contains($mail->acceptUrl, '/tenant-invitations/');
    });

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.invitation_sent',
        'tenant_id' => $tenant->id,
    ]);
});

test('inviting a manager replaces the pending invitation', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $tenant->run(function () {
        TenantUserInvitation::create([
            'name' => 'Ancien Gérant',
            'email' => 'ancien@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('inviteManagerName', 'Aya Kouassi')
        ->set('inviteManagerEmail', 'aya@manuelle.ci')
        ->call('inviteManager')
        ->assertHasNoErrors();

    $tenant->run(function () {
        expect(TenantUserInvitation::count())->toBe(1)
            ->and(TenantUserInvitation::first()->email)->toBe('aya@manuelle.ci');
    });
});

test('duplicate manager email is rejected on the show page', function () {
    $otherTenant = $this->createTenant();

    $otherTenant->run(function () {
        User::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'password' => 'mot-de-passe',
        ]);
    });

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('inviteManagerName', 'Aya Kouassi')
        ->set('inviteManagerEmail', 'aya@manuelle.ci')
        ->call('inviteManager')
        ->assertHasErrors(['inviteManagerEmail']);
});

test('super admin can delete a cancelled tenant from the show page', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['name' => 'À supprimer', 'status' => TenantStatus::Cancelled]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('deleteTenant')
        ->assertRedirect(route('admin.tenants.index'));

    expect(Tenant::find($tenant->id))->toBeNull();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.deleted',
        'tenant_id' => null,
    ]);
});

test('deleting a tenant that is not cancelled is refused', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['name' => 'À ne pas supprimer']);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('deleteTenant')
        ->assertStatus(404);

    expect(Tenant::find($tenant->id))->not->toBeNull();
});

test('super admin can extend the trial of a trialing tenant', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Trialing]);
    $plan = Plan::where('code', 'starter')->firstOrFail();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => now()->addDays(7),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('extendTrial')
        ->assertHasNoErrors();

    expect($subscription->fresh()->trial_ends_at->toDateString())->toBe(now()->addDays(21)->toDateString());

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.trial_extended',
        'tenant_id' => $tenant->id,
    ]);
});

test('extending a trial requires a trialing subscription', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'starter')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('extendTrial')
        ->assertStatus(404);
});

test('super admin can cancel and archive a tenant', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['name' => 'À archiver']);
    $plan = Plan::where('code', 'starter')->firstOrFail();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('cancelTenant')
        ->assertHasNoErrors();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Cancelled);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
    expect($subscription->fresh()->cancelled_at)->not->toBeNull();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.cancelled',
        'tenant_id' => $tenant->id,
    ]);
});

test('cancelling an already cancelled tenant is refused', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Cancelled]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('cancelTenant')
        ->assertStatus(404);
});

test('billing cannot extend a trial or cancel a tenant', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $tenant = $this->createTenant(['status' => TenantStatus::Trialing]);
    $plan = Plan::where('code', 'starter')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => now()->addDays(7),
    ]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('extendTrial')
        ->assertForbidden();

    Livewire::actingAs($billing, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('cancelTenant')
        ->assertForbidden();
});

test('card header renders its actions in a dropdown', function () {
    $html = view('components.card-header', [
        'title' => 'Titre de carte',
        'subtitle' => 'Sous-titre',
        'actions' => [
            ['label' => 'Tout voir', 'href' => '/tenants', 'icon' => 'arrow-right-start-on-rectangle'],
            ['label' => 'Inviter', 'href' => '/users', 'icon' => 'plus', 'wire:click' => 'open'],
        ],
    ])->render();

    expect($html)
        ->toContain('Titre de carte')
        ->toContain('Sous-titre')
        ->toContain('data-flux-button')
        ->toContain('Tout voir')
        ->toContain('href="/tenants"')
        ->toContain('Inviter')
        ->toContain('href="/users"')
        ->toContain('wire:click="open"');
});

test('admin sidebar renders console nav, user menu and logout forms', function () {
    $admin = AdminUser::factory()->create(['name' => 'Aya Kouassi']);

    $this->actingAs($admin, 'admin');

    $html = view('layouts.admin.sidebar', ['slot' => ''])->render();

    expect($html)
        ->toContain('Console')
        ->toContain(route('admin.dashboard'))
        ->toContain(route('admin.tenants.index'))
        ->toContain(route('admin.users.index'))
        ->toContain(route('admin.roles.index'))
        ->toContain($admin->name)
        ->toContain($admin->email)
        ->toContain('method="POST" action="'.route('admin.logout').'"')
        ->toContain('Se déconnecter')
        ->toContain('data-test="admin-sidebar-menu-button"')
        ->toContain('data-test="admin-mobile-logout-button"');
});

test('page header renders dynamic home link and breadcrumbs', function () {
    $html = view('components.page-header', [
        'title' => 'Boutique',
        'subtitle' => 'douceur.cakeman.test',
        'breadcrumbs' => [
            ['label' => 'Boutiques', 'href' => route('admin.tenants.index'), 'wire_navigate' => true],
            ['label' => 'Pâtisserie Douceur'],
        ],
    ])->render();

    expect($html)
        ->toContain('Boutique')
        ->toContain('douceur.cakeman.test')
        ->toContain('href="'.route('admin.dashboard').'"')
        ->toContain('href="'.route('admin.tenants.index').'"')
        ->toContain('Boutiques')
        ->toContain('Pâtisserie Douceur')
        ->toContain('wire:navigate');
});

test('table toolbar renders search, filters, page size and actions slots', function () {
    view()->share('errors', new ViewErrorBag);

    $html = view('components.table-toolbar', [
        'search' => 'search',
        'searchPlaceholder' => 'Rechercher une boutique…',
        'perPage' => 'perPage',
    ])
        ->with('filters', new HtmlString('<select name="status"></select>'))
        ->with('actions', new HtmlString('<button type="button">Créer</button>'))
        ->render();

    expect($html)
        ->toContain('wire:model.live.debounce.300ms="search"')
        ->toContain('placeholder="Rechercher une boutique…"')
        ->toContain('<select name="status"></select>')
        ->toContain('wire:model.live="perPage"')
        ->toContain('value="25"')
        ->toContain('<button type="button">Créer</button>');
});

test('card body renders its slot with overridable spacing', function () {
    $html = view('components.card-body', [])->with('slot', new HtmlString('<p>Contenu de la carte</p>'))->render();

    expect($html)
        ->toContain('<p>Contenu de la carte</p>')
        ->toContain('class="p-4"');

    $spaced = view('components.card-body', [])->with('slot', 'Contenu')->with('attributes', new ComponentAttributeBag(['class' => 'mt-4']))->render();

    expect($spaced)
        ->toContain('Contenu')
        ->toContain('class="p-4 mt-4"');
});

test('dashboard renders platform metrics and recent activity', function () {
    $admin = AdminUser::factory()->create(['name' => 'Ariane Dupont']);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $active = $this->createTenant(['name' => 'Pâtisserie Active']);
    $this->createTenant(['name' => 'Boutique En attente', 'status' => TenantStatus::Pending]);
    $this->createTenant(['name' => 'Boutique Essai', 'status' => TenantStatus::Trialing]);
    $this->createTenant(['name' => 'Boutique Suspendue', 'status' => TenantStatus::Suspended]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.dashboard')
        ->assertOk()
        ->assertSet('totalTenants', 4)
        ->assertSet('tenantsByStatus', [
            TenantStatus::Active->value => 1,
            TenantStatus::Pending->value => 1,
            TenantStatus::Trialing->value => 1,
            TenantStatus::Suspended->value => 1,
        ])
        ->assertSee('Pâtisserie Active')
        ->assertSee('Boutique En attente')
        ->assertSee('En attente')
        ->assertSee('Boutique Suspendue')
        ->assertSee('Ariane Dupont')
        ->assertSee('Super-Admin')
        ->assertSee('Tout voir');
});

test('super admin can access the audit page and sees journalized actions', function () {
    $admin = AdminUser::factory()->create(['name' => 'Ariane Dupont', 'email' => 'ariane@cakeman.test']);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['name' => 'Pâtisserie Auditee']);

    AdminAuditLog::record('tenant.status_changed', ['status' => 'suspended'], $tenant->id, $admin->id);
    AdminAuditLog::record('tenant.invitation_resent', ['domain' => 'auditee.cakeman.test'], $tenant->id, $admin->id);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.audit.index')
        ->assertOk()
        ->assertSee('ariane@cakeman.test')
        ->assertSee('Pâtisserie Auditee')
        ->assertSee('Statut de boutique modifié')
        ->assertSee('Invitation gérant renvoyée')
        ->assertSee('status : suspended');
});

test('support can access the audit page but billing cannot', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.audit.index')
        ->assertOk();

    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.audit.index')
        ->assertForbidden();
});

test('audit page can be filtered by action and searched', function () {
    $admin = AdminUser::factory()->create(['email' => 'ariane@cakeman.test']);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    AdminAuditLog::record('tenant.invitation_sent', ['email' => 'aya@manuelle.ci'], null, $admin->id);
    AdminAuditLog::record('subscription.cancelled', ['plan' => 'Pro'], null, $admin->id);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.audit.index')
        ->assertSee('aya@manuelle.ci')
        ->assertSee('plan : Pro')
        ->set('filters.action', 'tenant.invitation_sent')
        ->assertSee('aya@manuelle.ci')
        ->assertDontSee('plan : Pro')
        ->set('filters.action', 'all')
        ->set('search', 'aya@manuelle.ci')
        ->assertSee('aya@manuelle.ci')
        ->assertDontSee('plan : Pro');
});

test('support sessions page is accessible to support but forbidden for billing', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.support-sessions.index')
        ->assertOk();

    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.support-sessions.index')
        ->assertForbidden();
});

test('support can open a session with a mandatory reason and limited duration', function () {
    $support = AdminUser::factory()->create(['name' => 'Aya Kouassi', 'email' => 'aya@cakeman.test']);
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $tenant = $this->createTenant(['name' => 'Pâtisserie Auditee']);

    Livewire::actingAs($support, 'admin')
        ->test('admin.support-sessions.index')
        ->set('sessionTenantId', $tenant->id)
        ->set('sessionReason', 'Analyse d\'un problème de connexion signalé par le gérant')
        ->set('sessionDurationMinutes', 30)
        ->call('startSession')
        ->assertHasNoErrors();

    $session = AdminSupportSession::where('tenant_id', $tenant->id)->firstOrFail();

    expect($session->admin_user_id)->toBe($support->id);
    expect($session->duration_minutes)->toBe(30);
    expect($session->expires_at->gt(now()))->toBeTrue();
    expect($session->isActive())->toBeTrue();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'support.session_started',
        'tenant_id' => $tenant->id,
    ]);
});

test('opening a session requires a reason of at least ten characters', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $tenant = $this->createTenant();

    Livewire::actingAs($support, 'admin')
        ->test('admin.support-sessions.index')
        ->set('sessionTenantId', $tenant->id)
        ->set('sessionReason', 'Court')
        ->call('startSession')
        ->assertHasErrors(['sessionReason']);

    expect(AdminSupportSession::count())->toBe(0);
});

test('an expired session is not active and cannot be ended', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $tenant = $this->createTenant();

    $session = AdminSupportSession::create([
        'admin_user_id' => $support->id,
        'tenant_id' => $tenant->id,
        'reason' => 'Session expirée depuis longtemps',
        'duration_minutes' => 15,
        'expires_at' => now()->subHour(),
    ]);

    expect($session->isActive())->toBeFalse();
    expect($session->status())->toBe('expired');

    Livewire::actingAs($support, 'admin')
        ->test('admin.support-sessions.index')
        ->call('endSession', $session->id)
        ->assertStatus(404);
});

test('an expired session cannot be viewed', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $tenant = $this->createTenant();

    $session = AdminSupportSession::create([
        'admin_user_id' => $support->id,
        'tenant_id' => $tenant->id,
        'reason' => 'Session expirée depuis longtemps',
        'duration_minutes' => 15,
        'expires_at' => now()->subHour(),
    ]);

    Livewire::actingAs($support, 'admin')
        ->test('admin.support-sessions.index')
        ->call('showSnapshot', $session->id)
        ->assertStatus(404);
});

test('support can end an active session and it is journalized', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $tenant = $this->createTenant();

    $session = AdminSupportSession::create([
        'admin_user_id' => $support->id,
        'tenant_id' => $tenant->id,
        'reason' => 'Vérification du compte du gérant',
        'duration_minutes' => 30,
        'expires_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($support, 'admin')
        ->test('admin.support-sessions.index')
        ->call('endSession', $session->id)
        ->assertHasNoErrors();

    expect($session->fresh()->ended_at)->not->toBeNull();
    expect($session->fresh()->status())->toBe('ended');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'support.session_ended',
        'tenant_id' => $tenant->id,
    ]);
});

test('support can view tenant metrics in a read-only session', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $tenant = $this->createTenant();
    $tenant->run(function () {
        DB::table('users')->insert([
            'name' => 'Gérant',
            'email' => 'gerant@boutique.test',
            'password' => 'secret',
            'is_active' => true,
        ]);
    });

    $session = AdminSupportSession::create([
        'admin_user_id' => $support->id,
        'tenant_id' => $tenant->id,
        'reason' => 'Vérification des données du gérant',
        'duration_minutes' => 30,
        'expires_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($support, 'admin')
        ->test('admin.support-sessions.index')
        ->call('showSnapshot', $session->id)
        ->assertSet('snapshotSessionId', $session->id)
        ->assertSee('Utilisateurs actifs')
        ->assertSee('Invitations en attente')
        ->assertSee('Emplois en file')
        ->assertSee('Emplois échoués')
        ->assertSee('aucune écriture possible');
});

test('a manager cannot be invited once the shop is active', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('inviteManagerName', 'Aya Kouassi')
        ->set('inviteManagerEmail', 'aya2@manuelle.ci')
        ->call('inviteManager')
        ->assertStatus(422);

    $tenant->run(function (): void {
        expect(TenantUserInvitation::count())->toBe(0);
    });

    Mail::assertNothingSent();
});

test('an invitation cannot be resent once the shop is active', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('resendInvitation')
        ->assertStatus(422);

    Mail::assertNothingSent();
});

test('a pending shop without a manager cannot be activated', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('toggleStatus')
        ->assertStatus(422);

    expect($tenant->fresh()->status)->toBe(TenantStatus::Pending);
});

test('a suspended shop with a valid manager can be reactivated', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Suspended]);

    $tenant->run(function (): void {
        (new TenantRolesSeeder)->run();

        $manager = User::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'password' => 'mot-de-passe',
            'is_active' => true,
        ]);

        $manager->assignRole(TenantRolesSeeder::ROLE_MANAGER);
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('toggleStatus')
        ->assertHasNoErrors();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.status_changed',
        'tenant_id' => $tenant->id,
    ]);
});

test('the show page hides manager actions for an active shop', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $tenant->run(function (): void {
        (new TenantRolesSeeder)->run();

        $manager = User::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'password' => 'mot-de-passe',
            'is_active' => true,
        ]);

        $manager->assignRole(TenantRolesSeeder::ROLE_MANAGER);
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->assertDontSee('Inviter Gérant')
        ->assertSee('Suspendre');
});

test('a manager can be replaced on an active shop via a transfer invitation', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $tenant->run(function (): void {
        (new TenantRolesSeeder)->run();

        $manager = User::create([
            'name' => 'Ancien Gérant',
            'email' => 'ancien@manuelle.ci',
            'password' => 'mot-de-passe',
            'is_active' => true,
        ]);

        $manager->assignRole(TenantRolesSeeder::ROLE_MANAGER);
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('inviteManagerName', 'Nouveau Gérant')
        ->set('inviteManagerEmail', 'nouveau@manuelle.ci')
        ->call('replaceManager')
        ->assertHasNoErrors();

    $tenant->run(function (): void {
        $invitation = TenantUserInvitation::where('email', 'nouveau@manuelle.ci')->firstOrFail();

        expect($invitation->accepted_at)->toBeNull()
            ->and($invitation->isValid())->toBeTrue()
            ->and(User::where('email', 'ancien@manuelle.ci')->firstOrFail()->is_active)->toBeTrue();
    });

    Mail::assertQueued(ManagerInvitationMail::class, function (ManagerInvitationMail $mail): bool {
        return $mail->hasTo('nouveau@manuelle.ci')
            && str_contains($mail->acceptUrl, '/tenant-invitations/');
    });

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.manager_replaced',
        'tenant_id' => $tenant->id,
    ]);
});

test('replacing a manager is rejected on a shop without a manager', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('inviteManagerName', 'Nouveau Gérant')
        ->set('inviteManagerEmail', 'nouveau@manuelle.ci')
        ->call('replaceManager')
        ->assertStatus(422);

    $tenant->run(function (): void {
        expect(TenantUserInvitation::count())->toBe(0);
    });

    Mail::assertNothingSent();
});

test('the replace manager action is hidden until a manager exists', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->assertDontSee('Remplacer le gérant')
        ->assertSee('Inviter Gérant');
});

test('accepting a replacement invitation deactivates the previous manager', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $token = null;
    $tenant->run(function () use (&$token) {
        (new TenantRolesSeeder)->run();

        $manager = User::create([
            'name' => 'Ancien Gérant',
            'email' => 'ancien@manuelle.ci',
            'password' => 'mot-de-passe',
            'is_active' => true,
        ]);

        $manager->assignRole(TenantRolesSeeder::ROLE_MANAGER);

        $invitation = TenantUserInvitation::create([
            'name' => 'Nouveau Gérant',
            'email' => 'nouveau@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        $token = $invitation->token;
    });

    $tenant->run(function () use ($token) {
        Livewire::test('onboarding.invitation-accept', ['token' => $token])
            ->set('password', 'mot-de-passe')
            ->set('passwordConfirmation', 'mot-de-passe')
            ->call('accept')
            ->assertHasNoErrors();

        expect(User::where('email', 'ancien@manuelle.ci')->firstOrFail()->is_active)->toBeFalse()
            ->and(User::where('email', 'nouveau@manuelle.ci')->firstOrFail()->is_active)->toBeTrue()
            ->and(User::role(TenantRolesSeeder::ROLE_MANAGER)->where('is_active', true)->count())->toBe(1);
    });
});
