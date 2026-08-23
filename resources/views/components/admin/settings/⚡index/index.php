<?php

use App\Enums\AdminPermission;
use App\Mail\TwoFactorRequiredMail;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('Configuration')] class extends Component
{
    public string $activeTab = 'general';

    /** @var array<string, mixed> */
    public array $general = [];

    /** @var array<string, mixed> */
    public array $billing = [];

    /** @var array<string, mixed> */
    public array $acquisition = [];

    /** @var array<string, mixed> */
    public array $security = [];

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SettingsManage->value, 'admin'), 403);

        $this->general = [
            'name' => (string) Setting::get('platform.name', config('app.name')),
            'supportEmail' => (string) Setting::get('platform.support_email', ''),
            'maintenanceEnabled' => Setting::get('platform.maintenance_enabled', false) === '1',
            'maintenanceMessage' => (string) Setting::get('platform.maintenance_message', ''),
            'trialDays' => (int) Setting::get('tenant.trial_days', 14),
            'archiveDays' => (int) Setting::get('tenant.archive_days', 30),
            'maintenanceHours' => (int) Setting::get('tenant.maintenance_hours', 2),
        ];

        $this->billing = [
            'graceDays' => (int) Setting::get('billing.grace_days', 7),
            'sandboxMode' => Setting::get('billing.sandbox_mode', config('mobile-money.sandbox')) === '1',
            'webhookSecret' => (string) Setting::get('billing.webhook_secret', config('mobile-money.webhook_secret')),
        ];

        $this->acquisition = [
            'leadNotificationEmail' => (string) Setting::get('acquisition.lead_notification_email', ''),
        ];

        $this->security = [
            'invitationExpiresHours' => (int) Setting::get('security.invitation_expires_hours', 48),
            'twoFactorRequired' => Setting::get('security.two_factor_required', false) === '1',
        ];
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'general.name' => ['required', 'string', 'max:191'],
            'general.supportEmail' => ['nullable', 'email', 'max:191'],
            'general.maintenanceMessage' => ['nullable', 'string', 'max:2000'],
            'general.trialDays' => ['required', 'integer', 'between:1,90'],
            'general.archiveDays' => ['required', 'integer', 'between:1,180'],
            'general.maintenanceHours' => ['required', 'integer', 'between:1,24'],
        ]);

        Setting::setMany([
            'platform.name' => $this->general['name'],
            'platform.support_email' => $this->general['supportEmail'],
            'platform.maintenance_enabled' => $this->general['maintenanceEnabled'] ? '1' : '0',
            'platform.maintenance_message' => $this->general['maintenanceMessage'],
            'tenant.trial_days' => $this->general['trialDays'],
            'tenant.archive_days' => $this->general['archiveDays'],
            'tenant.maintenance_hours' => $this->general['maintenanceHours'],
        ]);

        $this->record('général');
    }

    public function saveBilling(): void
    {
        $this->validate([
            'billing.graceDays' => ['required', 'integer', 'between:1,30'],
            'billing.webhookSecret' => ['nullable', 'string', 'max:191'],
        ]);

        Setting::setMany([
            'billing.grace_days' => $this->billing['graceDays'],
            'billing.sandbox_mode' => $this->billing['sandboxMode'] ? '1' : '0',
            'billing.webhook_secret' => $this->billing['webhookSecret'],
        ]);

        $this->record('facturation');
    }

    public function saveAcquisition(): void
    {
        $this->validate([
            'acquisition.leadNotificationEmail' => ['nullable', 'email', 'max:191'],
        ]);

        Setting::setMany([
            'acquisition.lead_notification_email' => $this->acquisition['leadNotificationEmail'],
        ]);

        $this->record('acquisition');
    }

    public function saveSecurity(): void
    {
        $this->validate([
            'security.invitationExpiresHours' => ['required', 'integer', 'between:1,168'],
        ]);

        $wasRequired = Setting::get('security.two_factor_required', false) === '1';
        $nowRequired = (bool) $this->security['twoFactorRequired'];

        Setting::setMany([
            'security.invitation_expires_hours' => $this->security['invitationExpiresHours'],
            'security.two_factor_required' => $nowRequired ? '1' : '0',
        ]);

        if ($nowRequired && ! $wasRequired) {
            AdminUser::where('is_active', true)
                ->whereNull('two_factor_confirmed_at')
                ->each(function (AdminUser $user): void {
                    Mail::to($user->email)->queue(new TwoFactorRequiredMail);
                });
        }

        $this->record('sécurité');
    }

    private function record(string $section): void
    {
        AdminAuditLog::record('settings.updated', ['section' => $section]);

        Flux::toast(__('Configuration « :section » enregistrée.', ['section' => $section]));
    }
};
