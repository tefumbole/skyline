const API_BASE = import.meta.env.VITE_API_URL || '/api';
const STORAGE_KEY = 'alpha_supabase_auth';

function getToken() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed?.access_token || parsed?.currentSession?.access_token || null;
  } catch {
    return null;
  }
}

async function apiFetch(path, options = {}) {
  const token = getToken();
  const headers = { ...(options.headers || {}) };
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(`${API_BASE}${path}`, { ...options, headers });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || res.statusText || 'Request failed');
  return json;
}

export async function fetchEnvFiles() {
  const data = await apiFetch('/system/env-files');
  return {
    frontend: data.frontend || '',
    api: data.api || '',
  };
}

export async function saveEnvFiles({ frontend, api }) {
  return apiFetch('/system/env-files', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ frontend, api }),
  });
}
