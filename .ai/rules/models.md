---
paths:
  - app/Models/TenantUserInvitation.php
  - app/Models/Setting.php
  - app/Models/AdminUser.php
---

# Models

## Modèles tenant : mapper en données scalaires dans run()
Ne JAMAIS faire transiter des modèles Eloquent du schéma tenant (ex. TenantUserInvitation) vers le contexte central : dès que la tenancy se termine ($tenant->run()), tout accès à un attribut casté datetime (created_at, accepted_at...) déclenche Model::getConnection() → « Database connection [tenant] not configured » (la connexion tenant n'existe que pendant l'initialisation). Pour afficher des données tenant depuis la console centrale (admin.tenants.show), mapper les valeurs formatées (chaînes) DANS le closure run() — jamais des modèles.

## Contourner les tags du cache tenancy (Cache::store())
En contexte tenant (sous-domaine, `Tenant->run()`), le bootstrapper de stancl remplace le binding `cache` par un manager qui tagge TOUS les appels (`->tags([tenant])`) — le store `database` refuse (« This cache store does not support tagging », invisible en test car phpunit force `CACHE_STORE=array` qui supporte les tags). Tout cache central (réglages, etc.) doit donc passer par `Cache::store()` (vraie méthode du manager parent, non interceptée par `__call`) pour contourner les tags tenancy. Ne jamais utiliser `Cache::remember...`/`Cache::forget` directement dans du code exécutable sous tenancy.

## Passkeys table FK is admin_user_id in central schema
The central `passkeys` table FK is `admin_user_id` (renamed from `user_id` via migration) so the PasskeyAuthenticatable trait guess matches. Tenant passkeys table keeps `user_id`. Do not override the trait's passkeys() relation — the phpstan contract (HasMany<Passkey, Model>) is invariant and an override can never satisfy it.
