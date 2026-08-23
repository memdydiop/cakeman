<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

class AdminPasskeyController extends Controller
{
    public function options(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $user = Auth::guard('admin')->user()
            ?? abort(403);

        $options = $generate($user);

        $request->session()->put('admin.passkey.registration_options', WebAuthn::toJson($options));

        return response()->json(['options' => WebAuthn::toBrowserArray($options)]);
    }

    public function store(Request $request, StorePasskey $storePasskey): JsonResponse
    {
        $user = Auth::guard('admin')->user()
            ?? abort(403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ]);

        $optionsJson = $request->session()->pull('admin.passkey.registration_options');

        if (! $optionsJson) {
            abort(422, 'Options manquantes.');
        }

        $options = WebAuthn::fromJson($optionsJson, PublicKeyCredentialCreationOptions::class);

        $publicKeyCredential = WebAuthn::fromJson(
            json_encode($request->input('credential')) ?: '{}',
            PublicKeyCredential::class,
        );

        $passkey = $storePasskey(
            $user,
            $request->string('name')->toString(),
            $publicKeyCredential,
            $options,
        );

        return response()->json(['id' => $passkey->id]);
    }

    public function destroy(Request $request, Passkey $passkey, DeletePasskey $deletePasskey): JsonResponse
    {
        $user = Auth::guard('admin')->user()
            ?? abort(403);

        if ((int) $passkey->getAttribute('admin_user_id') !== (int) $user->id) {
            abort(403);
        }

        $deletePasskey($user, $passkey);

        return response()->json(['deleted' => true]);
    }
}
