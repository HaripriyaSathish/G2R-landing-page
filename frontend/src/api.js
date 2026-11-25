// Base URL of the PHP backend. Empty in dev (Vite proxies /api and /uploads).
// For production set VITE_API_URL, e.g. https://api.g2rglobalsolutions.com
export const API_URL = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')

export async function fetchSite() {
  const res = await fetch(`${API_URL}/api/site.php`, { headers: { Accept: 'application/json' } })
  const json = await res.json().catch(() => null)
  if (!res.ok || !json?.ok) throw new Error(json?.error || 'Unable to load website content')
  return json
}

export async function sendEnquiry(data) {
  const res = await fetch(`${API_URL}/api/contact.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(data),
  })
  const json = await res.json().catch(() => ({ ok: false, error: 'Server error' }))
  if (!res.ok || !json.ok) {
    const err = new Error(json.error || 'Something went wrong')
    err.fields = json.errors || {}
    throw err
  }
  return json
}
