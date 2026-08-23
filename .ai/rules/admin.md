---
paths:
  - 'resources/views/components/admin/**'
---

# Admin

## Recherche ILIKE sur colonnes jsonb
Pour rechercher dans une colonne jsonb (ex. properties d'admin_audit_logs), `where('col', 'ilike', ...)` échoue (jsonb vs text) — utiliser `whereRaw('col::text ilike ?', ['%term%'])`.

## AdminAuditLog : admin_user_id = acteur, cible dans properties
AdminAuditLog::record() : admin_user_id doit toujours être l'ACTEUR (défaut auth('admin')->id()) — ne jamais passer l'ID de la cible. Les infos de cible (e-mail, rôle...) vont dans $properties (le Détails de la page Audit les affiche).

## RateLimiter::for() renvoie $this, pas la clé
Sur la version installée de Laravel, `RateLimiter::for()` retourne l'instance RateLimiter (plus une clé string). Pour un verrouillage ad hoc (login console), construire la clé à la main : `$key = 'admin-login|'.Str::lower($email).'|'.request()->ip();` puis `RateLimiter::attempt($key, ...)` et `RateLimiter::availableIn($key)`. `RateLimiter::for()` ne sert qu'à enregistrer des limiteurs nommés (déjà faits dans FortifyServiceProvider).
