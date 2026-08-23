<?php

use App\Enums\TenantStatus;
use App\Mail\ManagerInvitationMail;
use App\Models\AdminAuditLog;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantUserInvitation;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Stancl\Tenancy\Database\Models\Domain;

new class extends Component
{
    public string $name = '';

    public string $subdomain = '';

    public string $managerName = '';

    public string $managerEmail = '';

    /**
     * Création manuelle d'un tenant (console, plan-sprint0 § 5.5) : plan free,
     * statut en attente de gérant, seed RBAC via le pipeline TenantCreated,
     * puis invitation du gérant (lien one-time 48 h côté tenant).
     */
    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/'],
            'managerName' => ['required', 'string', 'max:255'],
            'managerEmail' => ['required', 'email', 'max:255'],
        ]);

        if (Domain::where('domain', $this->subdomain.'.'.config('tenancy.root_domain'))->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => __('Ce sous-domaine est déjà utilisé.'),
            ]);
        }

        if (Tenant::managerEmailExists($this->managerEmail)) {
            throw ValidationException::withMessages([
                'managerEmail' => __('Cet e-mail est déjà utilisé.'),
            ]);
        }

        $tenant = Tenant::create([
            'name' => $this->name,
            'status' => TenantStatus::Pending,
        ]);

        $tenant->domains()->create(['domain' => $this->subdomain.'.'.config('tenancy.root_domain')]);

        $tenant->run(function (): void {
            $invitation = TenantUserInvitation::create([
                'name' => $this->managerName,
                'email' => $this->managerEmail,
                'token' => Str::random(64),
                'expires_at' => now()->addHours((int) Setting::get('security.invitation_expires_hours', TenantUserInvitation::EXPIRES_IN_HOURS)),
            ]);

            Mail::to($this->managerEmail)->send(new ManagerInvitationMail(
                managerName: $this->managerName,
                shopName: $this->name,
                acceptUrl: Tenant::url($this->subdomain, 'tenant-invitations/'.$invitation->token),
                expiresAt: $invitation->expires_at,
            ));
        });

        AdminAuditLog::record('tenant.created', [
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'manager_email' => $this->managerEmail,
        ], $tenant->id);

        $this->reset(['name', 'subdomain', 'managerName', 'managerEmail']);

        $this->dispatch('tenant-created');

        Flux::modal('create-tenant')->close();
    }
};
