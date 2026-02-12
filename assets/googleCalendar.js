async function loadGoogleEvents() {
  const res = await fetch('/api/google-calendar/events', { credentials: 'same-origin' });
  if (!res.ok) throw new Error('Cannot load google events');

  const data = await res.json();

  return data.events.map(e => ({
    id: 'google-' + e.id,
    title: e.summary ?? '(sans titre)',
    start: e.start,
    end: e.end,
    source: 'google',
  }));
}