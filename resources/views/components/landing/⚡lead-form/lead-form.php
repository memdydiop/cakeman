<?php

use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth', ['title' => 'Demande de démonstration'])] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $shopName = '';

    public string $notes = '';

    public bool $submitted = false;

    /**
     * Formulaire de la landing (CdC § 13, acquisition) : le prospect est
     * enregistré dans `landing_leads` avec dédoublonnage par e-mail et
     * normalisation du téléphone (+225 Côte d'Ivoire).
     */
    public function submit(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9 .-]{8,20}$/'],
            'shopName' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $email = strtolower(trim($this->email));

        if (Lead::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('Cet e-mail est déjà enregistré. Nous vous recontactons très vite.'),
            ]);
        }

        Lead::create([
            'name' => trim($this->name),
            'email' => $email,
            'phone' => filled($this->phone) ? Lead::normalizePhone($this->phone) : null,
            'shop_name' => filled($this->shopName) ? trim($this->shopName) : null,
            'source' => 'landing',
            'notes' => filled($this->notes) ? trim($this->notes) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $notificationEmail = Setting::get('acquisition.lead_notification_email', '');

        if ($notificationEmail !== '') {
            Mail::to((string) $notificationEmail)->send(new NewLeadMail(Lead::where('email', $email)->firstOrFail()));
        }

        $this->reset(['name', 'email', 'phone', 'shopName', 'notes']);

        $this->submitted = true;
    }
};
