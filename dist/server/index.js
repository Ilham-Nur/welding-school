const securityHeaders = {
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
