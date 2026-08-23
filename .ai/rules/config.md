---
paths:
  - config/session.php
---

# Config

## Sessions toujours en base centrale (jamais dans le schéma tenant)
SESSION_CONNECTION doit être épinglé à pgsql (connexion centrale) : stancl bascule la connexion par défaut vers le schéma tenant pendant la requête, et le driver de session database écrit sur la connexion par défaut. Sans épinglage, la session GET atterrit dans le schéma tenant alors que le POST (CSRF) la relit en central → 419 « Page expirée ». La migration tenant 0001_01_01_000000_create_users_table.php ne doit PAS créer la table sessions.
