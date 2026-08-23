# Plan d'implémentation — Sprint 0 (fondations)

**Source :** `cakeman.md` v1.9 — Sprint 0 : tenancy + RBAC + onboarding + console plateforme minimale.
**Périmètre retenu :** Tenancy (stancl/tenancy, schémas PostgreSQL) · RBAC tenant + plateforme (spatie/laravel-permission) · Onboarding tenant · Console super-admin minimale (Module 12, partie socle).
**Hors périmètre ici :** landing (M13), billing (M12), CI/CD — traités ensuite.

---

## 1. État des lieux du dépôt (vérifié)

| Élément | État |
|---|---|
| Laravel 13.25 / PHP 8.4 | ✅ (composer.lock) |
| Livewire 4.4 + Flux 2.16 + Fortify 1.38 | ✅ installés |
| Pest 5.1 (tests déjà en Pest : `tests/Feature/{Auth,Settings,DashboardTest,ExampleTest}`) | ✅ |
| stancl/tenancy, spatie/laravel-permission | ❌ à installer |
| Base locale : PostgreSQL 18.4 démarré (`pg_isready` OK) | ✅ mais `.env` est sur `sqlite` |
| `phpunit.xml` : `DB_CONNECTION=sqlite :memory:` | ⚠️ incompatible tenancy (schémas) → pgsql obligatoire |
| Migrations existantes : `users`, `cache`, `jobs`, `passkeys`, +2FA Fortify (schéma de départ unique, pas encore central/tenant) | à réorganiser |

## 2. Prérequis & décisions structurantes

1. **Passage à PostgreSQL** : `.env` → `DB_CONNECTION=pgsql` (base `cakeman`) ; `phpunit.xml` → pgsql (base dédiée `cakeman_test`, créée via `createdb`). SQLite est **impossible** avec `PostgresSchemaManager` (pas de schémas).
2. **Domaine central** : `cakeman.test` (dev) — console plateforme + landing + webhooks. **Domaines tenants** : `{sous-domaine}.cakeman.test`. En dev, configurer `/etc/hosts` ou utiliser `tenancy()` en tests ; stancl résout par sous-domaine (`InitializeTenancyBySubdomain`).
3. **Organisation des migrations** (convention stancl) :
   - `database/migrations/central/` → schéma `public` (exécutées une fois) ;
   - `database/migrations/tenant/` → chaque schéma `tenant_{uuid}` (exécutées à la création du tenant).
   Les migrations du starter kit (`users`, `passkeys`, 2FA, `sessions`, `password_reset_tokens`, `cache`, `jobs`) sont **réutilisées dans les deux scopes avec nom de table paramétrable** (cf. § 5.1) — exactement la note du CdC v1.3.
4. **Deux guards** : `web` (tenants) et `admin` (console centrale, `admin_users`).
5. **RBAC spatie deux fois** : tables dans chaque schéma tenant (rôles métier) + dans `public` (rôles plateforme). Cache du `PermissionRegistrar` **purgé par scope** à chaque bascule tenancy (évite la fuite inter-tenant, § 2.3 du CdC).
6. **Permissions centralisées en enums** (identifiants stables, jamais de strings en dur) :
   - `TenantPermission` : `order.create`, `order.cancel`, `payment.record`, `inventory.manage`, `dashboard.view`, etc. (liste exhaustive = base de M1→M11, seules quelques-unes sont utilisées au S0) ;
   - `AdminPermission` : `admin.tenant.view`, `admin.tenant.manage`, `admin.user.manage`, `admin.support.session`, `admin.billing.manage`, `admin.monitoring.view`, `admin.audit.view`, `admin.webhook.replay`, `admin.maintenance.toggle`, `admin.lead.view`.
7. **Enforcement de la suspension** : middleware tenancy + contrôle `Tenant::status === active` au niveau `EnsureValidTenant` (base du futur § grâce/past_due).

## 3. Packages

```bash
composer require stancl/tenancy:^3 spatie/laravel-permission:^6
```

- `stancl/tenancy` v3 : `PostgresSchemaManager`, identification par sous-domaine, jobs tenant-aware.
- `spatie/laravel-permission` v6 : compatible Laravel 13 (vérifier à l'installation).

> Règle projet : pas de changement de dépendances sans accord — c'est le périmètre validé du S0.

## 4. Fichiers créés/modifiés (vue d'ensemble)

```
config/tenancy.php                    [publié par vendor:publish]
config/permission.php                 [publié par vendor:publish, table_names identiques]
app/Enums/TenantStatus.php            [new]
app/Enums/TenantPermission.php        [new]
app/Enums/AdminPermission.php      [new]
app/Models/Tenant.php                 [new — extends stancl\Tenancy\Tenant, + status, événements]
app/Models/AdminUser.php           [new — central, Fortify, Notifiable, HasRoles]
app/Models/User.php                   [modifié — modèle tenant, HasRoles, phone/is_active]
app/Providers/TenancyServiceProvider.php [new — chemins migrations, jobs tenant-aware]
app/Providers/AdminFortifyServiceProvider.php [new — Fortify pour guard admin]
app/Actions/Fortify/CreateNewUser.php [existe — devient tenant-scoped]
app/Actions/Admin/CreateAdminUser.php [new]
app/Http/Middleware/EnsureTenantActive.php [new — suspension]
resources/views/components/admin/            [console — MFC v4, cf. cakeman.md § 2.4]
├── ⚡dashboard/               (admin.dashboard — nb tenants, statuts)
├── tenants/
│   ├── ⚡index/               (admin.tenants.index — liste paginée + recherche)
│   ├── ⚡create/              (admin.tenants.create — modale création manuelle)
│   └── ⚡show/                (admin.tenants.show — fiche + suspend/activate)
├── users/
│   └── ⚡index/               (admin.users.index — page Équipe : modales invitation/édition)
└── ⚡invitation-accept/       (admin.invitation-accept — pose du mot de passe, lien one-time)
resources/views/components/onboarding/
└── ⚡register-tenant/         (onboarding.register-tenant — page publique)
routes/web.php                  [central : /, /register, /admin/*]
routes/tenant.php               [new : /login, /dashboard — middleware tenancy]
routes/admin.php             [new : console]
database/migrations/central/    [tenants, domains (stancl), admin_users + annexes, admin_user_invitations, spatie]
database/migrations/tenant/     [users + annexes, spatie, tenant_settings]
database/seeders/AdminRolesSeeder.php       [Super-Admin/Support/Billing]
database/seeders/TenantRolesSeeder.php         [Gérant/Chef Pâtissier/Vendeur/Livreur — appelé à l'onboarding]
tests/Feature/Tenancy/TenantCreationTest.php
tests/Feature/Tenancy/TenantIsolationTest.php
tests/Feature/Tenancy/SuspensionTest.php
tests/Feature/Admin/AdminConsoleTest.php
tests/Feature/Admin/AdminUserTest.php
tests/Feature/Onboarding/RegisterTenantTest.php
tests/TestCase.php              [helper tenancy + purge cache spatie]
```

## 5. Détail par couche

### 5.1 Migrations — réutilisation paramétrable

Transformer les migrations du starter kit en **templates réutilisables** :

- `database/migrations/tenant/0001_..._create_users_table.php` → crée `users` (via `config('tenancy.user_table')` ou constante `'users'`) ;
- même fichier exécuté en scope central avec un nom de table différent (`admin_users`) → mécanisme : migration centrale qui appelle la même closure avec `$table = 'admin_users'`, OU migration unique lue par un runner maison (plus simple : **une classe `CreateUsersTable` réutilisée** par les deux migrations).

Colonnes (alignement CdC § 4.2/4.3) :

- **tenant `users`** : `id, name, email UNIQUE, email_verified_at, password, two_factor_* (Fortify), remember_token, phone, is_active, last_login_at, timestamps` ;
- **central `admin_users`** : identique + `is_active`, `last_login_at` (aucun `role_id` — Spatie fait foi, v1.1 du CdC) ;
- annexes dans les deux scopes : `password_reset_tokens`, `sessions`, `passkeys` (FK → table d'utilisateurs paramétrée), `cache`, `jobs` (tenant seulement).

### 5.2 Tenancy

- `php artisan tenancy:install` → tables `tenants`, `domains` en `public` + `config/tenancy.php`.
- `tenancy.php` :
  - `'database' => ['manager' => PostgresSchemaManager::class, 'template' => null, 'prefix' => 'tenant_', 'suffix' => '']` ;
  - `'identification' => ['driver' => RequestDataIdentificationDriver::class]` + middleware `InitializeTenancyBySubdomain` sur le groupe `tenancy` ;
  - `'central_domains' => ['cakeman.test', 'localhost', '127.0.0.1']` ;
  - chemins migrations : `central_migrations_path` = `database/migrations/central`, `tenant_migrations_path` = `database/migrations/tenant`.
- `TenancyServiceProvider::boot()` : `$this->migrations()` ; enregistrement des jobs tenant-aware (trait `TenantAware` ou `TenantAwareJob`).
- Modèle `Tenant` : `status` (enum `TenantStatus::Active/Suspended/Trialing/Cancelled`), mutateur de domaines, `booted()` → `created` : création du schéma automatique (stancl s'en charge via `TenantCreated` si `auto-create`/`auto-migrate` activés — **activer `'auto-create' => true, 'auto-migrate' => true`** pour l'onboarding).

### 5.3 RBAC

- Publier `config/permission.php` : `table_names` par défaut (mêmes noms dans les deux scopes, isolés par schéma).
- **Tenant** : seed `TenantRolesSeeder` (4 rôles racines + permissions de `TenantPermission`) exécuté à la création du schéma (hook `TenantCreated` / job `CreateTenantRoles` tenant-aware).
- **Plateforme** : seed `AdminRolesSeeder` dans `public` (3 rôles + permissions `admin.*`), exécuté via `db:seed --class=AdminRolesSeeder` au setup.
- **Cache** : dans `tests/TestCase` et dans le middleware tenancy → `app(PermissionRegistrar::class)->forgetCachedPermissions()` à chaque changement de scope (le cache stocké en `array` en test ; en prod, clé par tenant ou purge au switch — cf. CdC § 2.3).

### 5.4 Modèles & guards

- `config/auth.php` : guard `admin` → provider `admin_users` → `App\Models\AdminUser` ; guard `web` → `App\Models\User` (tenant).
- `AdminUser` : `Authenticatable`, `Notifiable`, `HasRoles` (spatie), mêmes traits Fortify 2FA que `User`.
- `User` (tenant) : + `HasRoles`, `phone`, `is_active`, `last_login_at`.
- **Règle CdC § 2.2.6** : aucun modèle métier hors contexte tenant — `AdminUser` et `Tenant` sont les seuls modèles centraux au S0.

### 5.5 Routes & Livewire

**Central** (`routes/web.php`, domaine `cakeman.test`) — composants MFC v4 natifs (`--mfc`, cf. cakeman.md § 2.4), routés via `Route::livewire()` :
- `GET /` → landing statique (placeholder S0) ;
- `GET/POST /register` → `Route::livewire('/register', 'onboarding.register-tenant')` (formulaire public : raison sociale, sous-domaine, nom, e-mail, mot de passe du Gérant) ;
- groupe `middleware: ['web','auth.admin','admin.permission:admin.tenant.view']` → `routes/admin.php` :
  - `/admin` → `admin.dashboard` (Dashboard minimal : nb tenants, statuts) ;
  - `/admin/tenants` → `admin.tenants.index` (liste paginée + recherche) ;
  - modale `admin.tenants.create` (création manuelle : nom, sous-domaine, plan=free, statut=active) ;
  - `/admin/tenants/{tenant}` → `admin.tenants.show` (fiche : domaine, statut, métriques minimales) + actions Suspendre/Activer (permission `admin.tenant.manage`, journal `admin_audit_logs`) ;
  - `/admin/users` → `admin.users.index` (page `Plateforme > Équipe`, permission `admin.user.manage`) : liste paginée + recherche, modale Invitation, modale Édition (rôle, activation/désactivation) — cf. § 5.8 ;
  - `/admin/invitations/{token}` → `admin.invitation-accept` (pose du mot de passe, lien one-time, page publique non authentifiée).

> **Règle UX impérative (CdC § 2.5)** : **tous** les formulaires de création/édition passent par des **modales Flux** (`<flux:modal>`, largeur `md:w-*`), jamais par une page dédiée — hors Wizard multi-étapes (`/register` onboarding) et pages one-time non authentifiées (`invitation-accept`). Au S0 : `admin.tenants.create` est une modale rendue depuis `admin.tenants.index` ; Invitation/Édition utilisateur sont des modales rendues depuis `admin.users.index`. Les flyouts/drawers restent réservés à la consultation.

**Tenant** (`routes/tenant.php`, middleware groupe `tenancy`) :
- `/login`, `/register` (tenant ? non — inscription tenant = onboarding central), `/dashboard` (page placeholder sécurisée, DoD du S0) ;
- l'existant `routes/settings.php` + `dashboard` sont déplacés sous ce groupe.

### 5.6 Onboarding (flux complet)

1. `POST /register` (central) → validation (sous-domaine libre, email unique) ;
2. Transaction : `Tenant::create(['name', 'status' => active])` → stancl crée le schéma `tenant_{uuid}` + exécute les migrations tenant + `TenantCreated` lance le job `CreateTenantRoles` (seed rôles/permissions) + seed `tenant_settings` par défaut (acompte 50 %, quota 5, politique intermédiaire…) ;
3. Création du domaine `{sous-domaine}.cakeman.test` (stancl `Domain`) ;
4. Création du 1ᵉʳ utilisateur `Gérant` (role `Gérant`) ;
5. Client générique **"Comptoir"** (client non nécessaire au S0 — à S4) ;
6. Redirection vers `https://{sous-domaine}.cakeman.test/login` (session du tenant, pas de SSO automatique au S0 — note : restreindre l'inscription publique = permission/setting pour la v1.9).

### 5.7 Suspension (base M12)

- `EnsureTenantActive` : après `EnsureTenancyInitialized`, si `tenant.status !== active` → 403 avec page dédiée (message "Boutique suspendue, contactez le support").
- Console : action suspend/activate → `Tenant::update(status)` + entrée `admin_audit_logs` (table minimale au S0 : `admin_user_id, tenant_id, action, properties jsonb, ip, created_at` — cf. § 4.2 du CdC).

### 5.8 Gestion des utilisateurs plateforme (page Équipe — périmètre Sprint 0 du CdC)

Aligné sur le Sprint 0 du CdC : invitation e-mail, activation/désactivation immédiate (sessions révoquées), attribution de rôle. La **création de rôles personnalisés** reste au Sprint 9 (S0 = les 3 rôles racines uniquement).

**Flux d'invitation :**
1. Modale `Invitation` (composant `admin.users.index`) : e-mail + rôle (`Super-Admin`/`Support`/`Billing`) → action `CreateAdminUser` crée `admin_users` (mot de passe aléatoire temporaire, `is_active = true` par défaut à l'acceptation) + envoie un e-mail avec lien de premier accès (token one-time, expiration 48 h — table `admin_user_invitations` ou colonne sur `admin_users`) ;
2. Le destinataire suit le lien → pose son mot de passe (page publique `/admin/invitations/{token}`) → activé ;
3. **Désactivation** : modale de confirmation → `is_active = false` → révocation immédiate des sessions actives (`sessions` du guard `admin` supprimées) — Cf. § 12 "sessions révoquées" ;
4. **Réattribution de rôle** : modale Édition (rôles Spatie) ;
5. Chaque mutation (création, rôle, désactivation) journalisée dans `admin_audit_logs` ;
6. **Suppression interdite** si des `admin_audit_logs` sont rattachés → archivage `is_active = false` (règle CdC § 12).

## 6. Tests (Pest — chaque livrable du S0 est testé)

| Test | Couvre |
|---|---|
| `TenantCreationTest` | création tenant → schéma créé (`information_schema.schemata`), migrations exécutées, rôles 4 racines seedés, domaine créé, settings par défaut |
| `TenantIsolationTest` | user tenant A invisible depuis tenant B ; `tenancy()->end()` → aucun accès ; vérification `search_path` |
| `SuspensionTest` | suspendre → `GET /dashboard` 403 ; réactiver → accès restauré ; entrée audit créée |
| `AdminConsoleTest` | auth `admin` requise ; rôle Super-Admin accède à /admin/tenants ; Support ne peut pas suspendre (permission refusée) ; création manuelle tenant OK |
| `AdminUserTest` | invitation → e-mail envoyé + lien valide ; pose de mot de passe → activé ; désactivation → sessions révoquées + accès refusé ; changement de rôle journalisé ; suppression archivée si audit rattaché |
| `RegisterTenantTest` | onboarding complet bout en bout ; sous-domaine dupliqué rejeté ; email dupliqué rejeté |
| `Middleware/GuardTest` | guard `admin` ≠ `web` ; pas de fuite de session entre console et tenants |

Infrastructure : `tests/TestCase.php` → helper `createTenant(): Tenant` (+ `tenancy()->initialize($tenant)`), purge cache Spatie avant/après chaque test ; `RefreshDatabase` remplacé par une routine **central-only** (migrations centrales + création/suppression de schémas par test — jamais de `RefreshDatabase` global sur les schémas tenants).

## 7. Ordre de travail (checklist)

1. `.env` + `phpunit.xml` → pgsql ; `createdb cakeman cakeman_test` ; vérifier `php artisan migrate` sur central.
2. `composer require stancl/tenancy spatie/laravel-permission` ; `php artisan tenancy:install`.
3. Réorganiser les migrations (central/tenant, table paramétrable) — **avant tout** pour ne pas créer de schéma pollué.
4. Enums + modèles (`Tenant`, `AdminUser`, `User` mis à jour) + guards + `EnsureTenantActive`.
5. Seeds RBAC (tenant + plateforme) + hook `TenantCreated`.
6. Routes centrales + console Livewire (Tenants/Index, Create, Show, Users/Index + invitation) + audit minimal.
7. Onboarding public (`RegisterTenant`) + routes tenant (`/login`, `/dashboard`).
8. Tests (liste § 6) + passage de la suite existante (Auth/Settings) sous le contexte tenant.
9. `composer ci:check` (Pint + PHPStan + tests) au vert ; `npm run build` si Vite manifest requis.

## 8. Points d'attention / risques

- **`phpunit.xml` en sqlite** : le premier changement à faire, sinon aucun test tenancy ne peut tourner.
- **Migrations du starter kit déjà exécutées en sqlite** : bascule propre pgsql → rejouer toutes les migrations sur la nouvelle base.
- **Spatie cache** : fuite inter-tenant si le cache permissions n'est pas purgé par scope (test d'étanchéité dédié).
- **`auto-migrate`** : désactivable si la migration à chaud ralentit l'inscription → job dédié en file (à trancher lors de l'implémentation).
- **`search_path` inclut `public`** (CdC § 2.2) : les requêtes tenant résolvent les tables centrales — vérifier en test que `AdminUser`/`tenants` ne sont jamais accessibles depuis un modèle tenant (interdiction des modèles partagés).
- **Dev local** : sous-domaines → `/etc/hosts` (`boutique1.cakeman.test`) ou domaine wildcard ; sinon `tenancy()->initialize()` dans les tests suffit.
- **Fortify** : le formulaire d'inscription natif (route `/register` tenant) doit être **désactivé** (onboarding central uniquement) ; les vues Fortify du tenant et de la plateforme sont séparées.