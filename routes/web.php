<?php

use App\Http\Controllers\MobileMoneyWebhookController;
use App\Models\Plan;
use App\Models\PlanAddon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// Landing (Module 13) : la grille tarifaire est lue depuis `plans` et
// `plan_addons` — zéro duplication avec la console (CdC § 3).
Route::middleware('platform.maintenance')->group(function (): void {
    Route::get('/', function () {
        // Sur un sous-domaine tenant, la racine mène au tableau de bord de la
        // boutique (la landing n'est accessible que sur les domaines centraux).
        if (tenant() !== null) {
            return redirect()->route('dashboard');
        }

        return view('welcome', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'addons' => PlanAddon::where('is_active', true)->get(),
        ]);
    })->name('home');

    // Onboarding central : inscription d'un nouveau tenant (CdC § 2.5, plan-sprint0 § 5.6).
    Route::livewire('/register', 'onboarding.register-tenant')->name('register');

    // Pages annexes (Module 13) — landing statique, référencées depuis le footer.
    Route::view('/faq', 'pages.faq')->name('faq');

    Route::view('/confidentialite', 'pages.privacy')->name('privacy');

    Route::view('/conditions-utilisation', 'pages.terms')->name('terms');
});

// Webhook de confirmation des paiements d'abonnement (CdC § 3, Module 12) —
// public, hors CSRF (signature HMAC vérifiée dans le contrôleur).
Route::post('/webhooks/mobile-money', MobileMoneyWebhookController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('webhooks.mobile-money');

// Sitemap XML (Module 13, SEO) — routes publiques de la landing.
Route::get('/sitemap.xml', function () {
    $urls = [
        route('home'),
        route('register'),
        route('faq'),
        route('privacy'),
        route('terms'),
        route('admin.login'),
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";

    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

    foreach ($urls as $url) {
        $xml .= '  <url><loc>'.e($url)."</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
    }

    $xml .= '</urlset>';

    return response($xml)->header('Content-Type', 'application/xml');
})->name('sitemap');

require __DIR__.'/admin.php';
