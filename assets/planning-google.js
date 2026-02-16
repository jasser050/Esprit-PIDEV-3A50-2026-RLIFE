async function getGoogleEvents() {
  const res = await fetch('/api/google-calendar/events', { credentials: 'same-origin' });
  const data = await res.json();

  return data.events.map(e => ({
    id: 'google-' + e.id,
    title: e.summary ?? '(sans titre)',
    start: e.start,
    end: e.end,
    backgroundColor: '#34A853', // vert Google
    borderColor: '#34A853',
  }));
}