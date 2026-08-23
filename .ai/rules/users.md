---
paths:
  - 'resources/views/components/admin/users/**'
---

# Users

## Deletion restricted to deactivated accounts
Suppression d'un utilisateur console : réservée aux comptes désactivés (is_active=false) — un compte actif doit d'abord être désactivé. Le bouton Supprimer n'est affiché que pour les comptes inactifs (index + fiche), et deleteUser() rejette les actifs. La suppression purge les sessions et les invitations en attente, conserve l'audit.
