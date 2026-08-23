<?php

use App\Enums\SubscriptionStatus;
use App\Jobs\GenerateMonthlyExport;
use App\Jobs\ProcessDunning;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionDunningAttempt;
use App\Models\SupportTicket;
use App\Services\InvoiceExportService;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\PlansSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
    (new PlansSeeder)->run();
});

test('dunning creates attempt for past_due and suspends after 14 days', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'starter')->firstOrFail();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => 'monthly',
        'status' => SubscriptionStatus::PastDue->value,
        'current_period_start' => now()->subDays(20),
        'current_period_end' => now()->addDays(10),
        'updated_at' => now()->subDays(2),
    ]);
    // force past_due age = 2 days (attempt 1 should trigger at day 1)
    DB::table('subscriptions')->where('id', $subscription->id)->update(['updated_at' => now()->subDays(2)]);

    (new ProcessDunning)->handle();

    expect(SubscriptionDunningAttempt::where('subscription_id', $subscription->id)->count())->toBe(1);

    // advance to 15 days → suspension
    DB::table('subscriptions')->where('id', $subscription->id)->update(['updated_at' => now()->subDays(15)]);
    DB::table('subscription_dunning_attempts')->where('subscription_id', $subscription->id)->update(['created_at' => now()->subDays(15), 'updated_at' => now()->subDays(15)]);

    (new ProcessDunning)->handle();

    expect($tenant->fresh()->status->value)->toBe('suspended');
    expect(SubscriptionDunningAttempt::where('subscription_id', $subscription->id)->where('status', 'suspended')->exists())->toBeTrue();
});

test('tickets can be created assigned replied and closed', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.support-tickets.index')
        ->set('ticketTenantId', $tenant->id)
        ->set('ticketSubject', 'Paiement bloqué')
        ->set('ticketBody', 'La boutique ne peut plus encaisser depuis hier.')
        ->set('ticketPriority', 'high')
        ->call('createTicket')
        ->assertHasNoErrors();

    $ticket = SupportTicket::where('subject', 'Paiement bloqué')->firstOrFail();
    expect($ticket->status)->toBe('open');

    // assign to me
    Livewire::actingAs($admin, 'admin')
        ->test('admin.support-tickets.index')
        ->call('assignToMe', $ticket->id)
        ->assertHasNoErrors();

    expect($ticket->fresh()->assigned_to)->toBe($admin->id);
    expect($ticket->fresh()->status)->toBe('pending');

    // reply
    Livewire::actingAs($admin, 'admin')
        ->test('admin.support-tickets.index')
        ->set('replyTicketId', $ticket->id)
        ->set('replyBody', 'Prise en charge, investigation en cours.')
        ->call('addReply')
        ->assertHasNoErrors();

    expect($ticket->fresh()->messages()->count())->toBe(1);

    // close
    Livewire::actingAs($admin, 'admin')
        ->test('admin.support-tickets.index')
        ->call('closeTicket', $ticket->id)
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe('closed');
});

test('exports csv download and monthly store', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'starter')->firstOrFail();

    Invoice::create([
        'number' => 'INV-EXPORT-001',
        'tenant_id' => $tenant->id,
        'subscription_id' => null,
        'amount' => 5000,
        'status' => 'paid',
        'due_at' => now(),
        'paid_at' => now(),
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
    ]);

    $response = actingAs($admin, 'admin')
        ->get(route('admin.exports.invoices.csv', ['from' => now()->subMonth()->format('Y-m-d'), 'to' => now()->format('Y-m-d')]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.csv');
    expect(AdminAuditLog::where('action', 'invoice.exported')->exists())->toBeTrue();

    // Contenu CSV vérifié via le service (StreamedResponse non capturable en TestResponse)
    $service = app(InvoiceExportService::class);
    $invoices = $service->query(now()->subMonth(), now());
    expect($invoices->pluck('number')->contains('INV-EXPORT-001'))->toBeTrue();

    Storage::fake('local');
    $path = $service->storeMonthly(now()->subMonth());
    Storage::disk('local')->assertExists($path);

    // monthly job
    Storage::fake('local');
    (new GenerateMonthlyExport)->handle($service);
    expect(Storage::disk('local')->files('exports'))->not->toBeEmpty();
});

test('announcements page uses modal and table', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.announcements.index')
        ->set('title', 'Info YNEX')
        ->set('body', 'Alignée en modal')
        ->call('publish')
        ->assertHasNoErrors();

    expect(Announcement::where('title', 'Info YNEX')->exists())->toBeTrue();
});
