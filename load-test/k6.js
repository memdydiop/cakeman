import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';

const baseUrl = __ENV.BASE_URL || 'http://cakeman.127.0.0.1.nip.io:8000';
const tenantHost = __ENV.TENANT_HOST || 'http://acme.cakeman.127.0.0.1.nip.io:8000';
const apiToken = __ENV.API_TOKEN || '';

const ttfb = new Trend('ttfb');

export const options = {
    stages: [
        { duration: '30s', target: 10 },
        { duration: '1m', target: 50 },
        { duration: '2m', target: 50 },
        { duration: '30s', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<400', 'p(99)<800'],
        ttfb: ['p(95)<300'],
    },
};

export default function () {
    // Central — login page (guest, Livewire)
    let r1 = http.get(`${baseUrl}/admin/login`);
    check(r1, { 'login 200': (r) => r.status === 200 });
    ttfb.add(r1.timings.waiting);

    // Central — landing
    let r2 = http.get(`${baseUrl}/`);
    check(r2, { 'landing ok': (r) => r.status === 200 || r.status === 302 });

    // Central API (avec token si fourni)
    let headers = {};
    if (apiToken) headers['Authorization'] = `Bearer ${apiToken}`;
    let r3 = http.get(`${baseUrl}/api/v1/tenants`, { headers });
    check(r3, { 'api tenants 200|401': (r) => r.status === 200 || r.status === 401 });

    // Tenant — activation page (guest, pas de tenancy active requise)
    let r4 = http.get(`${tenantHost}/dashboard`);
    check(r4, { 'tenant dashboard 200|302': (r) => r.status === 200 || r.status === 302 });

    sleep(1);
}