---
paths:
  - 'app/Services/**'
---

# Services

## Pas de list() sur tableaux associatifs
Destructuring `[$a, $b] = $arr` exige des clés numériques — échoue (warning "Undefined array key") sur un tableau associatif ; accéder aux clés nommées (`$link['url']`).
