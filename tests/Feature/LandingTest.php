<?php

use App\Enums\LeadStatus;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Lead;
use App\Models\Tenant;
use Database\Seeders\AdminRolesSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
});

test('landing page renders the lead form', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Demande de démonstration')
        ->assertSee('Demander une démonstration');
});

test('lead form stores a new lead with phone normalization and dedupe', function () {
    Livewire::test('landing.lead-form')
        ->set('name', 'Fatou Ndiaye')
        ->set('email', 'FATOU@DEMO.CI')
        ->set('phone', '07 12 34 56 78')
        ->set('shopName', 'Douceurs de Fatou')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $lead = Lead::where('email', 'fatou@demo.ci')->firstOrFail();

    expect($lead->name)->toBe('Fatou Ndiaye')
        ->and($lead->phone)->toBe('225712345678')
        ->and($lead->shop_name)->toBe('Douceurs de Fatou')
        ->and($lead->source)->toBe('landing')
        ->and($lead->status)->toBe(LeadStatus::New);

    Livewire::test('landing.lead-form')
        ->set('name', 'Fatou Ndiaye')
        ->set('email', 'FATOU@demo.ci')
        ->call('submit')
        ->assertHasErrors('email');

    expect(Lead::count())->toBe(1);
});

test('lead form rejects invalid data', function () {
    Livewire::test('landing.lead-form')
        ->set('name', '')
        ->set('email', 'pas-un-email')
        ->call('submit')
        ->assertHasErrors(['name', 'email']);

    expect(Lead::count())->toBe(0);
});

test('leads page requires the lead view permission', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.leads.index')
        ->assertStatus(403);
});

test('billing can view leads and manage their status', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);
    $lead = Lead::create([
        'name' => 'Awa Bamba',
        'email' => 'awa@boutique.ci',
        'phone' => '0708091011',
        'source' => 'landing',
    ]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.leads.index')
        ->assertOk()
        ->assertSee('Awa Bamba')
        ->assertSee('awa@boutique.ci')
        ->assertSee('1')
        ->call('markContacted', $lead->id)
        ->assertHasNoErrors();

    expect($lead->fresh()->status)->toBe(LeadStatus::Contacted);
    expect(AdminAuditLog::where('action', 'lead.contacted')->exists())->toBeTrue();
});

test('a lead can be converted and linked to a tenant', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);
    $tenant = $this->createTenant();
    $lead = Lead::create([
        'name' => 'Awa Bamba',
        'email' => 'awa2@boutique.ci',
        'source' => 'landing',
    ]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.leads.index')
        ->set('pendingLeadId', $lead->id)
        ->set('convertTenantId', $tenant->id)
        ->call('confirmConversion')
        ->assertHasNoErrors();

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted)
        ->and($lead->fresh()->converted_tenant_id)->toBe($tenant->id);
    expect(AdminAuditLog::where('action', 'lead.converted')->exists())->toBeTrue();
});

test('registering with a matching lead email converts it automatically', function () {
    Mail::fake();

    $lead = Lead::create([
        'name' => 'Aya Kouassi',
        'email' => 'aya@douceur.ci',
        'source' => 'landing',
    ]);

    Livewire::test('onboarding.register-tenant')
        ->set('businessName', 'Pâtisserie Douceur')
        ->set('subdomain', 'douceur')
        ->set('managerName', 'Aya Kouassi')
        ->set('email', 'aya@douceur.ci')
        ->call('register')
        ->assertHasNoErrors();

    $tenant = Tenant::where('name', 'Pâtisserie Douceur')->firstOrFail();

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted)
        ->and($lead->fresh()->converted_tenant_id)->toBe($tenant->id);
});

test('leads can be exported as csv', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    Lead::create([
        'name' => 'Awa Bamba',
        'email' => 'awa3@boutique.ci',
        'source' => 'landing',
    ]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.leads.index')
        ->call('export')
        ->assertFileDownloaded();
});

test('legal and faq pages render', function () {
    $this->get(route('faq'))->assertOk()->assertSee('Questions fréquentes');
    $this->get(route('privacy'))->assertOk()->assertSee('Confidentialité');
    $this->get(route('terms'))->assertOk()->assertSee('Conditions d\'utilisation');
});

test('sitemap lists the public routes', function () {
    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('home'))
        ->assertSee(route('register'))
        ->assertSee(route('faq'));
});

test('a tenant subdomain root redirects to the shop dashboard', function () {
    $tenant = $this->createTenant();

    $domain = $tenant->domains->firstOrFail()->domain;

    $this->tenantGet($tenant, '/')
        ->assertRedirect('http://'.$domain.'/dashboard');
});

test('the landing page is not shown on a tenant subdomain', function () {
    $tenant = $this->createTenant();

    $this->tenantGet($tenant, '/')
        ->assertDontSee('Demande de démonstration');
});
