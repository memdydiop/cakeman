<?php

namespace App\Concerns;

use App\Models\PlanFeature;
use Illuminate\Support\Facades\Cache;

trait HasPlanFeatures
{
    /**
     * @return array<string, mixed>
     */
    public function planFeatures(): array
    {
        $subscription = $this->subscription ?? null;
        if (! $subscription || ! $subscription->plan_id) {
            return [];
        }

        $key = 'tenant:'.$this->id.':features';
        $loader = function () use ($subscription): array {
            return PlanFeature::where('plan_id', $subscription->plan_id)
                ->pluck('value', 'key')
                ->all();
        };

        // En contexte tenant, le CacheTenancyBootstrapper tague tout le cache
        // or file/database ne supportent pas les tags → passer par le contexte central
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return tenancy()->central(fn () => Cache::remember($key, 300, $loader));
            }

            return Cache::remember($key, 300, $loader);
        } catch (\BadMethodCallException $e) {
            // store sans tags
            return $loader();
        }
    }

    public function hasFeature(string $key): bool
    {
        $features = $this->planFeatures();
        $value = $features[$key] ?? null;

        if ($value === null) {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value) && array_key_exists('enabled', $value)) {
            return (bool) $value['enabled'];
        }

        return (bool) $value;
    }

    public function featureLimit(string $key): ?int
    {
        $features = $this->planFeatures();
        $value = $features[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_array($value) && isset($value['max'])) {
            return (int) $value['max'];
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    public function featureRemaining(string $key, int $used): ?int
    {
        $limit = $this->featureLimit($key);

        return $limit === null ? null : max(0, $limit - $used);
    }
}
