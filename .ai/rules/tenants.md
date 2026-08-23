---
paths:
  - 'resources/views/components/admin/tenants/**'
---

# Tenants

## Cycle de vie tenant : archivage avant suppression
Cycle de vie tenant (CdC § 12) : suppression jamais directe — d'abord cancelTenant() (statut cancelled + abonnement annulé), puis deleteTenant() possible (abort 404 sinon). Prolongation d'essai = +14 jours (TRIAL_EXTENSION_DAYS) réservée aux abonnements Trialing. Icônes disponibles : calendar-days, archive-box (pas de calendar-plus dans Flux).

## Activation d'une boutique conditionnée à un gérant valide
Règle métier : une boutique ne peut être activée qu'avec un gérant actif (utilisateur rôle « Gérant », is_active=true — `Tenant::hasValidManager()`). Le toggle d'activation vérifie cette condition (422 sinon) ; `inviteManager`/`resendInvitation` sont réservés aux boutiques `Pending` (pas de nouveau gérant une fois active). L'accept flow reste le seul chemin qui crée le gérant et active la boutique.
