<?php

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Database\Models\Domain;

Route::middleware('api_token')->prefix('v1')->group(function () {
    Route::get('/tenants', function (): JsonResponse {
        /** @var Collection<int, Tenant> $tenants */
        $tenants = Tenant::query()->orderByDesc('created_at')->get();

        $result = [];

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            $result[] = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status->value,
                'domains' => Domain::where('tenant_id', $tenant->id)->pluck('domain')->all(),
                'created_at' => $tenant->created_at,
            ];
        }

        return response()->json($result);
    });

    Route::get('/tenants/{tenant}', function (string $tenant): JsonResponse {
        /** @var Tenant $model */
        $model = Tenant::query()->findOrFail($tenant);

        return response()->json([
            'id' => $model->id,
            'name' => $model->name,
            'status' => $model->status->value,
            'domains' => Domain::where('tenant_id', $model->id)->pluck('domain')->all(),
            'created_at' => $model->created_at,
        ]);
    });

    Route::get('/invoices', function (): JsonResponse {
        $invoices = Invoice::with('subscription.tenant')->orderByDesc('created_at')->limit(50)->get();

        return response()->json($invoices->map(fn (Invoice $invoice): array => [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $invoice->amount,
            'status' => $invoice->status->value,
            'due_at' => $invoice->due_at,
            'paid_at' => $invoice->paid_at,
        ]));
    });

    Route::get('/subscriptions', function (): JsonResponse {
        $subs = Subscription::with('plan', 'tenant')->orderByDesc('created_at')->limit(50)->get();

        return response()->json($subs->map(fn (Subscription $subscription): array => [
            'id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'plan' => $subscription->plan->name ?? null,
            'status' => $subscription->status->value,
            'billing_period' => $subscription->billing_period->value ?? null,
            'coupon_id' => $subscription->coupon_id,
        ]));
    });
});
