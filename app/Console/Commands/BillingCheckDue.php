<?php

namespace App\Console\Commands;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Mail\BillingReminderMail;
use App\Models\AdminAuditLog;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InvoiceService;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Rattrapage quotidien du billing (CdC § 3, Module 12) : fin d'essai →
 * facture + lien de paiement Mobile Money puis attente de confirmation
 * (webhook) ; échéance dépassée → impayé (période de grâce 7 j) ; grâce
 * expirée → suspension de la boutique. Rappels e-mail (J-7/J-3/J-1) : v2.
 */
class BillingCheckDue extends Command
{
    protected $signature = 'billing:check-due';

    protected $description = 'Fait progresser les abonnements : essais, factures, retards et suspensions';

    public function __construct(private readonly InvoiceService $invoiceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        foreach (Subscription::with('tenant', 'plan')
            ->where('status', '!=', SubscriptionStatus::Cancelled->value)
            ->cursor() as $subscription) {
            $this->sendDueReminder($subscription);
            $this->advance($subscription);
        }

        return self::SUCCESS;
    }

    private function advance(Subscription $subscription): void
    {
        $now = now();

        if ($subscription->status === SubscriptionStatus::Trialing && $subscription->trial_ends_at?->isPast()) {
            $start = $subscription->trial_ends_at;
            $end = $start->copy()->add(
                $subscription->billing_period === BillingPeriod::Annual ? '1 year' : '1 month',
            );

            $invoice = $this->invoiceService->createForSubscription($subscription, $start, $end);

            $subscription->update([
                'current_period_start' => $start,
                'current_period_end' => $end,
                'status' => $invoice === null ? SubscriptionStatus::Active : SubscriptionStatus::PastDue,
                'grace_until' => $invoice === null ? null : $now->copy()->addDays((int) Setting::get('billing.grace_days', 7)),
            ]);

            AdminAuditLog::record('subscription.trial_ended', ['plan' => $subscription->plan->name], $subscription->tenant_id);

            return;
        }

        if (in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)
            && $subscription->current_period_end?->isPast()) {
            $start = $subscription->current_period_end;
            $end = $start->copy()->add(
                $subscription->billing_period === BillingPeriod::Annual ? '1 year' : '1 month',
            );

            $invoice = $this->invoiceService->createForSubscription($subscription, $start, $end);

            if ($invoice === null) {
                $subscription->update([
                    'current_period_start' => $start,
                    'current_period_end' => $end,
                ]);

                return;
            }

            $this->invoiceService->markOverdue($subscription);

            $subscription->update([
                'status' => SubscriptionStatus::PastDue,
                'grace_until' => $now->copy()->addDays((int) Setting::get('billing.grace_days', 7)),
            ]);

            AdminAuditLog::record('subscription.past_due', ['plan' => $subscription->plan->name], $subscription->tenant_id);

            return;
        }

        if ($subscription->status === SubscriptionStatus::PastDue
            && $subscription->grace_until?->lte(now()->subDays((int) Setting::get('tenant.archive_days', 30)))
            && $subscription->tenant->status === TenantStatus::Suspended) {
            $subscription->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $subscription->tenant->update(['status' => TenantStatus::Cancelled]);

            AdminAuditLog::record('tenant.archived', ['plan' => $subscription->plan->name], $subscription->tenant_id);

            return;
        }

        if ($subscription->status === SubscriptionStatus::PastDue && $subscription->grace_until?->isPast()) {
            $subscription->tenant->update(['status' => TenantStatus::Suspended]);

            AdminAuditLog::record('subscription.suspended', ['plan' => $subscription->plan->name], $subscription->tenant_id);
        }
    }

    /**
     * Relance du gérant J-7/J-3/J-1 avant l'échéance (Module 12) — une seule
     * fois par palier, tracé dans `reminders_sent`.
     */
    private function sendDueReminder(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::Active || ! $subscription->current_period_end) {
            return;
        }

        $daysLeft = (int) now()->startOfDay()->diffInDays($subscription->current_period_end->startOfDay());

        if (! in_array($daysLeft, [7, 3, 1], true) || in_array($daysLeft, $subscription->reminders_sent, true)) {
            return;
        }

        $email = $subscription->tenant->run(function (): ?string {
            $manager = User::whereHas('roles', fn ($query) => $query->where('name', TenantRolesSeeder::ROLE_MANAGER))->first();

            return $manager?->email;
        });

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new BillingReminderMail(
            managerName: Str::before($email, '@'),
            shopName: $subscription->tenant->name,
            planName: $subscription->plan->name,
            amount: $subscription->billing_period === BillingPeriod::Annual
                ? $subscription->plan->annual_price
                : $subscription->plan->monthly_price,
            dueAt: $subscription->current_period_end,
            daysLeft: $daysLeft,
        ));

        $subscription->update([
            'reminders_sent' => [...$subscription->reminders_sent, $daysLeft],
        ]);
    }
}
