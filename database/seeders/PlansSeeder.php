<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanAddon;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

/**
 * Plans & add-ons de la plateforme (Module 12, CdC § 3) : grille FCFA
 * Gratuit / Starter / Pro / Business + add-ons à la carte. Idempotent.
 */
class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'free',
                'name' => 'Gratuit',
                'monthly_price' => 0,
                'annual_price' => 0,
                'limits' => ['users' => 1, 'orders_per_month' => 15, 'storage_gb' => 1],
                'features' => [
                    '1 utilisateur',
                    'Modules de base (devis, caisse, clients, stocks)',
                    '15 commandes/mois',
                    '1 Go de stockage',
                    'Marque Cakeman',
                    'Support communautaire WhatsApp',
                ],
                'sort_order' => 10,
            ],
            [
                'code' => 'starter',
                'name' => 'Starter',
                'monthly_price' => 5000,
                'annual_price' => 48000,
                'limits' => ['users' => 3, 'orders_per_month' => 60, 'storage_gb' => 10],
                'features' => [
                    'Tout le périmètre v1',
                    '3 utilisateurs',
                    '60 commandes/mois',
                    '10 Go de stockage',
                    'Sans marque Cakeman',
                    'Support e-mail/WhatsApp',
                ],
                'sort_order' => 20,
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'monthly_price' => 12500,
                'annual_price' => 120000,
                'limits' => ['users' => 10, 'orders_per_month' => null, 'storage_gb' => 50],
                'features' => [
                    '10 utilisateurs',
                    'Commandes illimitées',
                    '50 Go de stockage',
                    'Rappels WhatsApp avancés',
                    'API & exports illimités',
                    'Support prioritaire 24 h',
                ],
                'sort_order' => 30,
            ],
            [
                'code' => 'business',
                'name' => 'Business',
                'monthly_price' => 25000,
                'annual_price' => 240000,
                'limits' => ['users' => null, 'orders_per_month' => null, 'storage_gb' => null],
                'features' => [
                    'Utilisateurs illimités',
                    'Stockage illimité',
                    'SLA 99,9 %',
                    'SSO (v2)',
                    'Gestionnaire de compte',
                ],
                'sort_order' => 40,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['code' => $plan['code']], $plan);
        }

        // Feature flags YNEX (P1) — quotas et toggles par plan
        $featureMatrix = [
            'free' => [
                'products.max' => 30,
                'users.max' => 1,
                'orders.max' => 15,
                'recipes.enabled' => false,
                'pos.enabled' => false,
                'api.enabled' => false,
            ],
            'starter' => [
                'products.max' => 300,
                'users.max' => 3,
                'orders.max' => 60,
                'recipes.enabled' => true,
                'pos.enabled' => true,
                'api.enabled' => false,
            ],
            'pro' => [
                'products.max' => null,
                'users.max' => 10,
                'orders.max' => null,
                'recipes.enabled' => true,
                'pos.enabled' => true,
                'api.enabled' => true,
            ],
            'business' => [
                'products.max' => null,
                'users.max' => null,
                'orders.max' => null,
                'recipes.enabled' => true,
                'pos.enabled' => true,
                'api.enabled' => true,
            ],
        ];

        foreach ($featureMatrix as $code => $features) {
            $plan = Plan::where('code', $code)->first();
            if (! $plan) {
                continue;
            }
            foreach ($features as $key => $value) {
                PlanFeature::updateOrCreate(
                    ['plan_id' => $plan->id, 'key' => $key],
                    ['value' => $value],
                );
            }
        }

        $addons = [
            ['code' => 'extra_user', 'name' => 'Utilisateur additionnel', 'monthly_price' => 1500, 'unit' => 'user'],
            ['code' => 'extra_storage', 'name' => 'Stockage additionnel', 'monthly_price' => 2000, 'unit' => '10gb'],
            ['code' => 'extra_whatsapp', 'name' => 'Rappels WhatsApp', 'monthly_price' => 3000, 'unit' => '500_msg'],
            ['code' => 'priority_support', 'name' => 'Support prioritaire', 'monthly_price' => 15000, 'unit' => 'month'],
        ];

        foreach ($addons as $addon) {
            PlanAddon::updateOrCreate(['code' => $addon['code']], $addon);
        }
    }
}
