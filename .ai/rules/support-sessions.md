---
paths:
  - 'resources/views/components/admin/support-sessions/**'
---

# Support Sessions

## Sessions support : motif, durée limitée, lecture seule SQL
Sessions support (CdC § 12) : table admin_support_sessions — motif >= 10 caractères, durée 15/30/60/120 min, expiration automatique (statut calculé en direct : ended_at null + expires_at futur). La vue lecture seule tourne dans `$tenant->run()` + DB::transaction avec `SET TRANSACTION READ ONLY` (toute écriture échoue au niveau SQL).
