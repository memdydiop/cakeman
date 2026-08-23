---
paths:
  - 'resources/views/components/**/⚡invitation-accept/**'
---

# Invitation Accept

## Jamais d'erreur de validation invisible sur les champs non rendus
Dans les flux d'acceptation (gérant + admin), `accept()` rejette via `ValidationException::withMessages(['token' => ...])` mais le champ `token` n'existe pas dans le formulaire : l'erreur était invisible → submit muet. Toute garde qui masque le formulaire (invitation consommée/expirée, tenant non Pending) doit être reflétée dans le blade (`@elseif` + callout), pas seulement dans `accept()`. Si une erreur doit être visible, la rendre via `@error('token')` dans le formulaire.
