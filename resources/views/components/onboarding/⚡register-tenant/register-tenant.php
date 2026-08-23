<?php

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Mail\ManagerInvitationMail;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantUserInvitation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stancl\Tenancy\Database\Models\Domain;

new #[Layout('layouts.auth', ['title' => 'Créer ma boutique'])] #[Title('Créer ma boutique')] class extends Component
{
    public string $businessName = '';

    public string $subdomain = '';

    public string $managerName = '';

    public string $email = '';

    public ?string $planCode = null;

    public function mount(?string $plan = null): void
    {
        $planCode = $plan ?? request()->query('plan');

        $this->planCode = is_string($planCode) && Plan::where('code', $planCode)->exists() ? $planCode : null;
    }

    /**
     * Onboarding central (CdC § 2.5, plan-sprint0 § 5.6) : création du tenant
     * en attente de gérant + domaine, puis envoi du lien d'activation 48 h
     * (même mécanique que la console). Pas de mot de passe à l'inscription,
     * pas de SSO au S0. Plan choisi sur la landing (CdC § 13) : Gratuit par
     * défaut, essai 14 jours sans carte pour les plans payants.
     */
    public function register(): void
    {
        $this->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/'],
            'managerName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'planCode' => ['nullable', 'exists:plans,code'],
        ]);

        if (Domain::where('domain', $this->subdomain.'.'.config('tenancy.root_domain'))->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => __('Ce sous-domaine est déjà utilisé.'),
            ]);
        }

        if (Tenant::managerEmailExists($this->email)) {
            throw ValidationException::withMessages([
                'email' => __('Cet e-mail est déjà utilisé.'),
            ]);
        }

        try {
            $tenant = Tenant::create([
                'name' => $this->businessName,
                'status' => TenantStatus::Pending,
            ]);

            $tenant->domains()->create(['domain' => $this->subdomain.'.'.config('tenancy.root_domain')]);

            $this->convertMatchingLead($tenant);

            if ($this->planCode !== null && $this->planCode !== 'free') {
                $plan = Plan::where('code', $this->planCode)->firstOrFail();

                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'billing_period' => BillingPeriod::Monthly,
                    'status' => SubscriptionStatus::Trialing,
                    'trial_ends_at' => now()->addDays((int) Setting::get('tenant.trial_days', 14)),
                    'current_period_start' => now()->addDays((int) Setting::get('tenant.trial_days', 14)),
                    'current_period_end' => now()->addDays((int) Setting::get('tenant.trial_days', 14))->addMonth(),
                ]);
            }

            $tenant->run(function (): void {
                $invitation = TenantUserInvitation::create([
                    'name' => $this->managerName,
                    'email' => $this->email,
                    'token' => Str::random(64),
                    'expires_at' => now()->addHours((int) Setting::get('security.invitation_expires_hours', TenantUserInvitation::EXPIRES_IN_HOURS)),
                ]);

                Mail::to($this->email)->send(new ManagerInvitationMail(
                    managerName: $this->managerName,
                    shopName: $this->businessName,
                    acceptUrl: Tenant::url($this->subdomain, 'tenant-invitations/'.$invitation->token),
                    expiresAt: $invitation->expires_at,
                ));
            });
        } catch (ValidationException $e) {
            if (isset($tenant)) {
                $tenant->delete();
            }

            throw $e;
        }

        $this->redirect(Tenant::url($this->subdomain, 'activation-sent'));
    }

    /**
     * Conversion automatique du lead (CdC § 13) : si l'e-mail du gérant
     * correspond à un prospect enregistré, il passe à `converted` et est
     * rattaché à la nouvelle boutique.
     */
    private function convertMatchingLead(Tenant $tenant): void
    {
        $lead = Lead::where('email', strtolower($this->email))
            ->where('status', '!=', 'converted')
            ->latest()
            ->first();

        if ($lead !== null) {
            $lead->update([
                'status' => 'converted',
                'converted_tenant_id' => $tenant->id,
            ]);
        }
    }

    public function selectedPlanName(): ?string
    {
        if ($this->planCode === null || $this->planCode === 'free') {
            return null;
        }

        return Plan::where('code', $this->planCode)->value('name');
    }
};
