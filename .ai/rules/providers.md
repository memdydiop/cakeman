---
paths:
  - app/Providers/FortifyServiceProvider.php
---

# Providers

## is_active est vérifié au login tenant (Fortify + passkeys)
Le login tenant (guard web) passe par Fortify, qui n'utilise pas is_active par défaut. Tout compte désactivé (ex. gérant remplacé) doit être rejeté : Fortify::authenticateUsing() vérifie is_active + Hash::check, et Passkeys::authorizeLoginUsing() applique la même garde aux passkeys (match User/AdminUser). Ne pas retirer ces callbacks sans rétablir la vérification ailleurs.
