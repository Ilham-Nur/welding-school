import { cp, mkdir, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = process.cwd();
const dist = resolve(root, 'dist');
const client = resolve(dist, 'client');
const server = resolve(dist, 'server');

await rm(dist, { recursive: true, force: true });
await mkdir(client, { recursive: true });
await mkdir(server, { recursive: true });

await cp(resolve(root, 'public/templates/welding-school'), client, {
  recursive: true,
});
await cp(resolve(root, 'public/favicon.ico'), resolve(client, 'favicon.ico'));
await cp(resolve(root, 'public/logo_alpha.png'), resolve(client, 'logo_alpha.png'));
await cp(
  resolve(root, 'public/alpha-academy-directory-og.png'),
  resolve(client, 'alpha-academy-directory-og.png'),
);

const worker = `const securityHeaders = {
  'Referrer-Policy': 'strict-origin-when-cross-origin',
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'SAMEORIGIN',
};

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const assetRequest = url.pathname === '/'
      ? new Request(new URL('/index.html', url), request)
      : request;
    let response = await env.ASSETS.fetch(assetRequest);

    if (response.status === 404 && request.method === 'GET') {
      response = await env.ASSETS.fetch(new Request(new URL('/index.html', url), request));
    }

    const headers = new Headers(response.headers);
    for (const [name, value] of Object.entries(securityHeaders)) headers.set(name, value);
    return new Response(response.body, { status: response.status, headers });
  },
};
`;

await writeFile(resolve(server, 'index.js'), worker, 'utf8');
