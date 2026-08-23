---
paths:
  - 'tests/**'
---

# Tests

## Ajouter les nouvelles tables centrales à la purge des tests
Aucune transaction par test (migrate:fresh 1x/classe + purge des tables centrales). Toute nouvelle table centrale (ex. landing_leads) DOIT être ajoutée à la liste TRUNCATE de purgeCentralTables dans tests/Concerns/RefreshTenantDatabase.php, sinon les données persistent entre les tests.
