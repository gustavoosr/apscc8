/* Cliente da API REST: Fetch + CSRF + tratamento padronizado de erros. */
const Api = (() => {
  const BASE = '/api/v1';
  let csrf = (window.APP && window.APP.csrf) || '';

  class ApiError extends Error {
    constructor(message, status, errors) { super(message); this.status = status; this.errors = errors || []; }
  }

  async function request(method, path, body, opts = {}) {
    const headers = { 'Accept': 'application/json' };
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    if (method !== 'GET') headers['X-CSRF-Token'] = csrf;
    const res = await fetch(BASE + path, { method, headers, credentials: 'same-origin', body: body !== undefined ? JSON.stringify(body) : undefined });
    if (res.status === 204) return null;
    if (opts.raw) return res;
    let json = null;
    try { json = await res.json(); } catch (_) { /* resposta sem JSON */ }
    if (res.status === 401 && !path.startsWith('/auth/login')) { location.href = '/login'; throw new ApiError('Sessão expirada.', 401); }
    if (!res.ok || !json || json.success === false) {
      throw new ApiError((json && json.message) || 'Erro ao comunicar com o servidor.', res.status, json && json.errors);
    }
    return json;
  }

  const qs = (params = {}) => {
    const p = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') p.append(k, v); });
    const s = p.toString();
    return s ? '?' + s : '';
  };

  return {
    ApiError, qs,
    setCsrf(t) { csrf = t; },
    get: (path, params) => request('GET', path + qs(params)),
    post: (path, body) => request('POST', path, body ?? {}),
    put: (path, body) => request('PUT', path, body ?? {}),
    del: (path) => request('DELETE', path),
    rawGet: (path, params) => request('GET', path + qs(params), undefined, { raw: true }),
  };
})();
