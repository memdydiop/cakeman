# Cahier des Charges — Cakeman
## La gestion pâtissière simplifiée

**Version :** 1.9 — Révisée  
**Statut :** Validé pour développement  
**Date :** Août 2026  
**Marché cible :** Côte d'Ivoire & Afrique de l'Ouest (SaaS B2B)

### Journal des modifications v1.9 (revue de cohérence)

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 4.2, § 4.4 | `invoices.payment_link_id` (FK cross-schéma central → tenant, impossible) supprimée → `payment_url` stocké sur la facture ; idempotence assurée par `provider_ref` UNIQUE |
| 2 | § 4.3, § 6.B | `production_slots.reserved_count` → `reserved_complex_count` (résidu v1.1) |
| 3 | § 4.3 | Enum `inventory_movements.type` : `write_off` retiré (doublon sémantique de `loss`, sans flux dans M7) |
| 4 | § 4.2, § 4.4, § 4.5, M12 | Nouvelle table centrale `admin_webhook_logs` — journalisation des webhooks de paiement d'abonnements (billing) sur le domaine central |
| 5 | § 4.2 | Note explicite : pendant `past_due` (grâce), le tenant reste `active` mais **lecture seule** |
| 6 | § 7 | « 9 sprints » → **10 sprints** (Sprint 0-9) ; jalons M1→M5 recalés (S+8 / S+10 / S+14 / S+16 / S+20) |
| 7 | § 5.2 | Taille max des images unifiée à **1 200 px** (alignement § 6.A) |
| 8 | M2 | Nouvelle section **2.8 Grille de prix** (`price_lists`/`price_list_rules`) + onglet Tarifs |

### Journal des modifications v1.8

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 3, § 4.2, § 4.4, § 4.5, § 7 | **Ajout du Module 13 — Site Public & Acquisition (Landing)** : landing + pages tarifs (lues depuis `plans`/`plan_addons`), formulaire de pré-inscription (`landing_leads`, dédoublonnage +225, suivi `new → contacted → converted`), SEO, rapport d'acquisition console ; sprint 0 (landing statique + leads) et sprint 9 (complète) |

### Journal des modifications v1.7

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 3 M12, § 4.2, § 4.4, § 4.5, § 6.E, § 7 | **Modèle d'abonnement inspiré de Deelo.ai** : freemium + 4 paliers en FCFA (Gratuit / Starter 5 000 / Pro 12 500 / Business 25 000 FCFA), add-ons à la carte (`plan_addons`/`subscription_addons`), essai 14 jours sans carte, remise annuelle -20 %, **encaissement Mobile Money des abonnements en v1** (invoices + liens de paiement MM + webhooks idempotents, même mécanique que le Module 9), rappels J-7/J-3/J-1, période de grâce 7 j en lecture seule → suspension → archivage, enforcement des limites par plan. § 6.E recadré (SSO/SCIM, carte bancaire → v2) |

### Journal des modifications v1.6

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 3 M12, § 2.3, § 4.2, § 5.1, § 7 | **Gestion des utilisateurs du backoffice ajoutée** (absente du v1.5) : invitation e-mail, activation/désactivation immédiate, attribution de rôles, rôles personnalisés — RBAC spatie/laravel-permission dans le schéma central `public` (permissions enum `admin.*`, rôles racines `Super-Admin`/`Support`/`Billing`), `admin_users` étendue (`is_active`, `last_login_at`), page `Plateforme > Équipe`, sprints 0/9 alignés |

### Journal des modifications v1.5

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 2.5 | **Syntaxe Flux corrigée** : composants écrits `<flux:modal>` (v2, sans préfixe `x-`) et non `<x-flux::modal>` ; le slide-over est le **flyout** `<flux:modal flyout>` (position `right` par défaut) et non `position="right"` ; largeur via `md:w-*` (les variantes `medium`/`wide` de la v1.4 n'existent pas en v2 — variantes réelles : `default`, `floating`, `bare`) ; note de syntaxe ajoutée (§ 2.5) |

### Journal des modifications v1.4

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 2.5, § 3, § 7 | **Règle UX impérative** : tous les formulaires de création/édition passent par des **modales Flux** (`<x-flux::modal>`, variantes `medium`/`wide`) — plus aucun drawer de saisie ; les drawers (`position="right"`) sont réservés à la **consultation** (détail commande, fiche client, historiques). Surfaces UI des modules et livrables des sprints alignés |

### Journal des modifications v1.3

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 2.2, § 4.2, M12 | `admin_users` alignée sur la migration `users` de Laravel/Fortify (mêmes colonnes que la table des tenants : `email_verified_at`, `remember_token`, 2FA Fortify) + mêmes tables annexes (`password_reset_tokens`, `sessions`, `passkeys`) — la même stack d'authentification sert la plateforme et les tenants |

### Journal des modifications v1.2

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 3, § 4, § 7 | **Ajout du Module 12 — Plateforme d'Administration des Tenants (Super-Admin)**, jusque-là mentionnée sans périmètre : console sur domaine central, cycle de vie des tenants, sessions support en lecture seule journalisées, socle billing (`plans`/`subscriptions`), monitoring & audit — avec tables centrales, sprints et tests associés |
| 2 | § 2.2 | Règle d'architecture n° 6 : le domaine central n'exécute que la console plateforme et les webhooks, aucun code métier tenant |
| 3 | § 6.E | Backlog recadré : la facturation SaaS est désormais un **socle livré** (plans, abonnements, statuts) ; factures et encaissement des abonnements restent en v2/externe |

### Journal des modifications v1.1 (revue de cohérence)

| # | Section | Correction / amélioration |
|---|---|---|
| 1 | § 3 M2, M4 | Statut `expired` ajouté à l'enum `orders.status` + tunnel d'états (le statut "Devis expiré" était référencé mais non modélisé) |
| 2 | § 3 M8, Sprint 8 | Le statut fantôme `sold_owed` est remplacé par un **indicateur dérivé** (`balance_due > 0` sur commandes confirmées/livrées) — le statut de commande n'est pas pollué |
| 3 | § 4.5 | Contrainte CHECK `stock_qty >= 0` **supprimée** (contradictoire avec la règle M7 "rupture confirmée par le Chef") → contrôle applicatif journalisé |
| 4 | § 4.3 | `payments.recorded_by` passé en **nullable** (paiements webhook sans utilisateur humain) |
| 5 | § 3 M5, § 4.3 | Caisse rapide alignée sur la commande express `accessory_sale` + client générique "Comptoir" par tenant ; `order_id` reste nullable en dégradation |
| 6 | § 4.3 | `users.role_id` **supprimé** (redondant avec Spatie `model_has_roles`, risque de dérive) |
| 7 | § 4.3 | `tenant_settings.delivery_fees` jsonb **supprimé** (double source avec `delivery_zones`) |
| 8 | § 3 M10, § 4.3 | `production_slots.reserved_count` → `reserved_complex_count` (le quota ne concerne que les gâteaux complexes) + définition de la complexité |
| 9 | § 3 M9, M11 | Reformulation : un lien de paiement est une URL serveur pré-générée, **envoyable** depuis la fiche PWA hors-ligne (pas "exploitable hors-ligne") |
| 10 | § 4.3 | **Nouvelles tables** : `payment_links` (liens pré-générés + montant attendu → mismatch), `price_lists`/`price_list_rules` (grille de prix), `cash_closures` (clôture de caisse), `order_ingredient_consumptions` (consommation réelle par ligne → CMV exact) |
| 11 | § 4.3 | `products.reserved_qty` (stock réservé, M5) ; `orders.quote_token/quote_expires_at/quote_viewed_at` (lien public de devis) ; `inventory_movements.reversed_movement_id` (chaînage des contre-passations) |
| 12 | § 4.3 | `tenant_settings` complété : `voucher_validity_months`, `quote_followup_days`, `sold_owed_followup_days` (délais référencés par les modules mais absents du schéma) |
| 13 | § 3 M3 | Agrégats dérivés client (`voucher_balance`, `total_spent`, `last_order_at`) : recalcul périodique par job anti-dérive |
| 14 | § 6 | Ajout du § 6.E — hors périmètre v1 / backlog (facturation SaaS, API publique, multi-devises) |
| 15 | § 4.4/4.5 | Relations et index mis à jour avec les nouvelles tables ; CHECK de cohérence des dates complété |

---

## Table des matières

1. Résumé du projet, objectifs & contexte local (Côte d'Ivoire)
2. Architecture technique, stratégie UI/UX & choix structurants
3. Spécifications fonctionnelles détaillées (13 modules)
4. Modèle de données (schéma conceptuel PostgreSQL)
5. Exigences non fonctionnelles
6. Axes d'amélioration & points d'attention tech/métier
7. Plan de développement & sprints

---

# 1. RÉSUMÉ DU PROJET, OBJECTIFS & CONTEXTE LOCAL (CÔTE D'IVOIRE)

## 1.1 Présentation de Cakeman

Cakeman est une application SaaS B2B destinée aux **pâtisseries, cake designers et traiteurs sucrés** qui travaillent **sur commande** en Côte d'Ivoire et en Afrique de l'Ouest.

Contrairement aux pâtisseries de comptoir qui vendent des produits en stock, la pâtisserie "sur commande" fonctionne sur un cycle typique :

1. **Prise de commande personnalisée** (parts, saveurs, thème, visuel de référence, date de retrait/livraison) ;
2. **Devis et acompte** (30 à 100 %) ;
3. **Production planifiée** en atelier (cuisson J-2, montage J-1, décoration J-0) ;
4. **Retrait ou livraison** avec encaissement du solde.

Ce modèle génère aujourd'hui une charge administrative lourde : carnets papier, messageries WhatsApp désorganisées, acomptes non tracés, oublis de date, pertes de recettes, stocks non suivis, trésorerie illisible. **Cakeman digitalise l'intégralité de ce cycle** dans un outil simple, mobile-first, accessible depuis un smartphone.

### Valeur ajoutée pour les pâtissiers

| Problème actuel | Solution Cakeman |
|---|---|
| Devis faits à la main sur WhatsApp, jamais tracés | Devis générés, envoyés et suivis automatiquement |
| Acomptes reçus en Wave/Cash sans suivi | Encaissements enregistrés, webhooks de validation automatique |
| Oublis de dates de livraison | Planning d'atelier + rappels WhatsApp automatiques J-1 |
| Recettes et ingrédients gérés "de tête" | Fiches techniques avec décrémentation automatique des stocks |
| Créances non suivies (soldes à percevoir) | Suivi des encours et encaissements par commande |
| Aucune donnée sur les clients | CRM avec historique d'achats, préférences et allergènes |
| Trésorerie mélangée au quotidien | Distinction stricte CA / Trésorerie encaissée / Avoirs |
| Clients annulés sans règle | Politiques d'annulation configurables et automatisées |

### Objectifs

- **O1.** Réduire de 70 % le temps administratif de prise de commande et de suivi.
- **O2.** Éliminer les oublis et litiges de dates, d'acomptes et de soldes.
- **O3.** Donner au gérant une vision financière claire (CA réel, encaissements, créances, pénalités).
- **O4.** Professionnaliser la relation client via WhatsApp et le suivi de livraison.
- **O5.** Rendre le produit accessible sur réseau mobile 3G/4G et en Franc CFA (XOF).

## 1.2 Adaptation aux réalités locales

### Paiements mobiles (Mobile Money)

Le paiement mobile est le mode dominant en Côte d'Ivoire. Cakeman intègre en priorité :

- **Wave** (dominant en CI) ;
- **Orange Money** ;
- **MTN Mobile Money** ;
- **Moov Money** ;

via des agrégateurs ou intégrations directes : **CinetPay**, **Bizao**, **Paystack** (configurables par tenant dans le Module 2).

Règles locales intégrées : acompte obligatoire à la confirmation, solde payable à la livraison (lien Wave / QR code), remboursements sur annulation selon politique du gérant.

### Canal WhatsApp

WhatsApp est **l'outil de communication commerciale n°1** des pâtissiers ivoiriens. Cakeman l'utilise comme canal natif :

- Partage du devis en un clic (lien + message pré-formaté) ;
- Confirmation d'acompte reçu ;
- Rappels J-1 (livraison / retrait) ;
- Relance des devis envoyés et non confirmés ;
- Fiche de livraison envoyée au livreur.

Les modèles de messages sont **configurables par tenant** (Module 2) et les envois sont **asynchrones** (queue) pour ne jamais bloquer l'interface.

### Livraison par coursiers tiers et repères locaux

En Côte d'Ivoire, les adresses physiques classiques (numéro, rue) sont rares ou inopérantes. Cakeman adopte le modèle **"commune + repères locaux"** :

- Adresse de livraison structurée : commune (ex. Cocody), quartier, **repères** (ex. "après l'église Saint-Jean, maison jaune, 2ᵉ entrée") ;
- **Grille tarifaire de livraison par commune/zone** configurable (Cocody, Marcory, Yopougon, Grand-Bassam, etc.) ;
- Gestion de la livraison par **coursier tiers ou livreur interne**, avec fiche mobile indiquant repères locaux et solde FCFA exact à percevoir.

### Vente d'accessoires de fête

Complément de revenu naturel du pâtissier : bougies, toppers, feux d'artifice, présentoirs, boîtes cadeaux. Cakeman intègre un **catalogue d'accessoires** vendable de deux manières : ajouté au devis du gâteau, ou vendu directement au comptoir via la **Caisse rapide** (Module 5).

---

# 2. ARCHITECTURE TECHNIQUE, STRATÉGIE UI/UX & CHOIX STRUCTURANTS

## 2.1 Stack technique imposée

| Composant | Choix | Justification |
|---|---|---|
| Backend | **Laravel 13** (PHP 8.4+) | Écosystème complet, maturation SaaS, performance |
| Frontend | **Livewire 4** (Multi-File Components natifs, cf. § 2.4) | Réactivité sans SPA, maintenance simplifiée |
| UI Kit | **Flux UI** (version gratuite uniquement) | Composants modales/drawers/forms éprouvés, Tailwind natif |
| CSS | **Tailwind CSS** | Mobile-first, performances réseau réduites |
| Base de données | **PostgreSQL** | Rigueur transactionnelle, JSONB pour `tenant_settings`, plans d'exécution fiables |
| Multi-tenancy | **stancl/tenancy** (`PostgresSchemaManager`) — isolation par **schéma PostgreSQL** | Un seul serveur/une seule base, isolation logique stricte, bascule via `search_path` |
| Rôles & permissions | **spatie/laravel-permission** | RBAC standard, compatible multi-tenant |
| Files d'attente | Laravel Queues (database/Redis) | Traitement asynchrone WhatsApp & webhooks |
| Stockage | S3-compatible (MinIO en local) | Uploads compressés, isolation des fichiers par tenant |

## 2.2 Multi-tenancy avec PostgreSQL — isolation par schéma (stancl/tenancy)

### Architecture retenue

- **Une seule base de données PostgreSQL pour toute l'application** (`cakeman_db`) ;
- **Un schéma central `public`** : identité des tenants (`tenants`, domaines, statut abonnement), table `admin_users` du **super-admin plateforme** (structure identique à `users` des tenants : Fortify + 2FA + passkeys) uniquement ;
- **Un schéma dédié par tenant** (`tenant_{uuid}`) : toutes les données métier (users, customers, orders, payments, inventory…).

Cette stratégie **"schéma par tenant"** (trait `PostgresSchemaManager` de stancl/tenancy) offre un excellent compromis entre isolation et simplicité de gestion : aucune requête croisée possible (une requête n'atteint jamais que le schéma du search_path courant), aucune fuite de données en cas de bug applicatif, et restauration possible par schéma. À chaque requête, stancl/tenancy bascule dynamiquement le chemin de recherche :

```sql
SET search_path TO tenant_{uuid}, public;
```

### Identification du tenant

- **Web (UI)** : identification par **sous-domaine** (`nompatisserie.cakeman.ci`), middleware `InitializeTenancyBySubdomain` de stancl/tenancy.
- **API / Webhooks (paiements)** : identification par **en-tête sécurisé `X-Tenant-Id` + signature HMAC** ou clé d'API par tenant, avec résolution avant traitement du webhook (les webhooks arrivant sur le domaine central sont routés vers le schéma du tenant cible).

### Règles d'architecture

1. Toute requête entrante passe par le middleware tenancy AVANT tout accès Eloquent.
2. Les jobs en file d'attente sont **tenant-aware** (`TenantAwareJob`) : restauration du contexte tenant au moment de l'exécution.
3. Le stockage de fichiers est **namespace par tenant** (`tenants/{tenant_id}/...`) ; les URLs publiques contiennent le domaine du tenant.
4. Les migrations sont **tenant-scoped** (exécutées dans chaque schéma tenant, nommé `tenant_{uuid}`) ; les migrations centrales ne s'exécutent que dans le schéma `public`.
5. **Interdiction formelle** des modèles globalement scopés partagés : aucun modèle métier ne doit être accessible hors contexte tenant.
6. **Le domaine central n'exécute que la console plateforme (Module 12) et les webhooks** : aucun code métier tenant (wizard, planning, caisse…) ne tourne hors contexte tenancy.

## 2.3 Intégration de spatie/laravel-permission en contexte multi-tenant

- Les tables Spatie (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`) vivent **dans le schéma du tenant** → isolation naturelle : chaque tenant dispose de ses propres rôles et permissions.
- **Rôles racines fournis par défaut** à l'installation de chaque tenant (module 1) ; le gérant peut créer des rôles additionnels.
- Les **permissions sont centralisées en code** (enum/spécialisation de `PermissionRegistrar`) pour garantir des identifiants stables : `order.create`, `order.cancel`, `payment.record`, `inventory.manage`, `dashboard.view`, etc.
- Les policies Laravel s'appuient sur ces permissions, jamais sur de simples booléens d'utilisateur.
- Le cache des permissions Spatie doit être **purgeable par tenant** (`php artisan permission:cache-reset` dans le contexte tenant) pour éviter toute fuite inter-tenant ;
- **RBAC plateforme** : les mêmes tables Spatie vivent dans le schéma central `public` pour la console super-admin (Module 12) — même bibliothèque, deux scopes isolés ; permissions plateforme centralisées en enum (`admin.*`), rôles racines seedés (`Super-Admin`, `Support`, `Billing`) et purge de cache par scope.

## 2.4 Choix architectural Livewire 4 : Multi-File Components (MFC) natifs

### Principe

Livewire 4 propose 3 formats : single-file (SFC, défaut), **multi-file (MFC, natif v4)** et class-based (v3). Cakeman retient le **MFC natif v4** (`php artisan make:livewire --mfc`), qui colocalise classe PHP, template et fichiers annexes (JS/CSS/tests) dans un dossier unique sous `resources/views/components/`. La commande par défaut est figée dans `config/livewire.php` :

```php
'make_command' => ['type' => 'mfc'],
```

Arborescence du Wizard :

```
resources/views/components/orders/
├── ⚡create-order/                      (nom : orders.create-order — page /orders/create)
│   ├── create-order.php                 (contrôleur du Wizard)
│   ├── create-order.blade.php           (template)
│   ├── create-order.js                  (optionnel, --js)
│   ├── create-order.css                 (CSS scopé, optionnel, --css)
│   └── create-order.test.php            (tests Pest colocalisés, --test)
└── create-order/                        (sous-composants du Wizard)
    ├── ⚡step-customer/                 (nom : orders.create-order.step-customer)
    │   ├── step-customer.php
    │   └── step-customer.blade.php
    ├── ⚡step-cake/                     (étape : gâteau)
    ├── ⚡step-schedule/                 (étape : date/heure/livraison)
    ├── ⚡step-accessories/              (étape : accessoires)
    └── ⚡step-payment/                  (étape : acompte)
```

Règles de résolution (MFC v4) :

| Chemin fichier | Nom du composant |
|---|---|
| `resources/views/components/orders/⚡create-order/create-order.php` | `orders.create-order` |
| `resources/views/components/orders/create-order/⚡step-customer/step-customer.php` | `orders.create-order.step-customer` |

- Le préfixe `⚡` et la structure sont strippés à la résolution : en Blade → `<livewire:orders.create-order />`, en route → `Route::livewire('/orders/create', 'orders.create-order')` ;
- Piège : si on définit un `render()`, le chemin de vue **inclut le `⚡`** (`view('orders.⚡create-order')`) ;
- Les sous-composants sont rendus dans le template du parent via `<livewire:orders.create-order.step-customer />` (avec `wire:key` pour isoler les états) ;
- Conversion sans impact sur les usages (le nom ne change pas) : `php artisan livewire:convert orders.create-order --mfc` / `--sfc`.

### Justification

- **Maintenabilité** : chaque étape métier est un dossier PHP + template + JS/CSS/tests colocalisés, sans "god component" de 2 000 lignes ;
- **Séparation des responsabilités** : logique métier (validation, calculs, persistance) isolée dans la classe du composant, rendu limité au template ;
- **Testabilité** : chaque sous-composant est testable unitairement et en feature (Pest) avec ses données propres ; tests colocalisés (`--test`) ;
- **Montée en charge** : les états Livewire par étape réduisent la taille des payloads réseau (économie 3G/4G).

### Convention Cakeman

| Règle | Application |
|---|---|
| 1 composant = 1 page ou 1 flux métier | `orders.create-order`, `orders.order-index`, `payments.cashier` |
| Sous-composants privés pour les étapes | préfixe `step-`/`section-`, dossiers `⚡` sous le dossier du composant parent |
| Logique métier en services | `app/Services/` (pricing, cancellation, stock) réutilisables hors Livewire |
| Validation par Form Requests ou règles inline | selon la taille du flux (voir `.ai/rules` du projet) |
| Zéro requête Eloquent dans les vues | tous les accès data passent par le composant |
| Création | `php artisan make:livewire orders.create-order --mfc --test` ; `livewire:convert` pour migrer un format existant |

## 2.5 Stratégie Ergonomique : Modales (formulaires & actions) vs Drawers (consultation) vs Pages Dédiées

### Principe de la règle 80/20

**80 % des interactions → interactions sur overlay** (aucun rechargement, aucune perte de contexte) :
- **TOUS les formulaires de création/édition passent par des modales Flux** (`<flux:modal>`, largeur ajustable via `md:w-*` selon la taille du formulaire) : création rapide de client, ajout/édition d'ingrédient, d'accessoire, de produit, de zone de livraison, de rôle, ajustement de stock, approvisionnement, édition d'une ligne, édition des modèles de messages, création/édition d'un tenant ;
- **Modales centrales (`<flux:modal>`)** pour les actions courtes et confirmations : encaissement manuel, annulation d'une commande, vente caisse rapide, ajout rapide d'un article, validation de passage d'état, saisie de motif ;
- **Drawers / Slide-over (`<flux:modal flyout>`, ouverture à droite par défaut)** réservés à la **consultation rapide** (jamais de saisie) : détail commande, fiche client, historique des mouvements/paiements, détail d'annulation, jour/créneau de planning.

**20 % des vues complexes → pages dédiées pleine écran** pour les flux à charge d'information élevée, multi-étapes, pour éviter le scroll-in-scroll et la perte de données sur mobile :
- **Prise de commande personnalisée : `/orders/create` en Wizard 5 étapes** (pas de scroll interne, une étape = un écran) ;
- **Tableaux de bord Financiers & Analytics** (graphiques, agrégats, filtres) ;
- **Listes principales** (commandes, clients, stock) avec pagination serveur.

### Récapitulatif du mapping UX

| Interaction | Surface | Exemple |
|---|---|---|
| Action courte / confirmation | Modale Flux | Encaisser, annuler, changer d'état, caisse rapide |
| **Formulaire de création/édition** | **Modale Flux** (`<flux:modal>`, `md:w-*`) | Nouveau client, ajout/édition stock & accessoires, zone livraison, rôle, modèles WhatsApp, tenant |
| Flux multi-étapes | Page dédiée / Wizard | `/orders/create`, onboarding tenant |
| Données denses / analytics | Page dédiée | Tableaux de bord financiers, planning atelier |
| Consultation rapide | Flyout (`<flux:modal flyout>`) | Détail commande, fiche client, historiques |

> Règle impérative : **aucun formulaire de création/édition en drawer ni en page dédiée** (hors Wizard multi-étapes) — la modale Flux est la surface unique de saisie hors écrans principaux.
>
> Syntaxe (Flux v2, sans préfixe `x-`) : `<flux:modal>` (variante `floating`, `bare`), `<flux:modal.trigger>` / `<flux:modal.close>`, `<flux:modal flyout>` pour le slide-over (position `right` par défaut, `left`/`bottom` configurables) ; contrôle depuis Livewire via `Flux::modal('nom')->show()/close()`.

## 2.6 Performance mobile 3G/4G & devise XOF

- **Design mobile-first** : navigation par onglets en bas d'écran sur mobile, tableaux convertis en cartes ;
- **Poids des pages minimisé** : composants Livewire lazy (`wire:lazy`), pagination serveur, aucun tableau rendu intégralement ;
- **Compression automatique des images** téléversées (visuels de référence, logo, photos produits) : redimensionnement + compression WebP à l'upload, dans une job asynchrone ;
- **Devise native Francs CFA (XOF)** : formatage `1 250 000 FCFA`, sans décimales ; toutes les entrées monétaires en entiers (centimes non pertinents, évite les erreurs d'arrondi) ;
- **Déconnexion 3G gérée** : formulaires à étapes courtes, sauvegarde automatique du brouillon de commande, retry natif des requêtes Livewire.

---

# 3. SPÉCIFICATIONS FONCTIONNELLES DÉTAILLÉES (13 MODULES)

Chaque module précise : **Objectif · Fonctionnalités · Règles de gestion · Rôles autorisés · Surfaces UI · Points techniques**.

## Module 1 — Multi-Tenancy, Authentification & Rôles Spatie

### Objectif
Fournir une base d'accès sécurisée, isolée par tenant, avec un RBAC couvrant les 4 métiers de la pâtisserie.

### Rôles racines

| Rôle | Périmètre | Droits principaux |
|---|---|---|
| **Gérant** (Admin tenant) | Tous les modules | Paramétrage, finances, annulations, planning, livraisons, gestion utilisateurs |
| **Chef Pâtissier** | Atelier & Stock | Stocks, fiches techniques, planning de préparation, passages d'état "En préparation" → "Prête" |
| **Vendeur / Réceptionniste** | Comptoir & Devis | Création clients, devis, prise de commande, caisse rapide, encaissements |
| **Livreur** | Livraison | Vue mobile des fiches de livraison, statuts, encaissement du solde |

### Fonctionnalités
- Inscription de la pâtisserie (tenant) avec onboarding : identité, branding, zone de livraison par défaut, 1ᵉʳ utilisateur = Gérant ;
- Authentification Laravel Fortify + passkeys, résiliente mobile ;
- Gestion des utilisateurs du tenant (invitation par e-mail/WhatsApp, activation/désactivation) ;
- Attribution des rôles ; création de rôles personnalisés par le gérant (permissions choisies dans la liste exhaustive) ;
- Protection par permissions sur chaque action métier (contrôleurs + composants Livewire) ;
- Journal d'audit des connexions et actions sensibles (paiements, annulations, suppressions).

### Règles de gestion
- Un utilisateur appartient à **un seul tenant** (identifié par son schéma) ;
- Le super-admin plateforme n'a aucun accès aux données métier des tenants (accès de support en lecture seule via le Module 12, session temporaire journalisée) ;
- Désactivation d'un utilisateur : connexions actives révoquées immédiatement ;
- La suppression d'un utilisateur est impossible si des mouvements financiers ou de stock lui sont rattachés (archivage à la place).

### Surfaces UI
- Page dédiée `Settings > Équipe & Rôles` (liste + modale d'édition) ;
- Modale de confirmation pour désactivation/réattribution de rôle.

## Module 2 — Paramétrage Global Tenant (Panneau Gérant)

### Objectif
Centraliser **toute la configuration métier du tenant** dans une table `tenant_settings` (colonnes typées + blobs JSONB pour les structures libres), éditable uniquement par le Gérant.

### 2.1 Profil & Branding
- Nom commercial, logo (compressé WebP), slogan ;
- Adresse physique structurée : commune, quartier, **repères locaux** (champ libre enrichi) ;
- Numéro WhatsApp principal (celui utilisé pour les messages clients), téléphone fixe ;
- Mentions légales (numéro contribuable, adresse, RCCM), affichées en pied de devis ;
- Devise : XOF (fixe, non modifiable à la volée).

### 2.2 Règles commerciales
- **% d'acompte obligatoire** : 30 %, 50 % ou 100 % (choix unique appliqué par défaut, ajustable par commande) ;
- **Délai d'expiration des devis** : nombre de jours (ex. 7), expiration automatique → statut "Devis expiré" ;
- **Délai de prévenance minimum** : 48 h (configurable) — toute demande de date inférieure au délai déclenche un avertissement bloquant à la prise de commande.

### 2.3 Politiques d'annulation configurables
| Politique | Comportement automatisé |
|---|---|
| **Souple** | Remboursement 100 % de l'acompte (remboursement Wave ou avoir, au choix) |
| **Intermédiaire** | Émission automatique d'un **avoir/bon d'achat** du montant de l'acompte |
| **Stricte** | Acompte **conservé** et reclassé en pénalité (produit exceptionnel) |
- **Délai limite d'annulation sans frais** : au-delà, la politique appliquée peut être renforcée (ex. : strict si < 24 h) ;
- Toute annulation enregistre la politique effectivement appliquée sur la commande.

### 2.4 Capacité de production & atelier
- Horaires d'ouverture par jour (retrait/livraison) ;
- Jours de fermeture (hebdomadaire + exceptionnel) ;
- **Quota maximal journalier de gâteaux complexes** : au-delà, le calendrier bloque automatiquement les créneaux (Module 10).

### 2.5 Grille tarifaire de livraison
- Tarif par **commune/zone** (`delivery_zones`) : ex. Cocody 3 000 F, Marcory 3 500 F, Yopougon 4 000 F, Grand-Bassam 5 000 F ;
- Zones actives/inactives ; frais appliqué automatiquement au devis selon l'adresse de livraison ;
- Mode "retrait en boutique" = frais nuls.

### 2.6 Clefs API Mobile Money & WhatsApp
- Configuration **Wave, CinetPay, Bizao, Paystack** : clés chiffrées au repos (Laravel `Crypt`), mode test/production, compte de réception par défaut ;
- Configuration WhatsApp (fournisseur de messagerie : cloud API officielle ou agrégateur) + **modèles de messages** éditalbes (devis, confirmation, rappel J-1, relance, livraison) avec variables (`{client}`, `{montant}`, `{date}`, `{ref}`).

### 2.7 Seuils d'alerte stock & unités
- Unités disponibles : **g, kg, L, pcs** ;
- Seuil minimal global configurable et ajustable par produit ;
- Alertes visibles (badge stock) + option notification WhatsApp au gérant/chef.

### 2.8 Grille de prix
- Tables `price_lists` (liste nommée, une seule `is_default` par boutique) et `price_list_rules` (prix par unité/saveur + plage de parts, validité éventuelle) ;
- La grille par défaut alimente **l'estimation automatique des devis** (Module 4.1) et le prix des gâteaux par parts/saveur ;
- Édition en **modale** ; une règle peut être bornée dans le temps (promotions, saisonnalité).

### Règles de gestion
- Seul le rôle **Gérant** modifie les paramètres ; les autres rôles sont en lecture seule ;
- Les changements de politique d'annulation n'affectent **que les nouvelles commandes** ;
- Sauvegarde avec validation stricte (types, plages, formats téléphone).

### Surfaces UI
- Page dédiée `Settings > Boutique` avec onglets (Branding, Commercial, Atelier, Livraison, Tarifs, Intégrations, Stock) ;
- Modales pour l'édition des zones de livraison et des modèles de messages.

## Module 3 — CRM Clientèle & Portefeuille d'Avoirs

### Objectif
Centraliser la connaissance client et automatiser la gestion des avoirs issus des annulations.

### Fonctionnalités
- **Fiche client** : nom, prénom, numéro WhatsApp principal (clé de communication), e-mail optionnel, adresse de livraison par défaut **avec repères habituels**, notes libres ;
- **Historique d'achats** : commandes passées (statuts, montants), **chiffre d'affaires généré**, dernière commande ;
- **Préférences & allergènes** enregistrés (ex. "allergie arachide", "préfère le chocolat noir") — rappel visuel à la prise de commande ;
- **Portefeuille d'avoirs** : solde de bons d'actifs, historique des émissions/utilisations ;
- **Saisie rapide via Modale** accessible depuis le Wizard de commande (création client sans quitter le flux) ;
- Recherche client par nom ou numéro WhatsApp (requête unique sur mobile) ;
- Dédoublonnage : détection de numéro WhatsApp existant à la création.

### Règles de gestion
- Un client appartient au tenant (schéma tenant) ; aucune donnée partagée inter-tenant ;
- Les avoirs sont **utilisables automatiquement** sur les prochains devis (proposition au moment du paiement) ;
- La suppression d'un client est interdite s'il possède un historique de commandes → archivage ;
- Les numéros sont normalisés au format international (+225) ;
- Les agrégats dérivés (`voucher_balance`, `total_spent`, `last_order_at`) sont recalculés **par job planifié** (anti-dérive : ils ne sont jamais mis à jour directement par les flux métier, qui écrivent les lignes sources).

### Surfaces UI
- Page `Clients` (liste + recherche + pagination) ;
- **Drawer** fiche client (détail, historique, avoirs — consultation) ;
- **Modale** création rapide (dans le flux de commande) ;
- Modale de confirmation pour archivage.

## Module 4 — Gestion des Devis & Commandes Personnalisées

### Objectif
Couvrir le cœur du métier : la commande sur mesure, de la demande à la livraison.

### 4.1 Caractéristiques de la commande
- **Gâteau** : nombre de parts (avec guide "1 étage 10 parts / 2 étages 20 parts…"), saveurs (base + garnissage), thème/occasion (anniversaire, mariage, baptême…), couleurs ;
- **Visuels de référence** : upload photos HD (compression **côté client** Canvas/WebWorker avant envoi, payload < 300 Ko, WebP qualité ~70 — cf. § 6.A ; compression serveur en fallback), jusqu'à 5 par commande ;
- **Allergènes** : liste sélectionnable, affichée sur la fiche de production et la fiche client ;
- **Date/heure exactes** de retrait ou de livraison ;
- **Options** : numéro d'anniversaire en pâte à sucre, faux étage, personnalisation topper, etc. (options libres) ;
- **Prix** : soit tarif saisi manuellement (cake designer), soit calculé depuis la grille de prix par parts/saveur si configurée.

### 4.2 Wizard `/orders/create` (page dédiée — 5 étapes)

| Étape | Contenu | Règles |
|---|---|---|
| 1. Client | Recherche ou création rapide (modale) | Numéro WhatsApp requis |
| 2. Gâteau | Parts, saveurs, thème, allergènes, visuels, prix | Validation cohérence parts/étages ; visuels compressés côté client (cf. § 6.A) |
| 3. Accessoires | Catalogue + quantités (optionnel) | Déstockage à la confirmation |
| 4. Programme | Date/heure, retrait ou livraison, zone, frais | Respect délai de prévenance + quota journalier (vérifié à la confirmation, cf. § 6.B) |
| 5. Paiement | Acompte %, avoir applicable, mode de règlement | Acompte ≥ minimum configuré ; devis généré |

- **Brouillon** sauvegardé automatiquement à chaque étape (résilient 3G) ;
- **Uploads différés et non bloquants** : les visuels compressés sont transmis après l'enregistrement du brouillon (aperçu local optimiste + reprise en cas de coupure) ;
- Navigation avant/arrière sans perte de données ;
- Récapitulatif final éditable avant envoi.

### 4.3 Partage du devis
- Génération d'un **devis numérique** (PDF léger + vue web responsive) avec numéro, prix détaillé, acompte, conditions, mentions légales ;
- **Envoi WhatsApp en un clic** : message pré-formaté + lien de devis ;
- Lien de devis public (non authentifié) avec statut "Vu/En attente/Confirmé".

### 4.4 Tunnel d'états

```
Brouillon ➔ Devis envoyé ➔ Confirmée (acompte payé) ➔ En préparation
   ➔ Prête ➔ Livrée / Retirée
   └➔ Devis expiré (automatique après le délai configuré, scheduler)
   └➔ Annulée (à tout moment, selon Module 6)
```

| Transition | Déclencheur | Effet |
|---|---|---|
| Brouillon → Devis envoyé | Envoi WhatsApp | Horodatage, lien généré |
| Devis envoyé → Confirmée | Webhook acompte OU encaissement manuel | Réserve la date, bloque le quota journalier, déstocke accessoires — le tout dans une **transaction verrouillée** (SELECT ... FOR UPDATE, cf. § 6.B) pour garantir le quota même en cas de confirmations simultanées |
| Devis envoyé → Devis expiré | Scheduler (délai d'expiration dépassé, Module 2.2) | Statut `expired`, lien public invalidé ; relance WhatsApp (J+n) envoyée avant expiration ; réactivation possible par le Vendeur/Gérant (nouvel envoi) |
| Confirmée → En préparation | Chef Pâtissier | Déclenche la décrémentation des ingrédients (Module 7) |
| En préparation → Prête | Chef Pâtissier | Disponible retrait/livraison |
| Prête → Livrée/Retirée | Livreur/Vendeur | Encaissement solde, clôture financière (Module 8) |
| → Annulée | Vendeur/Gérant | Politique Module 6 |

> Note : "Livrée" et "Retirée" partagent le même statut technique `delivered` — le mode (`pickup`/`delivery`) distingue l'affichage. La créance éventuelle (solde impayé à la clôture) n'est **pas** un statut de commande : elle se déduit de `balance_due > 0` (voir Module 8).

Chaque transition est horodatée, journalisée et notifie le client (WhatsApp, sauf annulation gérée par le personnel).

### Rôles autorisés
- Vendeur/Gérant : création, modification pré-confirmation, annulation ;
- Chef Pâtissier : transitions de production ;
- Livreur : transition "Livrée" ;
- Seul le Gérant peut modifier une commande **confirmée** (traçabilité).

### Surfaces UI
- **Page dédiée `/orders/create`** (Wizard) ;
- Page `Commandes` (liste filtrable par statut/date) ;
- **Drawer** détail commande (timeline d'états, paiements, production) ;
- **Modale** d'encaissement, d'annulation, de passage d'état.

## Module 5 — Vente d'Accessoires & Articles de Fête

### Objectif
Monétiser le complément de gamme (bougies, toppers, feux d'artifice, présentoirs, boîtes) sans complexifier le cœur commande.

### Fonctionnalités
- **Catalogue accessoires** : nom, image, prix de vente FCFA, coût, stock (pcs), catégorie, actif/inactif, seuil d'alerte ;
- Deux circuits de vente :
  1. **Intégré au devis** : ligne "Accessoires" dans le Wizard (étape 3) — le tout déstocké à la confirmation ;
  2. **Caisse rapide au comptoir** : modale dédiée — sélection produit + quantité → **déstockage immédiat + encaissement immédiat** (cash ou Mobile Money) → reçu via WhatsApp ;
- Recherche rapide par nom ; gestion des prix libres par vente (remise manuelle autorisée) ;
- Mouvements de stock tracés (Module 7).

### Règles de gestion
- Un accessoire ne peut être vendu en quantité supérieure au stock disponible (stock courant − stock réservé) ;
- La caisse rapide ne passe **jamais** par le tunnel de commande : génération d'une "commande express" interne (type `accessory_sale`) pour la traçabilité comptable ;
- **Stock réservé** : à la confirmation d'une commande, les accessoires sont **déstockés** et les ingrédients de la fiche technique sont **réservés** (`products.reserved_qty`) jusqu'à la consommation réelle au passage "En préparation" (Module 7) ; le stock réservé n'est jamais proposé à la caisse rapide ni à une autre commande ;
- La caisse rapide enregistre toujours un client : un client générique **"Comptoir"** est créé automatiquement à l'installation du tenant (cas du client de passage sans fiche).

### Rôles autorisés
- Vendeur & Gérant : caisse rapide, catalogue ;
- Chef Pâtissier : lecture du catalogue, gestion des niveaux de stock.

### Surfaces UI
- Page `Accessoires` (catalogue, grid) ;
- **Modale "Caisse rapide"** accessible depuis la page d'accueil et le comptoir ;
- **Modale** ajout/édition d'un accessoire.

## Module 6 — Exécution des Annulations, Avoirs & Stock

### Objectif
Automatiser la gestion des annulations conformément à la politique du tenant, sans litige.

### Fonctionnalités
- **Modale de confirmation d'annulation** :
  - Rappel textuel de la politique appliquée (calculée selon dates) ;
  - Saisie du **motif** (obligatoire) : client, problème de production, autre ;
  - Choix du traitement (pré-rempli selon politique) : remboursement / avoir / acompte conservé ;
- **Traitement automatisé** :
  - *Politique Souple* → remboursement (retour Wave via webhook ou enregistrement de remboursement manuel) ;
  - *Politique Intermédiaire* → **émission automatique d'un Code Avoir unique** crédité sur la fiche client (Module 3) ;
  - *Politique Stricte* → acompte conservé, **reclassé en pénalité** (produit exceptionnel, Module 8) ;
- **Gestion des stocks sur annulation** :
  - Si **avant** le passage "En préparation" → **réintégration** automatique des accessoires au stock ;
  - Si **pendant/en** fabrication → enregistrement des **pertes d'ingrédients** (mouvement `loss` lié à la commande annulée) ;
- Notifications : WhatsApp au client (montant remboursé/avoir code/annulation confirmée) ;
- Historique d'annulation complet (qui, quand, motif, politique, montants).

### Règles de gestion
- Une annulation est **irréversible sans rôle Gérant** (réouverture exceptionnelle journalisée) ;
- La libération du créneau réservé s'effectue dans une **transaction verrouillée** (cf. § 6.B), garantissant la cohérence du quota face aux confirmations simultanées ;
- L'avoir émis a une validité configurable (ex. 12 mois) puis expire ;
- Un code avoir est **mono-usage partiel** : utilisable en plusieurs fois jusqu'à épuisement, jamais au-delà du solde ;
- Les remboursements externes (Wave) sont suivis en statut `pending → succeeded/failed`.

### Rôles autorisés
- Vendeur : déclenche l'annulation (dans la limite de ses droits, motif obligatoire) ;
- Gérant : tout, dont dérogation de politique (journalisée).

### Surfaces UI
- **Modale d'annulation** (avec résumé de politique + motif) ;
- **Drawer** détail d'annulation (timeline, mouvements de stock générés).

## Module 7 — Gestion des Stocks & Fiches Techniques (Recettes)

### Objectif
Suivre précisément les ingrédients, emballages et accessoires, et décrémenter automatiquement les stocks à la production.

### Fonctionnalités
- **Catalogue de produits stockables** : ingrédients, emballages, accessoires — unité (g, kg, L, pcs), stock courant, seuil, coût unitaire, fournisseur (optionnel) ;
- **Fiches techniques (recettes)** : liste d'ingrédients avec quantités (ex. *Gâteau chocolat 10 parts* : 500 g farine, 250 g sucre, 300 g chocolat…), rendement, coût théorique calculé, prix conseillé ;
- **Opérations rapides** (modales) : ajustement de stock (correction inventaire + motif), approvisionnement (quantité + coût), pertes/gaspillage (quantité + motif), ajout d'ingrédient ;
- **Décrémentation automatique** : au passage de la commande en "En préparation" (Module 4), consommation des ingrédients de la fiche technique associée + des accessoires non encore déstockés — chaque ligne est journalisée dans `order_ingredient_consumptions` (quantité planifiée vs réelle) ;
- **Substitution d'ingrédients en fabrication** (cf. § 6.C) : option **"Ajustement d'ingrédient"** au moment de la décrémentation — remplacement d'une ligne de la recette (ingrédient + quantité, ex. beurre marque A → B, chocolat 60 % → 70 %) avant validation de la production ;
- **Mouvements de stock** : historique complet par produit (`Approvisionnement`, `Sortie commande`, `Pertes`, `Ajustement`, `Retour annulation`, `Vente comptoir`, `Substitution`) ;
- **Alertes de seuil minimal** : badge + notification WhatsApp au Chef/Gérant.

### Règles de gestion
- Un mouvement est **immutable** (jamais supprimé : correction = mouvement inverse journalisé) ;
- La décrémentation est **transactionnelle** (DB transaction) et ne peut passer en négatif sans alerte bloquante (le Chef confirme une rupture avec motif "substitution") ;
- **Substitution** : remplacement journalisé — réintégration du mouvement du produit d'origine (annulé), mouvement `production_out` du produit de substitution, motif "substitution" obligatoire ; le **coût réel de la commande est recalculé** avec les quantités consommées (impact CMV, Module 8) ;
- La substitution n'est possible **qu'avant validation** de la production ; après, seule une perte/adjustement séparé est autorisé ;
- Les substitutions sont affichées sur la fiche de production et dans l'historique de la commande (traçabilité) ;
- Une recette consommée est rattachée à la commande (traçabilité des coûts par commande) via `order_ingredient_consumptions` ;
- Réintégration automatique lors d'une annulation pré-production (Module 6) ;
- Coût des marchandises consommées (CMV) calculable par période pour le Module 8.

### Rôles autorisés
- Chef Pâtissier & Gérant : gestion complète ;
- Vendeur : lecture (rupture visible au comptoir), ajustement interdit.

### Surfaces UI
- Page `Stocks` (liste + filtres + état des alertes) ;
- Page `Fiches techniques` (détail recette, coût) ;
- **Modale** ajout/édition produit & ingrédient ;
- **Modale** ajustement/approvisionnement/pertes/substitution ;
- **Drawer** historique des mouvements d'un produit.

## Module 8 — Gestion Financière, Trésorerie & Chiffre d'Affaires

### Objectif
Garantir une vision comptable fiable, adaptée à la réalité des acomptes et des soldes.

### Règle comptable stricte
- **Chiffre d'Affaires (CA)** = somme des **commandes Livrées/Retirées** (valeur totale, TTC) ;
- **Trésorerie encaissée** = flux réels perçus (acomptes + soldes) par moyen de paiement (Cash, Wave, Orange Money, MTN, Moov, Carte, Avoir) ;
- **Encours / créances** = soldes restant à percevoir sur commandes confirmées (acomptes reçus, solde non réglé) ;
- **Produits exceptionnels (pénalités)** = acomptes conservés lors d'annulations (politique stricte) ;
- **Avoirs émis et utilisés** = suivi distinct (dette du tenant envers le client).

### Fonctionnalités
- **Modale d'encaissement dédiée** (manuel) : sélection commande, type (acompte/solde/remboursement), montant, moyen de paiement, référence transaction (si MM) ;
- **Encaissements automatiques** via webhooks (Module 9) ;
- **Tableau de bord financier** (page dédiée) :
  - CA du jour/semaine/mois (par période personnalisée) ;
  - Trésorerie encaissée par moyen de paiement ;
  - Encours total et détail par commande ;
  - Pénalités d'annulation ;
  - Valeur des avoirs émis/utilisés ;
  - CMV (coût marchandises consommées) et marge brute estimée ;
- Journal de trésorerie chronologique (chaque mouvement = ligne immuable) ;
- Export CSV/PDF des rapports.

### Règles de gestion
- Une commande ne contribue au CA **qu'à sa clôture** (Livrée/Retirée) ; une annulation n'y contribue jamais ;
- Tout encaissement crée un `payment` ; tout remboursement crée un `payment` négatif typé ;
- Les montants sont stockés en entiers (FCFA) ; aucun arrondi flottant ;
- Clôture de caisse quotidienne : rapprochement trésorerie théorique vs encaissée, écart constaté par le Gérant (table `cash_closures`) ;
- Une commande clôturée (Livrée/Retirée) sans solde encaissé reste en **créance** — indicateur dérivé `sold_owed` (balance_due > 0, relance WhatsApp configurable via `sold_owed_followup_days`) ; ce n'est pas un statut de commande, le statut reste `delivered`.

### Rôles autorisés
- Vendeur : encaissements simples ;
- Gérant : tableau de bord, clôture de caisse, reclassements, export.

### Surfaces UI
- Page dédiée `Finances` (tableaux + graphiques) ;
- **Modale d'encaissement** (contextuelle depuis une commande ou le comptoir) ;
- **Drawer** détail des paiements d'une commande.

## Module 9 — Intégration des Paiements Mobiles (Mobile Money)

### Objectif
Automatiser la validation des acomptes et des soldes via les canaux Mobile Money locaux.

### Fonctionnalités
- **Collecte des acomptes** : génération d'un lien de paiement ou référence de paiement (Wave, CinetPay, Bizao, Paystack) envoyé au client par WhatsApp ;
- **Webhooks** : réception et validation automatique → dès réception de l'acompte, la commande passe "Confirmée" (Module 4) — le passage s'effectue dans une **transaction verrouillée** (cf. § 6.B) pour garantir la réservation du quota en cas de confirmations concurrentes ;
- **Soldes à la livraison** : génération d'un **lien Wave / QR Code** de solde **en amont de la tournée** (pré-généré au départ du livreur, table `payment_links`) — le livreur envoie le lien depuis la fiche PWA, le client paie, le webhook confirme et clôture ; la fiche reste consultable et l'envoi possible hors-ligne (cf. § 6.D), le paiement lui-même étant traité par le fournisseur dès le retour au réseau du client ;
- **Remboursements** : initiation via API (Wave/CinetPay) avec suivi d'état ;
- Tableau de bord des transactions par fournisseur (succès, échecs, montants) ;
- **Logs de webhooks** : payload brut horodaté, signature vérifiée, statut de traitement, rejeu manuel possible ;
- Mode **test** (sandbox) vs **production** par tenant.

### Règles de gestion
- **Idempotence** : chaque webhook traité une seule fois (clé `provider_ref` unique) ; les doublons sont ignorés et journalisés ;
- **Vérification de signature** obligatoire avant tout traitement ;
- Un paiement "succeeded" est irréversible ; les corrections passent par un remboursement dédié ;
- En cas d'échec de l'appel sortant (lien Wave non généré), l'encaissement manuel reste disponible (dégradation élégante) ;
- Montants des webhooks comparés au montant attendu (ordre) — écart → statut `mismatch` à vérifier manuellement.

### Rôles autorisés
- Gérant : configuration fournisseurs (Module 2), rejeu de webhook, vérification des mismatches ;
- Vendeur/Livreur : envoi de lien de solde.

### Surfaces UI
- Page `Paiements` (liste, statuts, filtres fournisseur/méthode) ;
- **Modale** de vérification d'écart de montant ;
- Notifications en temps réel (toast) lors de réception d'acompte.

## Module 10 — Planning d'Atelier & Capacité de Production

### Objectif
Organiser l'atelier (cuisson, montage, décoration) et garantir la faisabilité des commandes.

### Fonctionnalités
- **Vue calendrier interactive** (mois/semaine) : commandes positionnées par date (retrait/livraison), étapes de préparation (cuisson J-2, montage J-1, décoration J-0) ;
- Vue par jour : liste des tâches de l'atelier avec commande, fiche technique, allergènes, visuels ;
- **Blocage automatique** : dès que le **quota journalier de gâteaux complexes** est atteint, les nouveaux créneaux de la date sont bloqués (le Wizard Module 4 le signale et propose les dates suivantes) ;
- **Réservation concurrente-sûre** (cf. § 6.B) : la vérification et l'incrément du quota se font **atomiquement** dans une transaction avec verrouillage pessimiste (`SELECT ... FOR UPDATE` sur la ligne de la commande et/ou du créneau `production_slots`), garantissant qu'aucun dépassement ne survient lors de confirmations simultanées ;
- Gestion des fermetures et capacités réduites (jours fériés, congés) ;
- Réaffectation manuelle d'une commande d'une date à l'autre (Gérant) avec notification client ;
- Marqueurs visuels de statut (confirmée, en préparation, prête, livrée) ;
- Vue "Atelier du jour" spécifique au Chef Pâtissier (liste de production avec priorité).

### Règles de gestion
- Le quota est configuré dans `tenant_settings` (Module 2) et vérifié **à la confirmation** (réservation de créneau) et non à la création du brouillon ;
- La réservation est **transactionnelle et verrouillée** (cf. § 6.B) : lecture du compteur + incrément + commit dans la même transaction ; en cas de conflit, la transition échoue proprement avec propositions de dates alternatives (l'acompte déjà perçu n'est ni perdu ni dupliqué) ;
- Le même verrouillage s'applique à **l'annulation** (libération du créneau) et à la **réaffectation de date** ;
- Une table `production_slots (date, reserved_complex_count)` complète l'agrégat dérivé pour un verrouillage fin sans lecture de l'ensemble des commandes du jour ;
- **Définition "gâteau complexe"** (compte dans le quota) : recette de type `complex` OU ≥ 2 étages OU ≥ seuil de parts configuré — la qualification est calculée à la confirmation et stockée sur la commande (`is_complex`) ;
- Une commande confirmée **verrouille** son créneau : l'annulation (Module 6) libère la capacité ;
- Le planning J-2/J-1/J-0 est déduit automatiquement de la date de retrait/livraison et des durées de préparation configurées par type de gâteau (complexe/simple) ;
- Chevauchement impossible : deux commandes ne peuvent pas occuper le même créneau si le quota est dépassé.

### Rôles autorisés
- Chef Pâtissier : vue atelier, validation des étapes de préparation ;
- Gérant : capacité, fermetures, réaffectations ;
- Vendeur : lecture du calendrier (disponibilités).

### Surfaces UI
- **Page dédiée `Planning`** (calendrier interactif — données denses) ;
- **Drawer** de détail d'un jour/créneau (liste des tâches) ;
- Modale de fermeture/exception.

## Module 11 — Module Livraison & Communication WhatsApp

### Objectif
Simplifier la livraison (coursier tiers ou interne) et automatiser la communication client.

### 11.1 Livraison (coursier / livreur interne)
- **Fiche de livraison mobile** (vue mobile-first, prioritaire pour le rôle Livreur) :
  - Nom du client, téléphone WhatsApp cliquable ;
  - Adresse complète : commune, quartier, **repères locaux** ;
  - Commande : gâteau + accessoires, visuels de référence ;
  - **Solde FCFA exact à percevoir** + moyen de paiement attendu (Cash/MM) ;
  - Statuts : `Assignée → En route → Livrée / Échec de livraison` ;
  - Photo de preuve de livraison (optionnelle) ;
  - Encaissement du solde : Cash saisi, ou lien Wave/QR envoyé (Module 9) — la clôture est automatique au statut "Livrée" ;
- **Mode dégradé hors-ligne (PWA minimaliste, cf. § 6.D)** : Service Worker préchargeant les fiches assignées au départ de la tournée ; l'encaissement et le passage "Livrée" sont enregistrés localement (IndexedDB) puis **synchronisés automatiquement** au retour de la connexion, sans doublon (idempotence des paiements) ni perte (horodatage réel conservé) ; état de synchro visible sur chaque fiche ;
- Affectation d'une commande à un livreur (manuel par le Gérant/Vendeur) ;
- Historique des livraisons par livreur (performances, montants collectés).

### 11.2 Communication WhatsApp automatisée
Modèles configurables (Module 2) déclenchés automatiquement :

| Moment | Message | Destinataire |
|---|---|---|
| Devis créé | Devis + lien + montant acompte | Client |
| Acompte reçu (webhook) | Confirmation commande + numéro de référence | Client |
| J-1 (livraison/retrait) | Rappel date/heure + adresse/repères + solde | Client |
| Livraison assignée | Fiche de livraison (nom, adresse, repères, solde) | Livreur |
| Annulation | Motif + traitement (remboursement/avoir) | Client |
| Relance devis non confirmé (J+n configurable) | Relance + lien | Client |
| Solde impayé après livraison | Relance de paiement | Client |
| Alerte stock bas | Produit + quantité restante | Gérant/Chef |

### Règles de gestion
- Envois **asynchrones** (queue) : l'UI n'est jamais bloquée ; échec d'envoi → retry avec backoff puis état `failed` visible ;
- Respect du numéro WhatsApp principal du client pour toute communication commerciale ;
- Un livreur ne voit que **ses** livraisons du jour (jamais le portefeuille complet) ;
- "Livrée" est une transition verrouillée par le livreur affecté ou le Gérant ;
- **Actions hors-ligne** : toute action enregistrée localement passe par un statut `offline_pending` côté serveur jusqu'à réconciliation ; un conflit (commande modifiée entre-temps) est mis en file de résolution manuelle par le Gérant ; les montants et horodatages réels sont conservés (aucune réécriture) ;
- **Idempotence anti-doublon** : la clé `provider_ref` des `payments` garantit qu'un encaissement synchronisé depuis le hors-ligne n'est jamais enregistré deux fois.

### Rôles autorisés
- Livreur : fiches assignées, transitions, encaissement du solde ;
- Vendeur/Gérant : affectation, envoi manuel de messages, reprise d'une livraison ;
- Chef Pâtissier : lecture (savoir ce qui part).

### Surfaces UI
- Page `Livraisons du jour` (mobile-first, liste de fiches) ;
- **Fiche de livraison plein écran** (mobile) ;
- **Modale** d'affectation livreur ;
- Journal des messages envoyés (page `WhatsApp`).

## Module 12 — Plateforme d'Administration des Tenants (Super-Admin)

### Objectif
Piloter le SaaS depuis le **domaine central** (`cakeman.ci`, hors contexte tenancy) : cycle de vie des tenants, surveillance, support en lecture seule, socle de facturation. Les tenants n'ont **aucune** visibilité sur cette console.

### Fonctionnalités
- **Console super-admin** sur le domaine central (auth séparée `admin_users` — même stack Fortify/2FA/passkeys que les tenants, zone distincte) ;
- **Gestion des utilisateurs plateforme** : invitation par e-mail, activation/désactivation immédiate (sessions révoquées), attribution des rôles, création de rôles personnalisés par le Super-Admin (permissions choisies dans la liste exhaustive `admin.*`) — RBAC spatie/laravel-permission dans le schéma central `public` (Module 2 de la plateforme, cf. § 2.3) ;
- **Dashboard plateforme** : tenants actifs / essais / suspendus, MRR et croissance, volumes (commandes, CA), santé des files d'attente, webhooks en retard (> 15 min), erreurs applicatives ;
- **Gestion des tenants** : liste/recherche, création manuelle (ouverture du schéma), attribution de sous-domaine, plan d'abonnement, activation / **suspension** / annulation, prolongation d'essai ;
- **Fiche tenant** : statut, plan, domaine, métriques d'usage (utilisateurs actifs, commandes, stockage), derniers événements ;
- **Sessions support en lecture seule** : le super-admin ouvre une session temporaire sur le schéma d'un tenant (motif obligatoire, durée limitée, expiration automatique) — **aucune écriture possible** (rôle DB en lecture seule / transaction read-only) ;
- **Socle billing** : plans (`plans`), abonnements (`subscriptions` : `trialing → active → past_due → cancelled`), changements de plan, annulation d'abonnement, **encaissement Mobile Money et facturation (voir "Modèle tarifaire" ci-dessous)** ;
- **Domaines** : gestion des sous-domaines (attribution, vérification DNS pour domaine personnalisé — optionnel) ;
- **Opérations** : rejeu de webhook global, purge de cache d'un tenant, mode maintenance par tenant, export/effacement des données d'un tenant (portabilité RGPD) ;
- **Audit plateforme** : chaque action super-admin journalisée (`admin_audit_logs` : qui, quoi, quel tenant, quand, IP) — immuable.

### Règles de gestion
- Un **super-admin n'a jamais d'accès en écriture** aux données métier : les sessions support sont en lecture seule, horodatées, avec motif obligatoire et expiration automatique ;
- La **suspension** d'un tenant bloque immédiatement l'accès web (middleware tenancy) et le traitement des webhooks de paiement ; la levée de suspension restaure l'état sans perte de données ;
- La **suppression** d'un tenant n'est jamais directe : archivage (statut `cancelled`) puis effacement différé et tracé (RGPD) ;
- Les mutations de tenant (plan, statut, domaine) sont toutes journalisées dans `admin_audit_logs` ;
- L'annulation d'un abonnement n'efface pas le schéma : passage en `cancelled` + fenêtre de réactivation ;
- Aucune donnée métier d'un tenant n'est visible sur le dashboard plateforme (agrégats anonymisés uniquement) ;
- **Utilisateurs plateforme** : désactivation d'un utilisateur → sessions actives révoquées immédiatement ; suppression impossible si des `admin_audit_logs` lui sont rattachés (archivage `is_active = false` à la place) ; chaque mutation (création, rôle, désactivation) est journalisée ;
- Le rôle **Support** n'a jamais d'accès en écriture (ni sur la console au-delà de son périmètre, ni sur les données métier — sessions lecture seule uniquement) ;
- **Billing** : rappels d'échéance WhatsApp au gérant à J-7 / J-3 / J-1 ; **période de grâce de 7 jours en lecture seule** après échéance non payée → puis **suspension** (accès bloqué) → **archivage** après 30 jours ; aucune perte de données (réactivation = restauration complète) ; chaque webhook de paiement d'abonnement est journalisé dans `admin_webhook_logs` (même mécanique de rejeu que le Module 9) ;
- **Enforcement des limites du plan** : utilisateurs max, commandes/mois, stockage — alerte douce au dépassement, blocage à 100 % de la limite (message explicite + proposition d'upgrade) ; les limites sont appliquées côté service, jamais seulement côté UI ;
- Le paiement des abonnements passe par les **mêmes agrégateurs que les tenants (CinetPay/Wave…), en mode "compte plateforme"** (clés dédiées, hors `tenant_settings`) — lien de paiement Mobile Money généré à chaque facture, confirmé par webhook (mécanique Module 9 réutilisée).

### Rôles autorisés

RBAC plateforme (Spatie dans le schéma `public`), permissions centralisées en code (enum `AdminPermission` : `admin.tenant.view`, `admin.tenant.manage`, `admin.support.session`, `admin.billing.manage`, `admin.user.manage`, `admin.monitoring.view`, `admin.audit.view`, `admin.webhook.replay`, `admin.maintenance.toggle`…) :

| Rôle | Périmètre | Droits principaux |
|---|---|---|
| **Super-Admin** | Console complète | Tout : tenants, utilisateurs plateforme, billing, support, monitoring, audit, opérations, rôles & permissions |
| **Support** | Assistance | Lecture tenants, sessions support lecture seule, monitoring, rejeu de webhook, audit en lecture — aucune écriture |
| **Billing** | Abonnements | Plans, abonnements, changements de plan, rapports MRR |

- Aucun rôle tenant n'accède à la console ; aucun rôle plateforme n'accède aux données métier (hors sessions support en lecture seule).

### Modèle tarifaire & cycle de vie des abonnements (inspiré du modèle Deelo, adapté FCFA/Mobile Money)

**Principe freemium + paliers** : le plan **Gratuit** fait découvrir le produit (limites volontairement basses, branding Cakeman) ; les plans payants lèvent les limites. Essai gratuit de **14 jours sans carte** sur les plans payants (statut `trialing`).

| Plan | Prix (FCFA) | Inclus | Limites (enforcement) |
|---|---|---|---|
| **Gratuit** | 0 F/mois | Modules de base (devis, caisse rapide, clients, stocks, 1 atelier) | 1 utilisateur, 15 commandes/mois, 1 Go stockage, branding "Cakeman", support communautaire WhatsApp |
| **Starter** | 5 000 F/mois — 48 000 F/an (-20 %) | Tout le périmètre v1 | 3 utilisateurs, 60 commandes/mois, 10 Go, sans branding, support e-mail/WhatsApp |
| **Pro** | 12 500 F/mois — 120 000 F/an (-20 %) | + rappels WhatsApp avancés, API, exports illimités | 10 utilisateurs, commandes illimitées, 50 Go, support prioritaire 24 h |
| **Business** | 25 000 F/mois — 240 000 F/an (-20 %) | + SLA 99,9 %, SSO (v2), gestionnaire de compte | Utilisateurs illimités, stockage illimité |

**Add-ons à la carte** (`plan_addons`) : utilisateur supplémentaire (2 500 F/mois), stockage +10 Go (1 500 F/mois), volume WhatsApp +500 messages (2 000 F/mois), support prioritaire 4 h (7 500 F/mois).

**Cycle de facturation** (mensuel ou annuel, réduction 20 % sur l'annuel) :
1. **Trial 14 jours** sans carte bancaire ni Mobile Money requis (`trialing`, `trial_ends_at`) ;
2. **Activation** : à la fin de l'essai (ou dès la souscription), génération de la 1ʳᵉ facture `invoices` (période, montant FCFA entier) + **lien de paiement Mobile Money** (Wave/OM/MTN/Moov via agrégateur plateforme) envoyé par WhatsApp au gérant ;
3. **Confirmation par webhook** (idempotent, `provider_ref` unique) → `paid` → abonnement `active` ;
4. **Renouvellement mensuel/annuel** : facture générée en avance (J-7), rappels WhatsApp J-7/J-3/J-1 ;
5. **Impayé** : période de grâce 7 jours en **lecture seule** (`past_due`) → **suspension** du tenant (web + webhooks bloqués) → **archivage** après 30 jours (`cancelled`, effacement différé RGPD) ;
6. **Changement de plan / add-ons** : prorata sur la période en cours, appliqué immédiatement, journalisé.

### Surfaces UI
- Page dédiée `Plateforme > Dashboard` ;
- Page `Plateforme > Tenants` (liste + recherche + pagination) + fiche tenant ;
- **Modale** création/édition d'un tenant (plan, domaine, statut) ;
- Page `Plateforme > Équipe` (liste des utilisateurs + modales invitation/édition, rôles & permissions) ;
- Page `Plateforme > Abonnements` (plans, add-ons, statuts, changements, prorata) ;
- Page `Plateforme > Factures` (liste, statuts, relances, lien de paiement MM) ;
- Page `Plateforme > Support` (sessions de lecture, historique) ;
- Page `Plateforme > Monitoring` (queues, webhooks, erreurs) ;
- Page `Plateforme > Audit` (journal immuable, filtres par tenant/action).

## Module 13 — Site Public & Acquisition (Landing)

### Objectif
Convertir les visiteurs en tenants : présenter le produit, la grille tarifaire (Module 12), collecter les prospects et déclencher l'onboarding (Module 1). Hébergé sur le domaine central `cakeman.ci` (zone publique — hors tenancy et hors console).

### Fonctionnalités
- **Landing page** : hero produit, cycle du pâtissier sur commande (problème → solution), bénéfices, témoignages, FAQ, CTA "Commencer gratuitement" et "Voir les tarifs" ;
- **Pages tarifs** : grille des 4 plans + add-ons lue **directement depuis `plans`/`plan_addons`** (zéro duplication : la page reflète la grille de la plateforme) ;
- **Capture de leads (pré-inscription)** : formulaire nom + numéro WhatsApp + taille de l'atelier → table `landing_leads` ; suivi manuel côté plateforme (statut `new` → `contacted` → `converted`) ;
- **Inscription** : CTA → onboarding tenant (Module 1) — plan **Gratuit** par défaut, essai 14 jours si passage en plan payant (Module 12) ; le lead est marqué `converted` et lié au tenant créé ;
- **Pages annexes** : FAQ, mentions légales (éditeur, RCCM plateforme), politique de confidentialité (RGPD : données traitées, export/effacement), contact support ;
- **SEO & partage** : métadonnées (Open Graph), sitemap, données structurées (Product/Pricing), pages statiques légères (aucun JS critique) ;
- **Rapport d'acquisition** : essais démarrés/convertis, leads par source (dashboard plateforme).

### Règles de gestion
- Le site public est **non authentifié et hors tenancy** : aucun accès aux données métier ;
- Les tarifs affichés proviennent des tables plateforme — **jamais codés en dur** dans le front ;
- Un lead est **dédoublonné** par numéro WhatsApp normalisé (+225) ; un lead converti est lié au tenant (`converted_tenant_id`) et ne peut pas être re-converti ;
- Formulaire protégé contre le spam (honeypot + throttle) ;
- La landing respecte les exigences de performance mobile (§ 5.2 : pages critiques < 150 Ko).

### Rôles autorisés
- **Accès public** : lecture des pages ;
- **Super-Admin / Billing** : consultation des leads (`admin.lead.view`), marquage contacté, export CSV, lien vers le tenant créé.

### Surfaces UI
- Pages publiques statiques (landing, tarifs, FAQ, mentions, confidentialité) ;
- Page `Plateforme > Leads` (liste, filtres par statut, export) ;
- **Modale** de suivi d'un lead (statut, notes).

---

# 4. MODÈLE DE DONNÉES (SCHÉMA CONCEPTUEL POSTGRESQL)

## 4.1 Architecture physique

- **Une seule base PostgreSQL `cakeman_db`**, contenant :
  - **Schéma central `public`** — accueil du super-admin plateforme :
    - `tenants` (stancl), `domains` (stancl), tables plateforme (`admin_users`, RBAC Spatie plateforme, `plans`, `plan_addons`, `subscriptions`, `subscription_addons`, `invoices`, `admin_webhook_logs`, `admin_audit_logs`, `landing_leads`, Modules 12-13) ;
  - **Un schéma par tenant** (`tenant_{uuid}`, via `PostgresSchemaManager`) — toutes les tables métier ci-dessous, créées dynamiquement à l'installation du tenant (bascules automatiques par `SET search_path TO tenant_{uuid}, public`).

## 4.2 Schéma central (`public`)

### `tenants` (stancl)
| Colonne | Type | Notes |
|---|---|---|
| id | uuid PK | Identifiant tenant |
| name | varchar | Nom commercial |
| status | enum | `active`, `suspended`, `trialing`, `cancelled` | Reste `active` (lecture seule) pendant la période de grâce d'un impayé (`subscriptions.status = past_due`) |
| created_at / updated_at | timestamptz | |

> Le plan n'est plus porté par `tenants.plan` (v1.0) : il vit dans `subscriptions` (Module 12).

### `domains` (stancl)
| Colonne | Type | Notes |
|---|---|---|
| id | uuid PK | |
| domain | varchar UNIQUE | `nompatisserie.cakeman.ci` |
| tenant_id | uuid FK→tenants | |

### `admin_users` (super-admins — Module 12)
Structure **strictement identique** à `users` (migration Laravel + extensions Fortify du starter kit) — la même stack d'authentification sert la plateforme et les tenants.

| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| email | varchar UNIQUE | |
| email_verified_at | timestamptz NULL | |
| password | varchar | |
| two_factor_secret | text NULL | Fortify 2FA |
| two_factor_recovery_codes | text NULL | Fortify 2FA |
| two_factor_confirmed_at | timestamptz NULL | Fortify 2FA |
| remember_token | varchar NULL | |
| is_active | boolean | Désactivation = sessions révoquées (Module 12) |
| last_login_at | timestamptz NULL | |
| created_at / updated_at | timestamptz | |

> **Tables annexes identiques** (mêmes migrations que les tenants, exécutées dans `public`) : `password_reset_tokens`, `sessions`, `passkeys` (FK → `admin_users`) — les passkeys (WebAuthn) fonctionnent donc aussi pour les super-admins. En pratique, les migrations Laravel sont rendues **réutilisables** : nom de table paramétrable (`users` en tenant-scope, `admin_users` en scope central), mêmes fichiers.
>
> **RBAC plateforme** : tables Spatie (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`) dans le schéma `public`, comme pour les tenants (cf. § 2.3) — rôles racines `Super-Admin`, `Support`, `Billing`, permissions en enum `admin.*`.

### `plans` (offres SaaS — Module 12)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| code | varchar UNIQUE | `free`, `starter`, `pro`, `business` |
| name | varchar | |
| monthly_price | int | FCFA, entier |
| annual_price | int NULL | FCFA/an (-20 % vs mensuel) |
| features | jsonb | Modules inclus, branding, support level |
| limits | jsonb | Limites enforceables : max_users, max_orders_per_month, storage_gb, whatsapp_messages/mois |
| is_active | boolean | |
| timestamps | | |

### `plan_addons` (add-ons à la carte — Module 12)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| code | varchar UNIQUE | `extra_user`, `extra_storage`, `extra_whatsapp`, `priority_support` |
| name | varchar | |
| monthly_price | int | FCFA, entier |
| unit | varchar NULL | `user`, `10gb`, `500_msg`, `month` |
| is_active | boolean | |
| timestamps | | |

### `subscriptions` (Module 12)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| tenant_id | uuid FK→tenants UNIQUE | Un abonnement actif par tenant |
| plan_id | FK→plans | |
| billing_period | enum | `monthly`, `annual` |
| status | enum | `trialing`, `active`, `past_due`, `cancelled` |
| trial_ends_at | timestamptz NULL | Essai 14 jours sans carte |
| current_period_start / current_period_end | timestamptz NULL | |
| grace_until | timestamptz NULL | 7 jours lecture seule après impayé |
| cancelled_at | timestamptz NULL | |
| timestamps | | |

### `subscription_addons` (Module 12)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| subscription_id | FK→subscriptions | |
| plan_addon_id | FK→plan_addons | |
| quantity | int | |
| added_at / removed_at | timestamptz NULL | Prorata sur la période en cours |
| timestamps | | |

### `invoices` (facturation des abonnements — Module 12)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| number | varchar UNIQUE | `SC-YYYY-0001` |
| subscription_id | FK→subscriptions | |
| period_start / period_end | timestamptz | |
| amount | int | FCFA, entier |
| status | enum | `open`, `paid`, `overdue`, `void` |
| method | enum NULL | `wave`, `orange_money`, `mtn`, `moov` (Mobile Money) |
| payment_url | varchar NULL | Lien de paiement Mobile Money généré (agrégateur plateforme) — pas de FK : le schéma central ne référence jamais les tables tenant |
| provider_ref | varchar NULL UNIQUE | Idempotence webhook |
| paid_at | timestamptz NULL | |
| timestamps | | |

> Le paiement des abonnements passe par les **mêmes agrégateurs que les tenants** (CinetPay/Wave…), en mode "compte plateforme" (clés dédiées, hors `tenant_settings`) — lien Mobile Money + webhook idempotent, exactement comme les paiements des commandes (Module 9). Chaque webhook reçu est journalisé dans `admin_webhook_logs`.

### `admin_webhook_logs` (webhooks billing — Module 12)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| provider | enum | `wave`, `orange_money`, `mtn`, `moov` |
| payload | jsonb | Brut reçu |
| status | enum | `received`, `processed`, `ignored`, `mismatch`, `failed` |
| related_invoice_id | FK→invoices NULL | Facture corrélée (via `provider_ref`) |
| error | text NULL | |
| created_at | timestamptz | |

### `admin_audit_logs` (Module 12)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| admin_user_id | FK→admin_users | |
| tenant_id | uuid FK→tenants NULL | Cible (NULL = action globale) |
| action | varchar | `tenant.suspend`, `subscription.change_plan`, `support.session_open`… |
| properties | jsonb | Motif, avant/après, durée session |
| ip_address | inet | |
| created_at | timestamptz | Immuable |

### `landing_leads` (prospects du site public — Module 13)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| whatsapp_phone | varchar INDEX | Normalisé +225, dédoublonnage |
| name | varchar | |
| business_size | varchar NULL | Petit atelier / boutique / grossiste |
| status | enum | `new`, `contacted`, `converted` |
| converted_tenant_id | uuid FK→tenants NULL | Tenant créé depuis ce lead |
| source | varchar NULL | Referrer / campagne |
| notes | text NULL | |
| created_at | timestamptz | |

## 4.3 Schéma tenant — entités principales

### `tenant_settings`
Clé/valeur typé + blobs JSONB pour structures libres.

| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | Mono-ligne (ou versionnée) |
| business_name | varchar | |
| logo_path | text | |
| slogan | varchar | |
| address | jsonb | `{commune, quartier, landmarks}` |
| whatsapp_number | varchar | Format +225 |
| legal_mentions | jsonb | RCCM, contribuable |
| deposit_default_percent | smallint | 30/50/100 |
| quote_expiry_days | smallint | |
| min_notice_hours | int | Ex. 48 h |
| cancellation_policy | enum | `flexible`, `intermediate`, `strict` |
| free_cancellation_deadline_hours | int | |
| opening_hours | jsonb | Par jour |
| closed_days | jsonb | Dates/Jours |
| daily_cake_quota | int | Gâteaux complexes/jour |
| complex_cake_min_servings | int NULL | Seuil de parts qualifiant un gâteau "complexe" |
| voucher_validity_months | int | Validité des avoirs (Module 6, ex. 12) |
| quote_followup_days | int | Relance des devis non confirmés (Module 4.3, J+n) |
| sold_owed_followup_days | int | Relance des créances (Module 8) |
| payment_providers | jsonb | Clefs chiffrées, modes |
| whatsapp_templates | jsonb | Modèles de messages |
| alert_threshold_default | numeric | |
| created_at / updated_at | timestamptz | |

### `users` (étendu Fortify)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| email | varchar UNIQUE | |
| password | varchar | |
| phone | varchar | Numéro WhatsApp pro (optionnel) |
| is_active | boolean | |
| last_login_at | timestamptz | |
| timestamps | | |

> Le rôle n'est **pas** stocké en colonne : Spatie (`model_has_roles`) est l'unique source de vérité (le champ `role_id` du v1.0 a été retiré pour éviter toute dérive).

*Tables Spatie : `roles`, `permissions`, `model_has_roles`, `model_has_permissions`.*

### `customers`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| whatsapp_phone | varchar INDEX | Normalisé +225, recherche |
| email | varchar NULL | |
| default_address | jsonb | `{commune, quartier, landmarks}` |
| preferences | text | |
| allergies | text | |
| notes | text | |
| voucher_balance | int | Solde avoirs (FCFA, agrégat dérivé) |
| total_spent | int | CA généré (agrégat dérivé) |
| last_order_at | timestamptz | |
| archived_at | timestamptz NULL | |
| created_by | FK→users | |
| timestamps | | |

### `delivery_zones`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar | `Cocody`, `Marcory`… |
| fee | int | FCFA |
| is_active | boolean | |
| estimated_minutes | int | |

### `products`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| type | enum | `ingredient`, `packaging`, `accessory` |
| name | varchar | |
| category | varchar NULL | |
| unit | enum | `g`, `kg`, `L`, `pcs` |
| unit_price | int NULL | Prix de vente (accessoires) |
| unit_cost | int NULL | Coût unitaire |
| stock_qty | numeric | Stock courant (toujours dans l'unité du produit) |
| reserved_qty | numeric | Stock réservé (commandes confirmées, M5/M7) |
| min_stock | numeric | Seuil d'alerte |
| image_path | text NULL | |
| is_active | boolean | |
| timestamps | | |

> **Unité** : `stock_qty` et `reserved_qty` sont toujours exprimés dans l'unité du produit (`unit`) ; aucune conversion implicite g↔kg — la saisie impose l'unité du produit (évite les erreurs de facteur 1 000).

### `recipes` & `recipe_ingredients`
| Table | Colonnes clés |
|---|---|
| `recipes` | id PK, name, servings, type (`simple`, `complex`), preparation_days jsonb (J-2/J-1/J-0), status, timestamps |
| `recipe_ingredients` | id PK, recipe_id FK, product_id FK, quantity numeric, unit enum, FK composite unique (recipe_id, product_id) |

### `price_lists` & `price_list_rules` (grille de prix par parts/saveur — Module 4.1)
| Table | Colonnes clés |
|---|---|
| `price_lists` | id PK, name, is_active, timestamps |
| `price_list_rules` | id PK, price_list_id FK, servings_min int, servings_max int NULL, tier smallint NULL (étages), flavor varchar NULL (NULL = toutes), price int, timestamps |

> Résolution : meilleure correspondance `servings/tier/flavor` de la grille active ; à défaut, tarif manuel (cake designer).

### `orders`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| number | varchar UNIQUE | `SOF-YYYY-0001` (génération séquentielle verrouillée, § 6.B) |
| customer_id | FK→customers | Client générique "Comptoir" pour la caisse rapide |
| status | enum | `draft`, `quote_sent`, `confirmed`, `in_production`, `ready`, `delivered`, `cancelled`, `expired` |
| order_type | enum | `custom_cake`, `accessory_sale`, `mixed` |
| cake_spec | jsonb | `{servings, tiers, flavors, theme, allergies, options}` |
| reference_images | jsonb | Chemins WebP compressés |
| is_complex | boolean NULL | Qualification quota (Module 10), calculée à la confirmation |
| quote_token | varchar UNIQUE NULL | Token du lien public de devis (Module 4.3) |
| quote_expires_at | timestamptz NULL | Expiration du devis (scheduler → `expired`) |
| quote_viewed_at | timestamptz NULL | Statut "Vu" du lien public |
| recipe_id | FK→recipes NULL | Fiche technique appliquée |
| pickup_date | date | |
| pickup_time | time | |
| delivery_type | enum | `pickup`, `delivery` |
| delivery_zone_id | FK→delivery_zones NULL | |
| delivery_fee | int | |
| deposit_percent | smallint | |
| deposit_amount | int | |
| balance_due | int | Solde restant |
| subtotal | int | |
| total | int | Subtotal + frais |
| voucher_applied | int | Avoir utilisé |
| cancellation_policy_applied | enum NULL | |
| cancel_reason | text NULL | |
| cancelled_at | timestamptz NULL | |
| cancelled_by | FK→users NULL | |
| created_by | FK→users | |
| confirmed_at / production_at / ready_at / delivered_at | timestamptz NULL | Timeline |
| timestamps | | |

### `order_items`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | FK→orders | |
| product_id | FK→products NULL | Accessoires |
| description | text | Libellé libre |
| quantity | int | |
| unit_price | int | |
| total | int | |
| stock_deducted | boolean | Déstockage effectué |

### `order_ingredient_consumptions` (consommation réelle par ligne — Module 7, § 6.C)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | FK→orders | |
| recipe_id | FK→recipes | Fiche technique appliquée |
| product_id | FK→products | |
| quantity_planned | numeric | Quantité de la recette |
| quantity_actual | numeric | Quantité réellement consommée |
| unit | enum | `g`, `kg`, `L`, `pcs` |
| unit_cost | int NULL | Coût unitaire au moment de la consommation |
| is_substitution | boolean | Ligne remplacée (originelle ou de substitution) |
| replaced_movement_id | FK→inventory_movements NULL | Mouvement de substitution lié |
| timestamps | | |

> Le **coût réel de la commande** (CMV, Module 8) est la somme de `quantity_actual × unit_cost` de cette table — les substitutions y sont tracées ligne à ligne sans toucher aux mouvements globaux.

### `payments`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | FK→orders NULL | NULL uniquement en dégradation (caisse rapide sans express) |
| customer_id | FK→customers | Client générique "Comptoir" par défaut |
| type | enum | `deposit`, `balance`, `full`, `refund`, `penalty`, `voucher_redemption` |
| amount | int | Positif encaissé / négatif remboursé |
| method | enum | `cash`, `wave`, `orange_money`, `mtn`, `moov`, `card`, `voucher`, `transfer` |
| status | enum | `pending`, `offline_pending`, `succeeded`, `failed`, `refunded` |
| provider | varchar NULL | CinetPay/Bizao/… |
| provider_ref | varchar NULL UNIQUE | Idempotence webhook |
| payment_link_id | FK→payment_links NULL | Lien de paiement à l'origine du flux |
| recorded_by | FK→users NULL | NULL = webhook/système |
| paid_at | timestamptz | |
| timestamps | | |

### `payment_links` (liens de paiement pré-générés — Module 9)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | FK→orders | |
| kind | enum | `deposit`, `balance`, `refund` |
| provider | enum | `wave`, `cinetpay`, `bizao`, `paystack` |
| amount_expected | int | Montant attendu (comparé au webhook → `mismatch`) |
| status | enum | `pending`, `paid`, `expired`, `failed`, `mismatch` |
| provider_ref | varchar NULL UNIQUE | Référence fournisseur du lien |
| payment_url | text NULL | URL/QR générée en amont de la tournée |
| expires_at | timestamptz NULL | |
| created_by | FK→users | Livreur/Vendeur/Gérant |
| paid_at | timestamptz NULL | |
| timestamps | | |

### `vouchers` (Avoirs)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| code | varchar UNIQUE | Code avoir unique |
| customer_id | FK→customers | |
| source_order_id | FK→orders | Commande annulée |
| amount | int | Montant initial |
| amount_used | int | |
| status | enum | `active`, `used`, `expired`, `revoked` |
| expires_at | date | |
| timestamps | | |

### `inventory_movements`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| product_id | FK→products | |
| type | enum | `purchase`, `sale`, `production_out`, `loss`, `adjustment`, `return`, `substitution` |
| quantity | numeric | Signé (+/-) |
| unit | enum | |
| unit_cost | int NULL | Coût au mouvement |
| reason | text NULL | Motif obligatoire (pertes/ajustements) |
| order_id | FK→orders NULL | Rattaché commande (production/annulation) |
| reversed_movement_id | FK→inventory_movements NULL | Chaînage des contre-passations (substitution, § 6.C) |
| user_id | FK→users | |
| created_at | timestamptz | Immutable |

### `deliveries`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | FK→orders UNIQUE | |
| courier_id | FK→users NULL | Livreur interne (coursier tiers : nom libre) |
| courier_name | varchar NULL | Coursier tiers |
| status | enum | `assigned`, `in_transit`, `delivered`, `failed`, `offline_pending` |
| amount_collected | int NULL | Solde encaissé |
| collection_method | enum NULL | |
| proof_photo_path | text NULL | |
| assigned_at / in_transit_at / delivered_at | timestamptz NULL | |
| notes | text | |

### `cash_closures` (clôture de caisse quotidienne — Module 8)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| closed_on | date UNIQUE | Une clôture par jour |
| expected_total | int | Trésorerie théorique (journal des paiements du jour) |
| actual_total | int | Trésorerie comptée |
| difference | int | Écart (signé) |
| notes | text NULL | Justification de l'écart |
| closed_by | FK→users | Gérant |
| closed_at | timestamptz | |

### `production_slots` (réservation de créneaux — § 6.B)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| slot_date | date INDEX | Date du créneau |
| quota | int | Quota journalier (snapshot config) |
| reserved_complex_count | int | Commandes complexes confirmées (verrouillable `SELECT ... FOR UPDATE`) |
| UNIQUE | (slot_date) | Une ligne par date |

### `whatsapp_messages` (journal de communication)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| template_key | varchar | `quote_sent`, `reminder_j1`… |
| recipient | varchar | |
| order_id | FK→orders NULL | |
| content | text | |
| status | enum | `queued`, `sent`, `failed` |
| provider_message_id | varchar NULL | |
| sent_at | timestamptz NULL | |
| timestamps | | |

### `webhook_logs`
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| provider | varchar | |
| event | varchar | |
| payload | jsonb | Brut horodaté |
| signature_valid | boolean | |
| status | enum | `received`, `processed`, `ignored`, `mismatch`, `failed` |
| processed_at | timestamptz NULL | |
| error | text NULL | |

### `activity_logs` (audit)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK→users | |
| subject_type / subject_id | polymorphic | |
| event | varchar | `created`, `cancelled`, `payment.recorded`… |
| properties | jsonb | Avant/après, motif |
| ip_address | inet | |
| created_at | timestamptz | |

## 4.4 Relations principales

```
tenants 1──∞ domains
tenants 1──1 tenant_settings

tenants 1──1 subscriptions ∞──1 plans
subscriptions 1──∞ subscription_addons ∞──1 plan_addons
subscriptions 1──∞ invoices (payment_url stocké sur la facture, pas de FK vers les schémas tenants)
invoices 1──∞ admin_webhook_logs (nullable)
admin_users ∞──1 roles (Spatie model_has_roles, schéma public)
admin_users 1──∞ admin_audit_logs
tenants 1──∞ admin_audit_logs (nullable)
landing_leads ∞──1 tenants (converted_tenant_id, nullable)

users ∞──1 roles (Spatie model_has_roles)
users 1──∞ customers (created_by)
customers 1──∞ orders
customers 1──∞ vouchers
orders ∞──1 delivery_zones
orders 1──∞ order_items
order_items ∞──1 products
orders 1──∞ payments
payments ∞──1 payment_links (nullable)
payment_links ∞──1 orders
orders 1──0..1 deliveries
orders ∞──1 recipes (nullable)
orders ── date (pickup_date) ── production_slots   (réservation par date, pas de FK, § 6.B)
orders 1──∞ order_ingredient_consumptions ∞──1 recipes / products
recipes 1──∞ recipe_ingredients ∞──1 products
price_lists 1──∞ price_list_rules
products 1──∞ inventory_movements
inventory_movements ∞──1 orders (nullable)
inventory_movements 0..1──1 inventory_movements (reversed_movement_id, contre-passations)
orders 1──∞ whatsapp_messages (nullable)
cash_closures ∞──1 users (closed_by)
```

## 4.5 Index & contraintes clés (PostgreSQL)

- `orders(status, pickup_date)` — filtre planning/liste ;
- `orders(customer_id, status)` — historique client ;
- `orders(quote_token)` UNIQUE — lien public de devis ;
- `payments(order_id)`, `payments(provider_ref)` UNIQUE — idempotence ;
- `payment_links(order_id)`, `payment_links(provider_ref)` UNIQUE, `payment_links(provider, status)` — lien pré-généré et webhook associé ;
- `customers(whatsapp_phone)` — recherche dédoublonnage ; index GIN `pg_trgm` sur `customers(name)` pour la recherche par nom ;
- `inventory_movements(product_id, created_at)` — journal stock ;
- `production_slots(slot_date)` UNIQUE — verrouillage quota (§ 6.B) ;
- `vouchers(code)` UNIQUE ; `deliveries(order_id)` UNIQUE ; `cash_closures(closed_on)` UNIQUE ;
- `subscriptions(tenant_id)` UNIQUE — un abonnement actif par tenant ;
- `invoices(number)` UNIQUE, `invoices(provider_ref)` UNIQUE — idempotence webhook billing, `invoices(subscription_id, period_start)` — une facture par période ;
- `admin_audit_logs(tenant_id, created_at)` — historique par tenant ;
- `admin_webhook_logs(status, created_at)` — suivi des webhooks billing ;
- `landing_leads(whatsapp_phone)` — dédoublonnage ; `landing_leads(status)` — suivi d'acquisition ;
- `order_ingredient_consumptions(order_id)`, `(product_id)` — CMV par commande ;
- JSONB `cake_spec`, `address`, `tenant_settings` indexés en GIN selon usage ;
- Contraintes CHECK : `payments.amount != 0`, cohérence des dates (`delivered_at >= confirmed_at`, `cancelled_at >= confirmed_at`, `quote_expires_at > created_at`).
- **Pas de CHECK `stock_qty >= 0`** : la rupture confirmée par le Chef (Module 7, motif obligatoire) peut porter le stock en négatif — c'est un contrôle **applicatif** (transaction + alerte bloquante), pas une contrainte SQL.

---

# 5. EXIGENCES NON FONCTIONNELLES

## 5.1 Sécurité & étanchéité multi-tenant

- **Isolation stricte par schéma** : un schéma PostgreSQL par tenant (`PostgresSchemaManager` de stancl/tenancy, bascule `search_path`) ; aucun modèle partagé ; toute requête d'un tenant ne peut résoudre que les tables de son schéma + `public` (données centrales uniquement) ;
- **Vérification systématique** : middleware tenancy obligatoire sur toutes les routes web/API (sauf central) ; tests d'étanchéité inter-tenant obligatoires (accès croisé = échec de test) ;
- **Chiffrement au repos** : clefs API de paiement et WhatsApp via Laravel `Crypt` ; secrets jamais en clair en base ;
- **Chiffrement en transit** : HTTPS obligatoire (HSTS), sous-domaines wildcard ;
- **Webhooks** : vérification de signature HMAC obligatoire, journalisation brute, rejeu contrôlé ;
- **Sessions & CSRF** : framework standard + durée de session courte sur mobile ; invalidation des sessions à la désactivation d'utilisateur ;
- **RBAC strict** : chaque action métier derrière une permission Spatie + policy ; vérification en composant ET en service (pas uniquement côté UI) ; même règle pour la console plateforme (Module 12, permissions `admin.*`) ;
- **Audit trail** : journal d'activité immuable pour les actions financières, annulations, modifications de configuration, accès super-admin ;
- **Sessions support (Module 12)** : accès super-admin au schéma d'un tenant **exclusivement en lecture seule** (rôle DB `readonly` / transaction read-only), motif obligatoire, durée limitée avec expiration automatique, chaque ouverture/fermeture journalisée dans `admin_audit_logs` ;
- **Backups** : sauvegarde automatique de la base `cakeman_db` (PgDump) — restauration **par schéma tenant** testée trimestriellement ; rétention 30 jours + snapshot hebdomadaire ;
- **Confidentialité** : données clients (téléphones, adresses, repères) traitées comme sensibles ; export/effacement par tenant (portabilité) ; minimisation dans les logs.

## 5.2 Ergonomie & performance mobile 3G/4G

- **Mobile-first** : parcours complets utilisables sur écran 360 px ; navigation en onglets bas sur mobile ; zones tactiles ≥ 44 px ;
- **Poids des pages** : pages critiques < 150 Ko (HTML + JS) ; images WebP compressées à l'upload (max 1 200 px, qualité 70) ; lazy loading ;
- **Livewire optimisé** : composants lazy pour les listes/détails, debounce sur recherches, pagination serveur (jamais de rendu intégral) ;
- **Résilience réseau** : brouillon de commande sauvegardé automatiquement ; messages d'état réseau explicites ; retry des requêtes ;
- **Perception de performance** : skeletons de chargement (Flux), optimisme limité aux actions sûres ;
- **Cache** : mise en cache des données de référence (zones, produits, paramètres) ; purge par tenant.

## 5.3 Traitement asynchrone & fiabilité

- **Files d'attente** : notifications WhatsApp, compression d'images, webhooks sortants, relances programmées → jobs asynchrones (queue dédiée `notifications`, `media`, `payments`) ;
- **Tenant-aware jobs** : chaque job restaure le contexte tenant avant exécution ; échec = retry + backoff puis `failed` visible ;
- **Idempotence des webhooks** : traitement unique garanti par clé `provider_ref` unique ;
- **Planification (scheduler)** : relance des devis expirés, rappels J-1, expiration des avoirs, clôtures quotidiennes ;
- **Supervision** : monitoring des queues (taille, échecs), alertes en cas de retard de traitement des paiements (webhook non traité > 15 min) ;
- **Journalisation structurée** : logs JSON avec tenant_id, corrélation requête/job.

## 5.4 Disponibilité & opérations

- **Objectif** : 99,5 % de disponibilité mensuelle ; fenêtres de maintenance annoncées ;
- **Déploiement** : pipeline CI/CD (tests Pest + analyse statique PHPStan + Pint) sur chaque merge ; migrations de schéma versionnées et tenant-scoped ;
- **Environnements** : staging + production ; données de staging jamais copiées depuis la production ;
- **Sauvegarde/restauration** : procédure documentée et testée (RPO ≤ 24 h, RTO ≤ 4 h).

## 5.5 Qualité & testabilité

- **Tests** : Pest (feature + unit), couverture ciblée ≥ 80 % sur les services critiques (pricing, annulation, stock, paiements) ;
- **Tests d'étanchéité multi-tenant** obligatoires : utilisateur tenant A ne peut accéder aux données tenant B ;
- **Tests de flux** : Wizard de commande, transitions d'état, webhooks (simulation fournisseur), idempotence ;
- **Standard de code** : Laravel Pint, PHPStan niveau max, conventions MFC Livewire documentées (§ 2.4) ;
- **Accessibilité** : contrastes, labels, focus clavier (composants Flux), messages d'erreur explicites.

---

# 6. AXES D'AMÉLIORATION & POINTS D'ATTENTION TECH/MÉTIER

> La version 1.1 couvre l'ensemble du périmètre fonctionnel. Cette section consigne les ajustements pratiques et points de vigilance à intégrer **dès la conception** (schéma, API, composants) pour éviter les reprises coûteuses.

## 6.A Performance mobile & gestion des visuels — compression côté client

**Constat**
L'upload de photos HD (visuels de référence, Module 4) sur réseau 3G/4G instable peut être lent, échouer ou consommer le quota data du client. La compression asynchrone côté serveur (WebP) réduit le stockage, mais **pas** le volume transféré au moment de l'envoi.

**Recommandation**
- **Compression/redimensionnement côté client** (Canvas HTML5 / WebWorker JS) **avant** l'envoi au serveur :
  - Redimensionnement à 1 200 px max sur le plus grand côté ;
  - Encodage WebP/JPEG qualité ~70 ;
  - Cible : **payload d'upload < 300 Ko** dès l'émission ;
- Fallback automatique : si l'API Canvas/WebP est indisponible, upload du fichier brut avec compression serveur (dégradation élégante) ;
- Aperçu local immédiat (optimiste) du visuel avant upload ; indicateur de progression par image ;
- Reprise d'upload en cas de coupure (nouvel essai du fichier compressé, pas de perte du brouillon) ;
- Mise en attente des uploads : les visuels peuvent être transmis **après** l'enregistrement du brouillon (étape non bloquante du Wizard).

**Impact**
- Module 4 (Wizard étape 2), § 5.2 (performance mobile), médias stockés dans le schéma tenant.

## 6.B Gestion des quotas d'atelier — concurrence à la réservation

**Constat**
Le quota journalier est vérifié au passage en "Confirmée" (Module 10). Deux vendeurs confirmant simultanément un acompte pour la même date peuvent **dépasser le quota** par inadvertance (race condition read-check-write).

**Recommandation**
- **Verrouillage pessimiste de ligne** lors du traitement du paiement / transition vers "Confirmée" :
  - Transaction PostgreSQL avec `SELECT ... FOR UPDATE` sur la ligne de la commande (et/ou ligne d'agrégat `production_slots` par date) ;
  - Le compteur de créneaux réservés est lu **dans la même transaction verrouillée**, incrémenté et commité atomiquement ;
- En cas de conflit (quota atteint pendant la transaction) : la transition échoue proprement, message explicite au vendeur (dates alternatives proposées), aucun acompte perdu (l'argent reçu est resté sur le wallet du fournisseur ou est remboursable) ;
- Verrouillage équivalent sur l'**annulation** (libération du créneau) et sur la **réaffectation de date** ;
- Option : table `production_slots (date, reserved_complex_count)` en complément de l'agrégat dérivé, pour verrouillage fin sans lire l'ensemble des commandes.

**Impact**
- Modules 4 (étape 4 + transition Confirmée), 6 (annulation), 10 (quota) ; § 5.3 (fiabilité).

## 6.C Fiches techniques & flexibilité des ingrédients — substitutions en fabrication

**Constat**
En pâtisserie, un ingrédient manquant est régulièrement remplacé au dernier moment (beurre marque A → marque B, chocolat 60 % → 70 %). La décrémentation automatique stricte (Module 7) ne couvre pas ce cas réel.

**Recommandation**
- Au passage en "En préparation", prévoir une option **"Ajustement d'ingrédient"** dans le flux de décrémentation :
  - Le Chef peut **remplacer une ligne** de la fiche technique (ingrédient + quantité) avant validation ;
  - Le remplacement est **journalisé** : mouvement de stock du produit d'origine (annulé/réintégré), mouvement du produit de substitution (`production_out`), motif libre "substitution" ;
  - Le **coût de la commande** est recalculé avec les quantités réellement consommées (impact CMV, Module 8) ;
- Règle : la substitution est autorisée **avant** validation de la production ; après validation, seule une perte/adjustement séparé est possible ;
- Affichage clair des substitutions sur la fiche de production et dans l'historique de la commande (traçabilité client fournisseur).

**Impact**
- Modules 7 (décrémentation, mouvements), 8 (CMV), 10 (fiche atelier) ; type de mouvement `production_out` déjà prévu au schéma.

## 6.D Disponibilité réseau en livraison — mode dégradé pour le livreur

**Constat**
Les coursiers traversent des zones à faible couverture (intérieur, zones périphériques). La dépendance au réseau pour confirmer un encaissement peut bloquer la livraison et fausser la trésorerie.

**Recommandation**
- **Vue mobile livreur "PWA minimaliste"** : Service Worker + cache des fiches de livraison du jour assignées (préchargées au départ de la tournée) ;
- **Mode hors-ligne** :
  - L'encaissement du solde (cash saisi, ou lien Wave/QR généré en amont) et le passage "Livrée" sont **enregistrés localement** (IndexedDB / LocalStorage) ;
  - **Synchronisation automatique** dès le retour de la connexion (file de transactions offline, réconciliation avec l'état serveur, horodatage réel de l'action) ;
  - Détection de conflit (commande modifiée entre-temps) : file d'attente de résolution manuelle par le Gérant ;
- L'idempotence des `payments` (clé `provider_ref`, § 4.5) garantit qu'un encaissement offline ne sera **jamais dupliqué** à la synchro ;
- Rappel visuel d'état de synchronisation (pendant/synchronisé/en attente) sur la fiche de livraison.

**Impact**
- Module 11 (fiche mobile, encaissement solde), Module 9 (lien Wave/QR), § 5.3 (asynchrone) ; statut `offline_pending` ajouté au schéma sur `deliveries`/`payments`.

## 6.E Hors périmètre v1 — backlog

- **Facturation SaaS** : la **mécanique complète est livrée en v1** (plans freemium + add-ons, essai 14 jours, invoices, encaissement Mobile Money via agrégateur plateforme, rappels J-7/J-3/J-1, période de grâce, suspension/archivage, enforcement des limites — cf. Module 12) ; restent en v2 : **SSO/SAML & SCIM** (plan Business), paiement par carte bancaire, automatisation complète des proratas multi-cycles ;
- **API publique** : ouverture d'une API produit pour les intégrateurs (ERP, grossistes) — le modèle de données y est déjà préparé (webhooks, clés API par tenant) ;
- **Multi-devises / multi-pays** : XOF fixe en v1 (la structure `tenant_settings` permet d'étendre) ;
- **Marketplace de recettes** entre pâtissiers ; module fidélité (points, parrainage) ; analytics avancés (prédiction de demande).

---

# 7. PLAN DE DÉVELOPPEMENT & SPRINTS

> Cadence : **sprints de 2 semaines** (10 sprints + Go-live), ordonnancés par **dépendances techniques** : les fondations multi-tenant précèdent le parcours commercial, qui précède la production, qui précède les paiements et la logistique.
>
> Convention : chaque sprint livre des **fonctionnalités testables** (Pest) et respecte la règle d'architecture MFC Livewire (§ 2.4), les axes § 6.A→6.D étant intégrés **dès la conception** du sprint concerné.

## Jalons globaux

| Jalon | Contenu | Fin prévue |
|---|---|---|
| **M0 — Fondations** | Sprints 0-1 : tenancy, RBAC, paramétrage, CRM | S+4 |
| **M1 — Parcours commercial** | Sprints 2-3 : commandes/devis, stocks & recettes | S+8 |
| **M2 — Caisse & annulations** | Sprint 4 : accessoires, politiques d'annulation | S+10 |
| **M3 — Paiements & planning** | Sprints 5-6 : Mobile Money, quotas d'atelier | S+14 |
| **M4 — Logistique & communication** | Sprint 7 : livraison, WhatsApp, PWA livreur | S+16 |
| **M5 — Finances & Go-live** | Sprints 8-9 : analytics, durcissement, recette | S+20 |

## Sprint 0 — Fondations multi-tenant & Qualité

| Élément | Détail |
|---|---|
| **Objectif** | Squelette déployable, tenancy opérationnel, RBAC en place |
| **Modules** | Module 1 (partiel : tenancy + onboarding) |
| **Livrables** | Bootstrap Laravel 13 + Flux UI + Tailwind 4 + Vite ; stancl/tenancy `PostgresSchemaManager` (schéma central `public` + `tenant_{uuid}`, résolution par sous-domaine, migrations tenant-scoped, `TenantAwareJob`) ; Fortify + passkeys ; spatie/laravel-permission (permissions centralisées en enum + 4 rôles racines seedés) ; **RBAC plateforme** : tables Spatie en `public`, permissions enum `admin.*`, rôles racines `Super-Admin`/`Support`/`Billing` seedés ; **Pest** (le starter kit fournit PHPUnit : ajout du package pestphp/pest + migration des tests) ; onboarding tenant (inscription → création schéma → 1ᵉʳ Gérant) ; **console plateforme minimale (Module 12)** : auth `admin_users`, **gestion des utilisateurs plateforme (invitation, activation/désactivation, attribution de rôle)**, liste des tenants, création manuelle d'un tenant, activation/suspension, journal `admin_audit_logs` ; **landing statique (Module 13)** : présentation + CTA inscription + capture de leads (`landing_leads`) ; CI/CD (Pest, Pint, PHPStan sur chaque merge) |
| **Points §6** | Base des tests d'étanchéité inter-tenant (squelette obligatoire dès ici) |
| **Tests critiques** | Création de tenant crée bien le schéma ; accès croisé impossible ; roles seedés par tenant ; suspension d'un tenant → accès web bloqué immédiatement |
| **DoD** | Un tenant peut s'inscrire, se connecter, et son Gérant accède à une page vide sécurisée ; le super-admin voit le tenant dans la console et peut le suspendre/activer |

## Sprint 1 — Paramétrage Gérant & CRM Clientèle

| Élément | Détail |
|---|---|
| **Objectif** | Le gérant configure sa boutique ; les vendeurs gèrent leurs clients |
| **Modules** | Module 2 (complet), Module 3 (complet) |
| **Livrables** | `tenant_settings` (colonnes typées + JSONB) avec UI onglets ; zones de livraison ; politiques d'annulation ; quotas ; grille de prix (`price_lists`/`price_list_rules`) ; pages Clients + recherche WhatsApp ; modale création rapide client ; drawer fiche client (consultation : historique, avoirs) ; dédoublonnage numéro ; recalcul planifié des agrégats client |
| **Surfaces UI** | Page `Settings > Boutique` (onglets) ; Page `Clients` ; Modales (création rapide, zones, modèles) ; Drawer (fiche client, consultation) |
| **Tests critiques** | Validation stricte settings ; dédoublonnage WhatsApp ; archivage client avec historique |
| **DoD** | Le Gérant configure toute la boutique sans code ; un vendeur crée/recherche un client en < 10 s |

## Sprint 2 — Devis & Commandes Personnalisées (cœur du produit)

| Élément | Détail |
|---|---|
| **Objectif** | Le parcours de vente principal : du brouillon au devis envoyé |
| **Modules** | Module 4 (devis, wizard, tunnel d'états — hors transition Confirmée) |
| **Livrables** | Wizard MFC `/orders/create` 5 étapes (Client/Gâteau/Accessoires/Programme/Paiement) ; brouillon auto-sauvegardé ; validation parts/saveurs/allergènes ; grille de prix ; devis PDF + vue web ; lien public de devis (statut Vu/Confirmé) ; partage WhatsApp (provider mocké) ; transition Brouillon → Devis envoyé |
| **Points §6** | **6.A** : compression côté client (Canvas/WebWorker, < 300 Ko) + uploads différés non bloquants |
| **Surfaces UI** | Page dédiée `/orders/create` ; Page `Commandes` (liste filtrable) ; Drawer détail |
| **Tests critiques** | Wizard complet sans perte de données (retour arrière) ; brouillon restauré après coupure ; devis généré conforme (montants FCFA entiers) |
| **DoD** | Un vendeur crée une commande complète et envoie le devis WhatsApp en < 3 min sur mobile 3G |

## Sprint 3 — Stocks & Fiches Techniques

| Élément | Détail |
|---|---|
| **Objectif** | Le Chef pâtissier gère stocks et recettes ; la production consomme automatiquement |
| **Modules** | Module 7 (complet) |
| **Livrables** | Produits (ingrédients/emballages/accessoires, unités g/kg/L/pcs, `reserved_qty`) ; recettes + coût théorique ; mouvements immuables (types complets + `substitution`, chaînage `reversed_movement_id`) ; `order_ingredient_consumptions` (consommation ligne à ligne) ; modales opérations (appro, ajustement, pertes) ; décrémentation transactionnelle au passage "En préparation" ; seuils + alertes WhatsApp |
| **Points §6** | **6.C** : substitution d'ingrédient avant validation (journalisée, recalcul coût/CMV) |
| **Surfaces UI** | Page `Stocks` ; Page `Fiches techniques` ; Modale ajout/édition produit ; Drawer historique mouvements (consultation) ; Modales opérations |
| **Tests critiques** | Immutabilité des mouvements ; décrémentation atomique ; substitution recalculée ; pas de stock négatif sans alerte |
| **DoD** | Une recette appliquée à une commande consomme les bons ingrédients et met à jour le coût |

## Sprint 4 — Caisse rapide & Annulations (Avoirs, Politiques)

| Élément | Détail |
|---|---|
| **Objectif** | Vendre au comptoir et clôturer les annulations conformément aux politiques |
| **Modules** | Module 5 (complet), Module 6 (complet) |
| **Livrables** | Catalogue accessoires ; client générique "Comptoir" (seed) + `reserved_qty` (réservation des ingrédients) ; modale "Caisse rapide" (déstockage + encaissement + reçu WhatsApp) ; commandes express `accessory_sale` ; modale d'annulation (politique + motif obligatoire) ; traitement auto selon politique (remboursement / code avoir / pénalité) ; réintégration ou pertes de stock selon l'avancement ; libération du créneau verrouillée (préparation § 6.B) |
| **Points §6** | Préparation de **6.B** (verrouillage annulation) avant le quota du Sprint 6 |
| **Surfaces UI** | Page `Accessoires` ; Modale Caisse rapide ; Modale Annulation ; Drawer détail annulation |
| **Tests critiques** | Politique stricte → pénalité reclassée ; politique intermédiaire → avoir crédité & utilisable ; stock réintégré si pré-production |
| **DoD** | Une annulation applique la politique du gérant sans intervention manuelle |

## Sprint 5 — Paiements Mobile Money (Webhooks)

| Élément | Détail |
|---|---|
| **Objectif** | L'acompte payé confirme la commande automatiquement |
| **Modules** | Module 9 (complet), Module 8 (partiel : payments, journal de trésorerie) |
| **Livrables** | Fournisseurs Wave/CinetPay/Bizao/Paystack (clés chiffrées, sandbox/prod) ; `payment_links` (liens de paiement acompte + lien Wave/QR de solde pré-généré, montant attendu, statut, expiration) ; webhooks idempotents (signature HMAC, `provider_ref` unique, logs bruts, rejeu) ; remboursements API ; transition Devis envoyé → Confirmée automatique ; modale d'encaissement manuel ; journal des paiements |
| **Points §6** | **6.B** amorcé : transition Confirmée dans transaction verrouillée ; dégradation élégante si appel sortant échoue |
| **Surfaces UI** | Page `Paiements` (filtres fournisseur/méthode) ; Modale encaissement ; Modale mismatch |
| **Tests critiques** | Idempotence (webhook rejoué = 1 seul payment) ; signature invalide rejetée ; acompte reçu → statut Confirmée + réservation créneau |
| **DoD** | Un acompte Wave simulé confirme la commande et notifie le client |

## Sprint 6 — Planning d'Atelier & Quotas de Production

| Élément | Détail |
|---|---|
| **Objectif** | Garantir la faisabilité : calendrier, quota, préparation J-2/J-1/J-0 |
| **Modules** | Module 10 (complet) |
| **Livrables** | Vue calendrier mois/semaine ; vue "Atelier du jour" Chef ; blocage automatique des dates saturées ; `production_slots` (verrouillage `SELECT ... FOR UPDATE`, `reserved_complex_count`) ; qualification `is_complex` à la confirmation ; fermetures/congés ; réaffectation de date (Gérant, avec notification) ; planning J-2/J-1/J-0 déduit |
| **Points §6** | **6.B complet** : réservation atomique, conflit géré (dates alternatives), verrous annulation/réaffectation |
| **Surfaces UI** | Page dédiée `Planning` (calendrier) ; Drawer jour/créneau ; Modale fermeture |
| **Tests critiques** | Concurrence : 2 confirmations simultanées → quota jamais dépassé ; annulation libère la capacité |
| **DoD** | Le quota est strictement respecté sous charge concurrente (test de race) |

## Sprint 7 — Livraison & Communication WhatsApp

| Élément | Détail |
|---|---|
| **Objectif** | Le livreur encaisse le solde et livre ; la communication est automatisée |
| **Modules** | Module 11 (complet) |
| **Livrables** | Fiches de livraison mobiles (repères locaux, solde FCFA, photo preuve) ; affectation livreur ; transitions Assignée → En route → Livrée ; encaissement solde (cash ou lien Wave) ; templates WhatsApp (8 déclencheurs) + journal `whatsapp_messages` ; relances programmées (scheduler) |
| **Points §6** | **6.D** : PWA minimaliste (Service Worker, IndexedDB, synchro auto, statut `offline_pending`, résolution de conflits par le Gérant) |
| **Surfaces UI** | Page `Livraisons du jour` (mobile-first) ; Fiche plein écran ; Modale affectation ; Page `WhatsApp` (journal) |
| **Tests critiques** | Encaissement offline synchronisé sans doublon ; livreur isolé sur ses livraisons ; relance J-1 envoyée au bon moment |
| **DoD** | Un livreur livre hors-ligne, le solde est encaissé et la trésorerie à jour au retour du réseau |

## Sprint 8 — Finances & Analytics (Tableau de Bord)

| Élément | Détail |
|---|---|
| **Objectif** | La vision comptable du Gérant : CA réel, trésorerie, encours, marges |
| **Modules** | Module 8 (complet) |
| **Livrables** | Dashboard financier (CA livré/retiré, trésorerie par moyen, encours, pénalités, avoirs, CMV, marge) ; clôture de caisse quotidienne (`cash_closures` : théorique vs comptée, écart) ; reclassement des pénalités ; exports CSV/PDF ; indicateur de créance `sold_owed` (dérivé de `balance_due > 0`) + relance configurable |
| **Surfaces UI** | Page dédiée `Finances` (graphiques, agrégats, filtres période) |
| **Tests critiques** | CA ≠ encaissements (règle comptable stricte) ; clôture de caisse détecte les écarts ; pénalités isolées |
| **DoD** | Les chiffres du dashboard recoupent le journal de trésorerie ligne à ligne |

## Sprint 9 — Durcissement, Performance Mobile & Go-live

| Élément | Détail |
|---|---|
| **Objectif** | Préparer la production : qualité, perf 3G/4G, supervision |
| **Livrables** | Audit perf mobile (poids < 150 Ko, lazy loading, skeletons) ; passe de tests d'étanchéité multi-tenant exhaustive ; monitoring queues + alertes webhook > 15 min ; restauration par schéma testée ; documentation ops ; recette fonctionnelle complète (parcours de bout en bout) ; runbook support ; **console plateforme complète (Module 12)** : dashboard MRR/usage, **billing complet (invoices + liens de paiement Mobile Money + webhooks, rappels J-7/J-3/J-1, période de grâce lecture seule, suspension/archivage, enforcement des limites, add-ons, prorata)**, sessions support en lecture seule (motif obligatoire, expiration auto), monitoring cross-tenant, export/effacement RGPD d'un tenant, **UI `Plateforme > Équipe` complète (création de rôles personnalisés, gestion des permissions)** ; **landing complète (Module 13)** : tarifs dynamiques (depuis `plans`/`plan_addons`), FAQ, mentions, SEO/Open Graph, rapport d'acquisition, page `Plateforme > Leads` |
| **Points §6** | Validation finale de 6.A→6.D en conditions réelles (réseau dégradé simulé) |
| **Tests critiques** | Smoke test complet des 13 modules ; test de charge quota ; restauration schéma tenant ; session support en lecture seule (tentative d'écriture rejetée, audit complet) |
| **DoD** | Go-live validé : parcours réel simulé de bout en bout sans intervention manuelle |

## Ordre des dépendances (schéma critique)

```
Sprint 0 (tenancy/RBAC)
   ├─ Sprint 1 (settings + CRM)
   │     └─ Sprint 2 (devis/commandes)
   │           ├─ Sprint 3 (stocks/recettes)
   │           │     └─ Sprint 4 (caisse + annulations)
   │           │           └─ Sprint 5 (paiements MM)
   │           │                 └─ Sprint 6 (planning/quotas)
   │           │                       └─ Sprint 7 (livraison/WhatsApp)
   │           └─ Sprint 8 (finances) ← parallèle possible dès Sprint 5
   └─ Sprint 9 (durcissement & go-live)
```

---

*Fin du Cahier des Charges — Cakeman v1.9.*
