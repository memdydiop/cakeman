<?php

namespace App\Jobs;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\AdminAuditLog;
use App\Models\Subscription;
use App\Models\SubscriptionDunningAttempt;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDunning implements ShouldQueue
{
    use Queueable;

    /** @var list<int> jours de retry avant suspension */
    public const RETRY_DAYS = [1, 3, 7];

    public const SUSPEND_AFTER_DAYS = 14;

    public function handle(): void
    {
        $pastDue = Subscription::query()
            ->where('status', SubscriptionStatus::PastDue->value)
            ->whereNull('cancelled_at')
            ->with(['tenant', 'plan'])
            ->get();

        foreach ($pastDue as $subscription) {
            /** @var Tenant|null $tenant */
            $tenant = $subscription->tenant;
            if ($tenant === null) {
                continue;
            }

            $lastAttempt = SubscriptionDunningAttempt::where('subscription_id', $subscription->id)
                ->latest('attempt')
                ->first();

            $attemptNo = $lastAttempt ? $lastAttempt->attempt + 1 : 1;
            $daysSincePastDue = (int) $subscription->updated_at->diffInDays(now());

            // Déjà suspendu ?
            if ($tenant->status === TenantStatus::Suspended) {
                continue;
            }

            // Suspension après 14 j sans recouvrement
            if ($daysSincePastDue >= self::SUSPEND_AFTER_DAYS) {
                $tenant->update(['status' => TenantStatus::Suspended->value]);
                AdminAuditLog::record('subscription.suspended', ['subscription_id' => $subscription->id, 'reason' => 'dunning'], $tenant->id);
                SubscriptionDunningAttempt::create([
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $tenant->id,
                    'attempt' => $attemptNo,
                    'status' => 'suspended',
                    'last_error' => 'Suspended after '.self::SUSPEND_AFTER_DAYS.' days past_due',
                    'attempted_at' => now(),
                    'next_retry_at' => null,
                ]);
                Log::warning('Dunning suspend tenant', ['tenant' => $tenant->id, 'subscription' => $subscription->id]);

                continue;
            }

            // Ne retente que aux jours prévus
            $nextRetryDay = self::RETRY_DAYS[$attemptNo - 1] ?? null;
            if ($nextRetryDay !== null && $daysSincePastDue < $nextRetryDay) {
                continue;
            }

            // Simule une tentative de recouvrement (webhook Mobile Money rejoué)
            // En prod : appel gateway ; ici on log + enregistre l'échec
            $nextRetryAt = $nextRetryDay !== null ? $subscription->updated_at->addDays($nextRetryDay) : null;

            SubscriptionDunningAttempt::create([
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
                'attempt' => $attemptNo,
                'status' => 'failed',
                'last_error' => 'Past due — retry '.$attemptNo,
                'attempted_at' => now(),
                'next_retry_at' => $nextRetryAt,
            ]);

            AdminAuditLog::record('subscription.past_due', ['subscription_id' => $subscription->id, 'attempt' => $attemptNo], $tenant->id);
            Log::info('Dunning attempt', ['tenant' => $tenant->id, 'attempt' => $attemptNo]);
        }
    }
}
