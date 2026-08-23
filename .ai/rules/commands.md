---
paths:
  - app/Console/Commands/BillingCheckDue.php
---

# Commands

## BillingCheckDue : archivage avant suspension
Ordre des branches dans advance() : l'archivage auto (suspension > 30 j) doit être vérifié AVANT la branche de suspension (qui `return` tôt) — sinon un tenant suspendu n'est jamais archivé.
