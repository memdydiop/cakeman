<?php

use App\Enums\AdminPermission;
use App\Mail\AdminInvitationMail;
use App\Mail\AdminPasswordResetMail;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\AdminUserInvitation;
use App\Models\User;
use Database\Seeders\AdminRolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Livewire\Livewire;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
});

test('admin user structure is aligned with tenant users', function () {
    $tenant = $this->createTenant();
    tenancy()->initialize($tenant);

    $adminColumns = Schema::connection('pgsql')->getColumnListing('admin_users');
    $userColumns = Schema::getColumnListing('users');

    $aligned = ['name', 'email', 'email_verified_at', 'password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'remember_token', 'phone', 'is_active', 'last_login_at'];

    foreach ($aligned as $column) {
        expect($adminColumns)->toContain($column);
        expect($userColumns)->toContain($column);
    }

    expect(in_array(PasskeyUser::class, class_implements(AdminUser::class), true))->toBeTrue();
    expect(collect(class_uses_recursive(User::class))->keys())->toHaveSameSize(collect(class_uses_recursive(AdminUser::class))->keys());

    $admin = AdminUser::on('pgsql')->create([
        'name' => 'Aya',
        'email' => 'aya-console@cakeman.test',
        'password' => 'mot-de-passe',
        'phone' => '+225 07 00 00 00 00',
    ]);

    expect($admin->fresh()->phone)->toBe('+225 07 00 00 00 00');
    expect($admin->initials())->toBeString();
});

test('inviting a user sends an email and creates an inactive account', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->assertSee('$flux.modal')
        ->set('inviteEmail', 'nouveau@cakeman.test')
        ->set('inviteRole', AdminRolesSeeder::ROLE_SUPPORT)
        ->call('invite')
        ->assertHasNoErrors();

    $invited = AdminUser::where('email', 'nouveau@cakeman.test')->firstOrFail();

    expect($invited->is_active)->toBeFalse();
    expect($invited->hasRole(AdminRolesSeeder::ROLE_SUPPORT))->toBeTrue();

    $invitation = AdminUserInvitation::where('email', 'nouveau@cakeman.test')->firstOrFail();
    expect($invitation->isValid())->toBeTrue();

    Mail::assertQueued(AdminInvitationMail::class);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.invited',
        'admin_user_id' => $admin->id,
    ]);
});

test('accepting an invitation activates the account and logs in', function () {
    $invited = AdminUser::factory()->create([
        'email' => 'nouveau@cakeman.test',
        'is_active' => false,
    ]);
    $invited->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $invitation = AdminUserInvitation::create([
        'email' => 'nouveau@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => 'token-valide',
        'expires_at' => now()->addHours(48),
    ]);

    Livewire::test('admin.invitation-accept', ['token' => 'token-valide'])
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect($invited->fresh()->is_active)->toBeTrue();
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect(auth('admin')->id())->toBe($invited->id);
});

test('expired invitation is rejected', function () {
    $invited = AdminUser::factory()->create([
        'email' => 'nouveau@cakeman.test',
        'is_active' => false,
    ]);

    AdminUserInvitation::create([
        'email' => 'nouveau@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => 'token-expire',
        'expires_at' => now()->subHour(),
    ]);

    Livewire::test('admin.invitation-accept', ['token' => 'token-expire'])
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('accept')
        ->assertHasErrors(['token']);

    expect($invited->fresh()->is_active)->toBeFalse();
    $this->assertGuest('admin');
});

test('accepting an already consumed invitation is rejected', function () {
    $invited = AdminUser::factory()->create([
        'email' => 'nouveau@cakeman.test',
        'is_active' => false,
    ]);

    $invitation = AdminUserInvitation::create([
        'email' => 'nouveau@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => 'token-consomme',
        'expires_at' => now()->addHours(48),
    ]);

    $invitation->update(['accepted_at' => now()->subHour()]);

    Livewire::test('admin.invitation-accept', ['token' => 'token-consomme'])
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('accept')
        ->assertHasErrors(['token']);

    expect($invited->fresh()->is_active)->toBeFalse();
    $this->assertGuest('admin');
});

test('a consumed invitation link shows an unusable state instead of the form', function () {
    AdminUser::factory()->create([
        'email' => 'nouveau@cakeman.test',
        'is_active' => false,
    ]);

    $invitation = AdminUserInvitation::create([
        'email' => 'nouveau@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => 'token-consomme',
        'expires_at' => now()->addHours(48),
    ]);

    $invitation->update(['accepted_at' => now()->subHour()]);

    Livewire::test('admin.invitation-accept', ['token' => 'token-consomme'])
        ->assertSee('Cette invitation a déjà été utilisée.')
        ->assertDontSee('Confirmation du mot de passe');
});

test('an expired invitation link shows an unusable state instead of the form', function () {
    AdminUserInvitation::create([
        'email' => 'nouveau@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => 'token-expire',
        'expires_at' => now()->subHour(),
    ]);

    Livewire::test('admin.invitation-accept', ['token' => 'token-expire'])
        ->assertSee('Ce lien a expiré.')
        ->assertDontSee('Confirmation du mot de passe');
});

test('deactivating a user revokes sessions and blocks access', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'session-test',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => '{}',
        'last_activity' => now()->timestamp,
    ]);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.index')
        ->call('startEditing', $target->id)
        ->set('editRoles', [AdminRolesSeeder::ROLE_BILLING])
        ->set('editIsActive', false)
        ->call('saveEditing')
        ->assertHasNoErrors();

    expect($target->fresh()->is_active)->toBeFalse();
    expect($target->fresh()->hasRole(AdminRolesSeeder::ROLE_BILLING))->toBeTrue();
    expect(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.updated',
        'admin_user_id' => $superAdmin->id,
    ]);

    expect($target->fresh()->canAccessConsole())->toBeFalse();
});

test('editing a user can assign multiple roles and direct permissions', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->create();
    $target->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.index')
        ->call('startEditing', $target->id)
        ->assertDispatched('modal-show', name: 'edit-user')
        ->set('editRoles', [AdminRolesSeeder::ROLE_SUPPORT, AdminRolesSeeder::ROLE_BILLING])
        ->set('editPermissions', [AdminPermission::MonitoringView->value])
        ->call('saveEditing')
        ->assertHasNoErrors();

    $target->refresh();

    expect($target->hasAllRoles([AdminRolesSeeder::ROLE_SUPPORT, AdminRolesSeeder::ROLE_BILLING]))->toBeTrue()
        ->and($target->hasPermissionTo(AdminPermission::MonitoringView->value))->toBeTrue();
});

test('a user cannot deactivate their own account', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.index')
        ->call('startEditing', $superAdmin->id)
        ->set('editRoles', [AdminRolesSeeder::ROLE_SUPER_ADMIN])
        ->set('editIsActive', false)
        ->call('saveEditing')
        ->assertHasErrors(['editIsActive']);

    expect($superAdmin->fresh()->is_active)->toBeTrue();
});

test('a user cannot delete their own account', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.index')
        ->call('deleteUser', $superAdmin->id)
        ->assertHasErrors(['user']);

    expect(AdminUser::find($superAdmin->id))->not->toBeNull();
});

test('an active user cannot be deleted — deactivation is required first', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->create();

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.index')
        ->call('deleteUser', $target->id)
        ->assertHasErrors(['user']);

    expect(AdminUser::find($target->id))->not->toBeNull();

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.show', ['user' => $target->id])
        ->call('deleteUser')
        ->assertHasErrors(['user']);

    expect(AdminUser::find($target->id))->not->toBeNull();
});

test('deleting a user preserves the audit trail and purges sessions and pending invitations', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->inactive()->create(['email' => 'supprime@cakeman.test']);

    AdminAuditLog::record('admin_user.invited', ['email' => $target->email], adminUserId: $superAdmin->id);

    DB::table('sessions')->insert([
        'id' => 'session-delete',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => '{}',
        'last_activity' => now()->timestamp,
    ]);

    AdminUserInvitation::create([
        'email' => 'supprime@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
    ]);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.index')
        ->call('deleteUser', $target->id)
        ->assertHasNoErrors();

    expect(AdminUser::find($target->id))->toBeNull();
    expect(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);
    expect(AdminUserInvitation::where('email', 'supprime@cakeman.test')->exists())->toBeFalse();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.deleted',
        'admin_user_id' => $superAdmin->id,
    ]);
});

test('a deactivated user can be deleted from the show page', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->inactive()->create();

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.show', ['user' => $target->id])
        ->call('deleteUser')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    expect(AdminUser::find($target->id))->toBeNull();
});

test('pending invitations are listed with resend and revoke actions', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    AdminUserInvitation::create([
        'email' => 'invite@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->assertSee('invite@cakeman.test')
        ->assertSee('Invitations')
        ->assertSee('Valide')
        ->assertSeeHtml('wire:click="resendInvitation')
        ->assertSeeHtml('wire:click="revokeInvitation')
        ->assertSeeHtml('title="Renvoyer"')
        ->assertSeeHtml('title="Révoquer"');
});

test('consumed invitations are listed without resend and revoke actions', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    AdminUserInvitation::create([
        'email' => 'consommee@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
        'accepted_at' => now()->subDay(),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->assertSee('consommee@cakeman.test')
        ->assertSee('Consommée le')
        ->assertDontSeeHtml('wire:click="revokeInvitation')
        ->assertDontSeeHtml('wire:click="resendInvitation');
});

test('consumed and pending invitations are both listed on the users page', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    AdminUserInvitation::create([
        'email' => 'consommee@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
        'accepted_at' => now()->subDay(),
    ]);

    AdminUserInvitation::create([
        'email' => 'en-attente@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->assertSee('consommee@cakeman.test')
        ->assertSee('en-attente@cakeman.test')
        ->assertSee('Consommée le')
        ->assertSee('Valide');
});

test('resending an invitation renews the token and sends the email', function () {
    Mail::fake();

    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $invitation = AdminUserInvitation::create([
        'email' => 'invite@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->subHour(),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->call('resendInvitation', $invitation->id)
        ->assertHasNoErrors();

    $invitation->refresh();

    expect($invitation->isValid())->toBeTrue();

    Mail::assertQueued(AdminInvitationMail::class, fn ($mail) => $mail->hasTo('invite@cakeman.test'));

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.invitation_resent',
    ]);
});

test('a consumed invitation cannot be resent from the team page', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $invitation = AdminUserInvitation::create([
        'email' => 'invite@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
        'accepted_at' => now()->subDay(),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->call('resendInvitation', $invitation->id)
        ->assertStatus(404);

    $invitation->refresh();

    expect($invitation->accepted_at)->not->toBeNull();
});

test('revoking an invitation deletes it and the inactive account', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $invited = AdminUser::factory()->inactive()->create(['email' => 'invite@cakeman.test']);

    $invitation = AdminUserInvitation::create([
        'email' => 'invite@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->call('revokeInvitation', $invitation->id)
        ->assertHasNoErrors();

    expect(AdminUserInvitation::find($invitation->id))->toBeNull()
        ->and(AdminUser::find($invited->id))->toBeNull();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.invitation_revoked',
    ]);
});

test('a consumed invitation cannot be revoked from the team page', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $invitation = AdminUserInvitation::create([
        'email' => 'invite@cakeman.test',
        'role' => AdminRolesSeeder::ROLE_SUPPORT,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
        'accepted_at' => now()->subDay(),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->call('revokeInvitation', $invitation->id)
        ->assertStatus(404);

    expect(AdminUserInvitation::find($invitation->id))->not->toBeNull();
});

test('a deactivated user cannot log in to the console', function () {
    $admin = AdminUser::factory()->inactive()->create();

    Livewire::test('admin.login')
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors(['email']);

    $this->assertGuest('admin');
});

test('an active admin can log in to the console', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'patron@cakeman.test',
    ]);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::test('admin.login')
        ->set('email', 'patron@cakeman.test')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->id())->toBe($admin->id);
    expect($admin->fresh()->last_login_at)->not->toBeNull();
});

test('the team index filters users by role, status and 2FA', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $support = AdminUser::factory()->create(['name' => 'Support Actif']);
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    $billing = AdminUser::factory()->inactive()->create(['name' => 'Billing Inactif']);
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $with2fa = AdminUser::factory()->withTwoFactor()->create(['name' => 'Avec 2FA']);
    $with2fa->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->set('filters.role', AdminRolesSeeder::ROLE_SUPPORT)
        ->assertSee('Support Actif')
        ->assertSee('Avec 2FA')
        ->assertDontSee('Billing Inactif');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->set('filters.status', 'inactive')
        ->assertSee('Billing Inactif')
        ->assertDontSee('Support Actif')
        ->assertDontSee('Avec 2FA');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->set('filters.two_factor', 'enabled')
        ->assertSee('Avec 2FA')
        ->assertDontSee('Support Actif')
        ->assertDontSee('Billing Inactif');
});

test('the team index shows last login and 2FA columns', function () {
    $admin = AdminUser::factory()->create(['last_login_at' => now()->subDay()]);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->assertSee('Dernière connexion')
        ->assertSee('2FA')
        ->assertSee($admin->last_login_at->format('d/m/Y H:i'));
});

test('exportCsv returns a downloadable CSV containing the users and records audit', function () {
    $admin = AdminUser::factory()->create(['name' => 'Chef Équipe', 'email' => 'chef@cakeman.test']);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $support = AdminUser::factory()->create(['name' => 'Membre Équipe', 'email' => 'membre@cakeman.test']);
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.users.index')
        ->call('exportCsv')
        ->assertFileDownloaded();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.exported',
        'admin_user_id' => $admin->id,
    ]);
});

test('the user show page displays profile, sessions and targeted audit', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->create([
        'name' => 'Cible Visuelle',
        'last_login_at' => now()->subHour(),
    ]);
    $target->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    AdminAuditLog::record('admin_user.invited', ['email' => $target->email], adminUserId: $superAdmin->id);

    DB::table('sessions')->insert([
        'id' => 'session-show',
        'user_id' => $target->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'pest-browser',
        'payload' => '{}',
        'last_activity' => now()->timestamp,
    ]);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.show', ['user' => $target->id])
        ->assertSee('Cible Visuelle')
        ->assertSee('Sessions actives')
        ->assertSee('10.0.0.1')
        ->assertSee('Journal d\'audit')
        ->assertSee('Invité')
        ->assertSee('pest-browser')
        ->assertSee($target->last_login_at->format('d/m/Y H:i'));
});

test('deactivating a user from the show page revokes sessions', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->create();
    $target->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    DB::table('sessions')->insert([
        'id' => 'session-revoke',
        'user_id' => $target->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'pest-browser',
        'payload' => '{}',
        'last_activity' => now()->timestamp,
    ]);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.show', ['user' => $target->id])
        ->call('toggleActive')
        ->assertHasNoErrors();

    expect($target->fresh()->is_active)->toBeFalse();
    expect(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.updated',
        'admin_user_id' => $superAdmin->id,
    ]);
});

test('a user cannot deactivate their own account from the show page', function () {
    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.show', ['user' => $superAdmin->id])
        ->call('toggleActive')
        ->assertHasErrors(['user']);

    expect($superAdmin->fresh()->is_active)->toBeTrue();
});

test('sending a password reset creates a token and sends the email', function () {
    Mail::fake();

    $superAdmin = AdminUser::factory()->create();
    $superAdmin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $target = AdminUser::factory()->create(['email' => 'reset-cible@cakeman.test']);

    Livewire::actingAs($superAdmin, 'admin')
        ->test('admin.users.show', ['user' => $target->id])
        ->call('sendPasswordReset')
        ->assertHasNoErrors();

    expect(DB::table('admin_password_reset_tokens')->where('email', 'reset-cible@cakeman.test')->exists())->toBeTrue();

    Mail::assertQueued(AdminPasswordResetMail::class, fn ($mail) => $mail->hasTo('reset-cible@cakeman.test'));

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.password_reset_sent',
        'admin_user_id' => $superAdmin->id,
    ]);
});

test('the admin password reset page changes the password', function () {
    $target = AdminUser::factory()->create([
        'email' => 'reset-cible@cakeman.test',
        'password' => 'ancien-mot-de-passe',
    ]);

    $token = Password::broker('admins')->createToken($target);

    Livewire::test('admin.password-reset', ['token' => $token])
        ->set('email', 'reset-cible@cakeman.test')
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.login'));

    expect(Hash::check('nouveau-mot-de-passe', $target->fresh()->password))->toBeTrue();

    expect(DB::table('admin_password_reset_tokens')->where('email', 'reset-cible@cakeman.test')->exists())->toBeFalse();
});

test('the admin password reset page rejects a wrong email', function () {
    $target = AdminUser::factory()->create(['email' => 'reset-cible@cakeman.test']);

    $token = Password::broker('admins')->createToken($target);

    Livewire::test('admin.password-reset', ['token' => $token])
        ->set('email', 'autre@cakeman.test')
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect(Hash::check('nouveau-mot-de-passe', $target->fresh()->password))->toBeFalse();
});
