<?php

use App\Enums\AdminPermission;
use App\Models\AdminUser;
use Database\Seeders\AdminRolesSeeder;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.admin')] #[Title('Rôles')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $name = '';

    /** @var list<string> */
    public array $permissions = [];

    public ?int $editingId = null;

    public string $editName = '';

    /** @var list<string> */
    public array $editPermissions = [];

    public function mount(): void
    {
        abort_unless(Auth::guard('admin')->user()?->hasPermissionTo(AdminPermission::RoleManage->value, 'admin'), 403);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        $roles = Role::withCount('permissions')
            ->where('guard_name', 'admin')
            ->when($this->search, fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage);

        $roleCounts = AdminUser::query()
            ->with('roles:id,name,guard_name')
            ->get(['id'])
            ->flatMap(fn (AdminUser $user): Collection => $user->roles->pluck('name'))
            ->countBy();

        foreach ($roles as $role) {
            $role->users_count = $roleCounts->get($role->name, 0);
        }

        return $roles;
    }

    /**
     * Permissions groupées par domaine (Module 12) pour les formulaires.
     *
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

    public function createRole(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if (Role::where('name', $this->name)->where('guard_name', 'admin')->exists()) {
            throw ValidationException::withMessages([
                'name' => __('Ce rôle existe déjà.'),
            ]);
        }

        $role = Role::create(['name' => $this->name, 'guard_name' => 'admin']);
        $role->syncPermissions($this->permissions);

        $this->reset(['name', 'permissions']);

        Flux::modal('create-role')->close();
    }

    public function startEditing(int $id): void
    {
        $role = Role::with('permissions')->where('guard_name', 'admin')->findOrFail($id);

        $this->editingId = $id;
        $this->editName = $role->name;
        $this->editPermissions = $role->permissions->pluck('name')->all();

        Flux::modal('edit-role')->show();
    }

    public function saveEditing(): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
        ]);

        $role = Role::where('guard_name', 'admin')->findOrFail($this->editingId);

        $duplicate = Role::where('name', $this->editName)
            ->where('guard_name', 'admin')
            ->whereKeyNot($role->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'editName' => __('Ce rôle existe déjà.'),
            ]);
        }

        $role->update(['name' => $this->editName]);

        $role->syncPermissions($this->editPermissions);

        $this->reset(['editingId', 'editName', 'editPermissions']);

        Flux::modal('edit-role')->close();
    }

    public function deleteRole(int $id): void
    {
        $role = Role::where('guard_name', 'admin')->findOrFail($id);

        if ($role->name === AdminRolesSeeder::ROLE_SUPER_ADMIN) {
            throw ValidationException::withMessages([
                'role' => __('Le rôle Super-Admin ne peut pas être supprimé.'),
            ]);
        }

        $role->delete();

        $this->reset(['editingId', 'editName', 'editPermissions']);
    }
};
