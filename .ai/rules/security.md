---
paths:
  - 'resources/views/components/admin/security/**'
---

# Security

## Security page: 2FA + personal password change
La page admin.security.index (accessible via sidebar Paramètres → Sécurité et dropdown profil) couvre 2FA TOTP + changement de mot de passe personnel. changePassword() exige l'ancien mot de passe, révoque les autres sessions (id != session()->getId()), consigne admin_user.password_changed. Audit : ajouter le libellé des nouvelles actions dans les deux actionLabel (audit index + user show).
