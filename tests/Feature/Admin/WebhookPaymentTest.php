<?php

use App\Enums\BillingPeriod;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\AdminUser;
use App\Models\AdminWebhookLog;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\PlansSeeder;
use Livewire\Livewire;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
    (new PlansSeeder)->run();
});

function webhookSignature(array $payload): string
{
    return hash_hmac('sha256', json_encode($payload), 'test-secret');
}

function createInvoicedSubscription(array $overrides = []): array
{
    $tenant = test()->createTenant(['name' => 'Pâtisserie Facturée']);
    $plan = Plan::where('code', 'starter')->firstOrFail();

    $subscription = Subscription::create(array_merge([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::PastDue,
        'current_period_start' => now()->subMonth(),
        'current_period_end' => now(),
        'grace_until' => now()->addDays(7),
    ], $overrides));

    $invoice = Invoice::create([
        'number' => 'FACT-2026-00001',
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'amount' => 5000,
        'status' => InvoiceStatus::Pending,
        'due_at' => now()->addDays(7),
        'period_start' => now()->subMonth(),
        'period_end' => now(),
        'method' => 'wave',
        'provider_ref' => 'MM-FACT-2026-00001-TEST',
    ]);

    return [$tenant, $subscription, $invoice];
}

test('a valid payment webhook marks the invoice paid and activates the subscription', function () {
    [$tenant, $subscription, $invoice] = createInvoicedSubscription();

    $payload = [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'amount' => 5000,
    ];

    $this->postJson(route('webhooks.mobile-money'), $payload, [
        'X-Cakeman-Signature' => webhookSignature($payload),
    ])->assertOk()->assertJson(['status' => 'processed']);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    expect($invoice->fresh()->paid_at)->not->toBeNull();
    expect($invoice->fresh()->method)->toBe('wave');
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
    expect($subscription->fresh()->grace_until)->toBeNull();
    expect($subscription->fresh()->current_period_end->isFuture())->toBeTrue();

    $this->assertDatabaseHas('admin_webhook_logs', [
        'status' => 'processed',
        'provider_ref' => $invoice->provider_ref,
        'related_invoice_id' => $invoice->id,
    ]);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'invoice.paid',
        'tenant_id' => $tenant->id,
    ]);
});

test('a payment webhook reactivates a suspended tenant', function () {
    [$tenant, $subscription, $invoice] = createInvoicedSubscription([
        'status' => SubscriptionStatus::PastDue,
        'grace_until' => now()->subDay(),
    ]);

    $tenant->update(['status' => TenantStatus::Suspended]);

    $payload = [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'amount' => 5000,
    ];

    $this->postJson(route('webhooks.mobile-money'), $payload, [
        'X-Cakeman-Signature' => webhookSignature($payload),
    ])->assertOk()->assertJson(['status' => 'processed']);

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('duplicate webhooks are ignored and journalized', function () {
    [, , $invoice] = createInvoicedSubscription();

    $payload = [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'amount' => 5000,
    ];

    $headers = ['X-Cakeman-Signature' => webhookSignature($payload)];

    $this->postJson(route('webhooks.mobile-money'), $payload, $headers)->assertOk();
    $this->postJson(route('webhooks.mobile-money'), $payload, $headers)->assertOk();

    expect(AdminWebhookLog::where('provider_ref', $invoice->provider_ref)->count())->toBe(2);
    expect(AdminWebhookLog::where('provider_ref', $invoice->provider_ref)->where('status', 'duplicate')->count())->toBe(1);
    expect(Invoice::find($invoice->id)->status)->toBe(InvoiceStatus::Paid);
});

test('a webhook with an invalid signature is rejected', function () {
    [, , $invoice] = createInvoicedSubscription();

    $payload = [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'amount' => 5000,
    ];

    $this->postJson(route('webhooks.mobile-money'), $payload, [
        'X-Cakeman-Signature' => 'invalide',
    ])->assertStatus(401);

    expect(Invoice::find($invoice->id)->status)->toBe(InvoiceStatus::Pending);
    expect(AdminWebhookLog::where('status', 'failed')->where('error', 'Signature invalide')->count())->toBe(1);
});

test('a webhook with a mismatched amount is flagged and does not mark the invoice paid', function () {
    [, $subscription, $invoice] = createInvoicedSubscription();

    $payload = [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'amount' => 100,
    ];

    $this->postJson(route('webhooks.mobile-money'), $payload, [
        'X-Cakeman-Signature' => webhookSignature($payload),
    ])->assertOk()->assertJson(['status' => 'mismatch']);

    expect(Invoice::find($invoice->id)->status)->toBe(InvoiceStatus::Pending);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue);
    expect(AdminWebhookLog::where('provider_ref', $invoice->provider_ref)->where('status', 'mismatch')->count())->toBe(1);
});

test('a webhook for an unknown provider ref is logged as failed', function () {
    $this->postJson(route('webhooks.mobile-money'), [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => 'MM-INCONNUE',
        'amount' => 5000,
    ], [
        'X-Cakeman-Signature' => webhookSignature([
            'provider' => 'wave',
            'event' => 'payment.succeeded',
            'provider_ref' => 'MM-INCONNUE',
            'amount' => 5000,
        ]),
    ])->assertOk()->assertJson(['status' => 'failed']);

    expect(AdminWebhookLog::where('provider_ref', 'MM-INCONNUE')->where('status', 'failed')->count())->toBe(1);
});

test('support can view webhook logs and replay a failed one', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    [, , $invoice] = createInvoicedSubscription();

    $log = AdminWebhookLog::create([
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'payload' => [
            'provider' => 'wave',
            'event' => 'payment.succeeded',
            'provider_ref' => $invoice->provider_ref,
            'amount' => 5000,
        ],
        'signature_valid' => true,
        'status' => 'failed',
        'error' => 'Aucune facture pour ce provider_ref',
        'processed_at' => now(),
    ]);

    Livewire::actingAs($support, 'admin')
        ->test('admin.webhooks.index')
        ->assertOk()
        ->assertSee('MM-FACT-2026-00001-TEST')
        ->assertSee('Échec')
        ->call('replay', $log->id)
        ->assertHasNoErrors();

    expect($log->fresh()->status)->toBe('processed');
    expect(Invoice::find($invoice->id)->status)->toBe(InvoiceStatus::Paid);
});

test('replaying a processed webhook is refused', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    [, , $invoice] = createInvoicedSubscription();

    $log = AdminWebhookLog::create([
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'payload' => [],
        'signature_valid' => true,
        'status' => 'processed',
        'processed_at' => now(),
    ]);

    Livewire::actingAs($support, 'admin')
        ->test('admin.webhooks.index')
        ->call('replay', $log->id)
        ->assertStatus(404);
});

test('billing cannot access the webhook page', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.webhooks.index')
        ->assertForbidden();
});

test('simulating a payment from the invoices modal settles the invoice', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    [$tenant, $subscription, $invoice] = createInvoicedSubscription();

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->call('showInvoices', $tenant->id)
        ->assertSee('FACT-2026-00001')
        ->call('simulatePayment', $invoice->id)
        ->assertHasNoErrors();

    expect(Invoice::find($invoice->id)->status)->toBe(InvoiceStatus::Paid);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
    expect(AdminWebhookLog::where('provider_ref', $invoice->provider_ref)->where('status', 'processed')->count())->toBe(1);
});

test('an already paid invoice cannot be simulated again', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    [$tenant, , $invoice] = createInvoicedSubscription();

    $invoice->update(['status' => InvoiceStatus::Paid, 'paid_at' => now()]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->call('simulatePayment', $invoice->id)
        ->assertStatus(404);
});
