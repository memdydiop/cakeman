---
paths:
  - 'app/**'
---

# App

## Jobs : timestamps entiers ; two_factor_secret en text
Tables `jobs` (Laravel) : `available_at` et `created_at` sont des entiers UNIX (timestamps), pas des datetime — insérer `now()->timestamp`. Colonne `two_factor_secret` (Fortify) : le payload chiffré dépasse varchar(255), la garder en `text`.

## Convention des clés de Configuration
Clés de réglages (table `settings`, modèle `Setting` central, cache `settings.all` invalidé à chaque écriture) : préfixes par domaine — `platform.*` (name, support_email, maintenance_enabled/message), `tenant.*` (trial_days, archive_days, maintenance_hours), `billing.*` (grace_days, sandbox_mode, webhook_secret), `acquisition.*` (lead_notification_email), `security.*` (invitation_expires_hours, two_factor_required). Les valeurs sont stockées en string ('0'/'1' pour les booléens) ; toujours passer un défaut : `Setting::get('billing.grace_days', 7)`. Les onglets de la page Configuration sauvegardent via des clés Livewire sans points (ex. `general.trialDays`) mappées vers ces clés pointées.
