const BASE_URL = 'http://saasapp.s196298.fvl-001.webo-facto.com/api/v1';

async function apiFetch(endpoint, options = {}) {
  const url = `${BASE_URL}${endpoint}`;
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new Error(data.error || `Erreur ${res.status}`);
  }
  return data;
}

export function validateLicense(code) {
  return apiFetch('/license/validate', {
    method: 'POST',
    body: JSON.stringify({ code }),
  });
}

export function registerScreen(license_code, deviceInfo = {}) {
  return apiFetch('/screen/register', {
    method: 'POST',
    body: JSON.stringify({ license_code, ...deviceInfo }),
  });
}

export function fetchCatalog(token) {
  return apiFetch(`/catalog?token=${encodeURIComponent(token)}`);
}

export function sendHeartbeat(token) {
  return apiFetch('/screen/heartbeat', {
    method: 'POST',
    body: JSON.stringify({ token }),
  });
}

export function sendOffline(token) {
  return apiFetch('/screen/offline', {
    method: 'POST',
    body: JSON.stringify({ token }),
  });
}

export function trackClick(token, item_type, item_id, item_name) {
  return apiFetch('/track', {
    method: 'POST',
    body: JSON.stringify({ token, item_type, item_id, item_name }),
  });
}

export function sendLog(token, message, level = 'info') {
  return apiFetch('/screen/log', {
    method: 'POST',
    body: JSON.stringify({ token, level, message }),
  });
}
