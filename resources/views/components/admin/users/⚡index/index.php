<?php

use App\Enums\AdminPermission;
use App\Mail\AdminInvitationMail;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\AdminUserInvitation;
use App\Models\Setting;
use Database\Seeders\AdminRolesSeeder;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts.admin')] #[Title('Équipe')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    /** @var array{role: string, status: string, two_factor: string} */
    public array $filters = [
        'role' => 'all',
        'status' => 'all',
        'two_factor' => 'all',
    ];

    // Formulaire d'invitation (modale).
    public string $inviteEmail = '';

    public string $inviteRole = AdminRolesSeeder::ROLE_SUPPORT;

    // Formulaire d'édition (modale).
    public ?int $editingId = null;

    /** @var list<string> */
    public array $editRoles = [];

    /** @var list<string> */
    public array $editPermissions = [];

    public bool $editIsActive = true;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, list<AdminPermission>>
     */
    public function permissionGroups(): array
    {
        return AdminPermission::groups();
    }

    public function permissionLabel(AdminPermission $permission): string
    {
        return $permission->label();
    }

    #[Computed]
    public function adminUsers(): LengthAwarePaginator
    {
        return AdminUser::query()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('email', 'ilike', '%'.$this->search.'%');
            }))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where(
                'is_active',
                $this->filters['status'] === 'active',
            ))
            ->when($this->filters['two_factor'] === 'enabled', fn ($query) => $query->whereNotNull('two_factor_confirmed_at'))
            ->when($this->filters['two_factor'] === 'disabled', fn ($query) => $query->whereNull('two_factor_confirmed_at'))
            ->when($this->filters['role'] !== 'all', fn ($query) => $query->role($this->filters['role'], 'admin'))
            ->with(['roles', 'passkeys'])
            ->withCount('passkeys')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function invitations(): Collection
    {
        return AdminUserInvitation::query()
            ->orderByDesc('created_at')
            ->get();
    }

    public function roleOptions(): array
    {
        return Role::where('guard_name', 'admin')->orderBy('name')->pluck('name')->all();
    }

    /**
     * Export CSV des utilisateurs (RGPD / portabilité, CdC § 12) : le filtre
     * courant est appliqué, la recherche aussi.
     */
    public function exportCsv(): StreamedResponse
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $users = AdminUser::query()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('email', 'ilike', '%'.$this->search.'%');
            }))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where(
                'is_active',
                $this->filters['status'] === 'active',
            ))
            ->when($this->filters['two_factor'] === 'enabled', fn ($query) => $query->whereNotNull('two_factor_confirmed_at'))
            ->when($this->filters['two_factor'] === 'disabled', fn ($query) => $query->whereNull('two_factor_confirmed_at'))
            ->when($this->filters['role'] !== 'all', fn ($query) => $query->role($this->filters['role'], 'admin'))
            ->with('roles')
            ->orderByDesc('created_at')
            ->get();

        AdminAuditLog::record('admin_user.exported', ['count' => $users->count()]);

        $filename = 'utilisateurs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($users): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Nom', 'E-mail', 'Rôles', 'Statut', '2FA', 'Dernière connexion', 'Créé le'], separator: ';');

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->roles->pluck('name')->join(', '),
                    $user->is_active ? 'Actif' : 'Désactivé',
                    $user->two_factor_confirmed_at !== null ? 'Activée' : 'Non',
                    $user->last_login_at?->format('d/m/Y H:i') ?? '—',
                    $user->created_at?->format('d/m/Y H:i'),
                ], separator: ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);
    }

    /**
     * Invitation d'un utilisateur console (CdC § 12, plan-sprint0 § 5.8) :
     * compte créé inactif + lien one-time 48 h envoyé par e-mail.
     */
    public function invite(): void
    {
        $this->validate([
            'inviteEmail' => ['required', 'email', 'max:255', 'unique:admin_users,email'],
            'inviteRole' => ['required', 'in:'.implode(',', $this->roleOptions())],
        ]);

        $admin = AdminUser::create([
            'name' => Str::headline(Str::before($this->inviteEmail, '@')),
            'email' => $this->inviteEmail,
            'password' => Str::password(24),
            'is_active' => false,
        ]);
        $admin->assignRole($this->inviteRole);

        $invitation = AdminUserInvitation::create([
            'email' => $this->inviteEmail,
            'role' => $this->inviteRole,
            'token' => Str::random(64),
            'expires_at' => now()->addHours((int) Setting::get('security.invitation_expires_hours', AdminUserInvitation::EXPIRES_IN_HOURS)),
        ]);

        Mail::to($this->inviteEmail)->send(new AdminInvitationMail($invitation));

        AdminAuditLog::record('admin_user.invited', ['email' => $this->inviteEmail, 'role' => $this->inviteRole]);

        $this->reset(['inviteEmail', 'inviteRole']);

        $this->modal('invite-user')->close();
    }

    public function startEditing(int $id): void
    {
        $admin = AdminUser::with(['roles', 'permissions'])->findOrFail($id);

        $this->editingId = $admin->id;
        $this->editRoles = $admin->roles->pluck('name')->all();
        $this->editPermissions = $admin->permissions->pluck('name')->all();
        $this->editIsActive = $admin->is_active;

        Flux::modal('edit-user')->show();
    }

    /**
     * Édition d'un utilisateur (rôles multiples, permissions directes,
     * activation/désactivation) — la désactivation révoque les sessions
     * actives (CdC § 12).
     */
    public function saveEditing(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $this->validate([
            'editRoles' => ['required', 'array', 'min:1'],
            'editRoles.*' => ['in:'.implode(',', $this->roleOptions())],
            'editPermissions' => ['array'],
            'editPermissions.*' => ['in:'.implode(',', AdminPermission::values())],
            'editIsActive' => ['boolean'],
        ]);

        $admin = AdminUser::findOrFail($this->editingId);

        if ($admin->id === auth('admin')->id() && ! $this->editIsActive) {
            throw ValidationException::withMessages([
                'editIsActive' => __('Vous ne pouvez pas désactiver votre propre compte.'),
            ]);
        }

        DB::transaction(function () use ($admin) {
            $wasActive = $admin->is_active;

            $admin->update(['is_active' => $this->editIsActive]);
            $admin->syncRoles($this->editRoles);
            $admin->syncPermissions($this->editPermissions);

            if ($wasActive && ! $this->editIsActive) {
                DB::table('sessions')->where('user_id', $admin->id)->delete();
            }
        });

        AdminAuditLog::record(
            'admin_user.updated',
            ['roles' => $this->editRoles, 'permissions' => $this->editPermissions, 'is_active' => $this->editIsActive],
        );

        $this->reset(['editingId', 'editRoles', 'editPermissions', 'editIsActive']);

        $this->modal('edit-user')->close();
    }

    /**
     * Suppression d'un utilisateur console : audit conservé (FK nullOnDelete)
     * et interdiction de supprimer son propre compte.
     */
    /**
     * Suppression d'un utilisateur (RGPD / nettoyage) : réservée aux comptes
     * désactivés — un compte actif doit d'abord être désactivé. Sessions et
     * invitations en attente purgées, audit conservé.
     */
    public function deleteUser(int $id): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $admin = AdminUser::findOrFail($id);

        if ($admin->id === auth('admin')->id()) {
            throw ValidationException::withMessages([
                'user' => __('Vous ne pouvez pas supprimer votre propre compte.'),
            ]);
        }

        if ($admin->is_active) {
            throw ValidationException::withMessages([
                'user' => __('Un compte actif ne peut pas être supprimé — désactivez-le d\'abord.'),
            ]);
        }

        DB::transaction(function () use ($admin): void {
            DB::table('sessions')->where('user_id', $admin->id)->delete();
            AdminUserInvitation::where('email', $admin->email)->whereNull('accepted_at')->delete();

            AdminAuditLog::record('admin_user.deleted', ['email' => $admin->email]);

            $admin->delete();
        });

        Flux::toast(__('Utilisateur supprimé.'));
    }

    /**
     * Renvoi d'une invitation console : nouveau token, nouvelle expiration
     * 48 h et e-mail renvoyé (lien one-time, plan-sprint0 § 5.8).
     */
    public function resendInvitation(int $id): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $invitation = AdminUserInvitation::whereNull('accepted_at')->find($id);

        abort_unless($invitation, 404);

        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addHours((int) Setting::get('security.invitation_expires_hours', AdminUserInvitation::EXPIRES_IN_HOURS)),
        ]);

        Mail::to($invitation->email)->send(new AdminInvitationMail($invitation));

        AdminAuditLog::record('admin_user.invitation_resent', ['email' => $invitation->email, 'role' => $invitation->role]);

        Flux::toast(__('Invitation renvoyée.'));
    }

    /**
     * Révocation d'une invitation console : l'invitation et le compte
     * inactif associé sont supprimés.
     */
    public function revokeInvitation(int $id): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $invitation = AdminUserInvitation::whereNull('accepted_at')->find($id);

        abort_unless($invitation, 404);

        AdminUser::where('email', $invitation->email)->where('is_active', false)->delete();

        AdminAuditLog::record('admin_user.invitation_revoked', ['email' => $invitation->email, 'role' => $invitation->role]);

        $invitation->delete();

        Flux::toast(__('Invitation révoquée.'));
    }
};
