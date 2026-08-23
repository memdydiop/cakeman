<?php

namespace App\Http\Controllers;

use App\Models\AdminWebhookLog;
use App\Models\Setting;
use App\Services\SubscriptionPaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint public de confirmation des paiements Mobile Money (CdC § 3,
 * Module 12) : signature HMAC vérifiée, idempotence via `provider_ref`,
 * chaque webhook journalisé dans `admin_webhook_logs`.
 */
class MobileMoneyWebhookController extends Controller
{
    public function __construct(private readonly SubscriptionPaymentProcessor $processor) {}

    public function __invoke(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $provider = (string) ($payload['provider'] ?? config('mobile-money.provider', 'wave'));
        $event = (string) ($payload['event'] ?? '');
        $providerRef = (string) ($payload['provider_ref'] ?? '');

        $signatureValid = $this->verifySignature($request, $raw);

        if (! $signatureValid) {
            AdminWebhookLog::create([
                'provider' => $provider,
                'event' => $event,
                'provider_ref' => $providerRef === '' ? null : $providerRef,
                'payload' => $payload,
                'signature_valid' => false,
                'status' => 'failed',
                'error' => 'Signature invalide',
                'processed_at' => now(),
            ]);

            return response()->json(['error' => 'invalid_signature'], 401);
        }

        if ($providerRef === '') {
            AdminWebhookLog::create([
                'provider' => $provider,
                'event' => $event,
                'payload' => $payload,
                'signature_valid' => true,
                'status' => 'failed',
                'error' => 'provider_ref manquant',
                'processed_at' => now(),
            ]);

            return response()->json(['error' => 'missing_provider_ref'], 422);
        }

        $log = $this->processor->process($provider, $event, $providerRef, $payload, true);

        Log::channel('stack')->info('Webhook Mobile Money traité', [
            'provider' => $provider,
            'event' => $event,
            'provider_ref' => $providerRef,
            'status' => $log->status,
        ]);

        return response()->json(['status' => $log->status], 200);
    }

    private function verifySignature(Request $request, string $raw): bool
    {
        $secret = (string) Setting::get('billing.webhook_secret', config('mobile-money.webhook_secret'));
        $sandbox = Setting::get('billing.sandbox_mode', config('mobile-money.sandbox') ? '1' : '0') === '1';

        if ($secret === '' && $sandbox) {
            return true;
        }

        $received = (string) $request->header('X-Cakeman-Signature', '');

        $expected = hash_hmac('sha256', $raw, $secret);

        return hash_equals($expected, $received);
    }
}
