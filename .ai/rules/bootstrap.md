---
paths:
  - bootstrap/app.php
---

# Bootstrap

## Tenancy Livewire : init via le groupe web, pas par mutation de route
Le endpoint Livewire (default-livewire.update, enregistré globalement par le paquet avec web uniquement) ne doit PAS être rendu tenant-aware en mutant la route au boot : Livewire enregistre sa route pendant le boot des providers mais elle disparaît de la collection avant le chargement des routes (booted() ne la trouve jamais). Fix déterministe : InitializeTenancyByDomainUnlessCentral est ajouté au GROUPE web via $middleware->web(append: [...]) dans bootstrap/app.php — il initialise la tenancy sur tout sous-domaine tenant (requêtes Livewire comprises) et no-op sur les domaines centraux. Ne pas ajouter de route update custom (getUpdateUri() est global et casserait le Livewire central).
