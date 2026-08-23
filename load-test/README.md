# Load testing

## Outils
- Smoke local : ApacheBench (`ab`) — déjà dispo sur la machine dev
- Charge réaliste : k6 (à installer sur staging) — voir `k6.js`

## Lancer le smoke (dev)
```bash
ab -n 200 -c 10 http://cakeman.127.0.0.1.nip.io:8000/admin/login
ab -n 200 -c 10 http://cakeman.127.0.0.1.nip.io:8000/
ab -n 100 -c 10 -H "Authorization: Bearer <token>" http://127.0.0.1:8000/api/v1/tenants
```

## Lancer k6 (staging)
```bash
# installer k6 : https://k6.io/docs/getting-started/installation/
k6 run --vus 10 --duration 30s load-test/k6.js
k6 run load-test/k6.js  # utilise les stages définis dans le script
```

Variables d'env k6 :
- BASE_URL (défaut http://cakeman.127.0.0.1.nip.io:8000)
- TENANT_HOST (défaut acme.cakeman.127.0.0.1.nip.io:8000)
- API_TOKEN (Bearer pour /api/v1/tenants)
