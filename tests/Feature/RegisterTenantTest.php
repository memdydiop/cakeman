<?php

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Http\Middleware\InitializeTenancyByDomainUnlessCentral;
use App\Mail\ManagerInvitationMail;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantUserInvitation;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    (new PlansSeeder)->run();
});

test('onboarding with a paid plan creates a trialing subscription', function () {
    Mail::fake();

    Livewire::test('onboarding.register-tenant', ['plan' => 'pro'])
        ->set('businessName', 'Pâtisserie Douceur')
        ->set('subdomain', 'douceur')
        ->set('managerName', 'Aya Kouassi')
        ->set('email', 'aya@douceur.ci')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect('http://douceur.cakeman.test/activation-sent');

    $tenant = Tenant::where('name', 'Pâtisserie Douceur')->firstOrFail();
    $plan = Plan::where('code', 'pro')->firstOrFail();
    $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

    expect($subscription->plan_id)->toBe($plan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->trial_ends_at->isFuture())->toBeTrue();
});

test('onboarding with an unknown plan falls back to the free plan', function () {
    Mail::fake();

    Livewire::test('onboarding.register-tenant', ['plan' => 'inconnu'])
        ->set('businessName', 'Pâtisserie Douceur')
        ->set('subdomain', 'douceur')
        ->set('managerName', 'Aya Kouassi')
        ->set('email', 'aya@douceur.ci')
        ->call('register')
        ->assertHasNoErrors();

    $tenant = Tenant::where('name', 'Pâtisserie Douceur')->firstOrFail();

    expect(Subscription::where('tenant_id', $tenant->id)->exists())->toBeFalse();
});

test('onboarding creates a pending tenant and sends an activation invitation', function () {
    Mail::fake();

    Livewire::test('onboarding.register-tenant')
        ->set('businessName', 'Pâtisserie Douceur')
        ->set('subdomain', 'douceur')
        ->set('managerName', 'Aya Kouassi')
        ->set('email', 'aya@douceur.ci')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect('http://douceur.cakeman.test/activation-sent');

    $tenant = Tenant::where('name', 'Pâtisserie Douceur')->firstOrFail();

    expect($tenant->status)->toBe(TenantStatus::Pending);
    expect($tenant->domains->pluck('domain'))->toContain('douceur.cakeman.test');

    $tenant->run(function () {
        $invitation = TenantUserInvitation::where('email', 'aya@douceur.ci')->firstOrFail();

        expect($invitation->name)->toBe('Aya Kouassi')
            ->and($invitation->isValid())->toBeTrue();

        expect(User::where('email', 'aya@douceur.ci')->exists())->toBeFalse();
    });

    Mail::assertQueued(ManagerInvitationMail::class, function (ManagerInvitationMail $mail): bool {
        return $mail->hasTo('aya@douceur.ci')
            && $mail->shopName === 'Pâtisserie Douceur'
            && str_contains($mail->acceptUrl, '/tenant-invitations/');
    });
});

test('duplicate subdomain is rejected', function () {
    $this->createTenant(domain: 'douceur.cakeman.test');

    Livewire::test('onboarding.register-tenant')
        ->set('businessName', 'Doublon')
        ->set('subdomain', 'douceur')
        ->set('managerName', 'Aya Kouassi')
        ->set('email', 'autre@douceur.ci')
        ->call('register')
        ->assertHasErrors(['subdomain']);

    expect(Tenant::where('name', 'Doublon')->exists())->toBeFalse();
});

test('duplicate email is rejected and tenant is cleaned up', function () {
    $tenant = $this->createTenant();

    $tenant->run(function () {
        User::create([
            'name' => 'Aya',
            'email' => 'aya@douceur.ci',
            'password' => 'mot-de-passe',
        ]);
    });

    Livewire::test('onboarding.register-tenant')
        ->set('businessName', 'Autre Boutique')
        ->set('subdomain', 'autre-boutique')
        ->set('managerName', 'Aya Kouassi')
        ->set('email', 'aya@douceur.ci')
        ->call('register')
        ->assertHasErrors(['email']);

    expect(Tenant::where('name', 'Autre Boutique')->exists())->toBeFalse();
});

test('invalid subdomain format is rejected', function () {
    Livewire::test('onboarding.register-tenant')
        ->set('businessName', 'Boutique')
        ->set('subdomain', 'PAS_VALIDE')
        ->set('managerName', 'Aya')
        ->set('email', 'aya@douceur.ci')
        ->call('register')
        ->assertHasErrors(['subdomain']);

    expect(Tenant::count())->toBe(0);
});

test('manager invitation activates the tenant and logs the manager in', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
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
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $manager = User::where('email', 'aya@manuelle.ci')->firstOrFail();

        expect($manager->is_active)->toBeTrue()
            ->and($manager->hasRole(TenantRolesSeeder::ROLE_MANAGER))->toBeTrue()
            ->and($manager->email_verified_at)->not->toBeNull()
            ->and(TenantUserInvitation::where('token', $token)->firstOrFail()->accepted_at)->not->toBeNull()
            ->and(auth()->check())->toBeTrue();
    });

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});

test('accepting an invitation is rejected when the tenant is already active', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
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
            ->assertHasErrors(['token']);

        expect(User::where('email', 'aya@manuelle.ci')->exists())->toBeFalse();
    });
});

test('expired manager invitation is rejected', function () {
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

    $tenant->run(function () use ($token) {
        Livewire::test('onboarding.invitation-accept', ['token' => $token])
            ->set('password', 'mot-de-passe')
            ->set('passwordConfirmation', 'mot-de-passe')
            ->call('accept')
            ->assertHasErrors(['token']);

        expect(User::where('email', 'aya@manuelle.ci')->exists())->toBeFalse();
    });

    expect($tenant->fresh()->status)->toBe(TenantStatus::Pending);
});

test('accepting an already consumed invitation is rejected', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        $invitation->update(['accepted_at' => now()->subHour()]);

        $token = $invitation->token;
    });

    $tenant->run(function () use ($token) {
        Livewire::test('onboarding.invitation-accept', ['token' => $token])
            ->set('password', 'mot-de-passe')
            ->set('passwordConfirmation', 'mot-de-passe')
            ->call('accept')
            ->assertHasErrors(['token']);

        expect(User::where('email', 'aya@manuelle.ci')->exists())->toBeFalse();
    });

    expect($tenant->fresh()->status)->toBe(TenantStatus::Pending);
});

test('a consumed invitation link shows an unusable state instead of the form', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        $invitation->update(['accepted_at' => now()->subHour()]);

        $token = $invitation->token;
    });

    $tenant->run(function () use ($token) {
        Livewire::test('onboarding.invitation-accept', ['token' => $token])
            ->assertSee('Cette invitation a déjà été utilisée.')
            ->assertDontSee('Confirmation du mot de passe');
    });
});

test('an expired invitation link shows an unusable state instead of the form', function () {
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

    $tenant->run(function () use ($token) {
        Livewire::test('onboarding.invitation-accept', ['token' => $token])
            ->assertSee('Ce lien a expiré.')
            ->assertDontSee('Confirmation du mot de passe');
    });
});

test('accepting an invitation is blocked when the plan user limit is reached', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $tenant->run(function () {
        User::create([
            'name' => 'Gérante Existante',
            'email' => 'existante@manuelle.ci',
            'password' => 'mot-de-passe',
        ]);
    });

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
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
            ->assertHasErrors(['password']);

        expect(User::where('email', 'aya@manuelle.ci')->exists())->toBeFalse();
    });

    expect($tenant->fresh()->status)->toBe(TenantStatus::Pending);
});

test('the livewire update route is covered by the conditional tenancy middleware', function () {
    $route = Route::getRoutes()->getByName('default-livewire.update');
    $webGroup = app('router')->getMiddlewareGroups()['web'] ?? [];

    expect($route)->not->toBeNull()
        ->and($webGroup)->toContain(InitializeTenancyByDomainUnlessCentral::class);
});

test('the livewire update middleware initializes tenancy on tenant hosts only', function () {
    $tenant = $this->createTenant();
    $middleware = app(InitializeTenancyByDomainUnlessCentral::class);

    $tenantRequest = Request::create('http://'.$tenant->domains()->first()->domain.'/livewire-update', 'POST');
    $middleware->handle($tenantRequest, fn () => null);

    expect(tenancy()->initialized)->toBeTrue();

    tenancy()->end();

    $centralRequest = Request::create('http://'.config('tenancy.root_domain').'/livewire-update', 'POST');
    $middleware->handle($centralRequest, fn () => null);

    expect(tenancy()->initialized)->toBeFalse();
});

test('the manager form is hidden when the tenant is already active', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        $token = $invitation->token;
    });

    $tenant->run(function () use ($token) {
        Livewire::test('onboarding.invitation-accept', ['token' => $token])
            ->assertDontSee('wire:submit="accept"')
            ->assertSee(__('Cette boutique est déjà active.'));
    });
});

test('an invitation expiring mid-session shows a visible message', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $token = null;
    $tenant->run(function () use (&$token) {
        $invitation = TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);

        $token = $invitation->token;
    });

    $tenant->run(function () use ($token) {
        $component = Livewire::test('onboarding.invitation-accept', ['token' => $token])
            ->set('password', 'mot-de-passe')
            ->set('passwordConfirmation', 'mot-de-passe');

        TenantUserInvitation::where('token', $token)->first()->update(['expires_at' => now()->subHour()]);

        $component->call('accept')
            ->assertHasErrors(['token'])
            ->assertSee(__('Ce lien a expiré.'));
    });
});
