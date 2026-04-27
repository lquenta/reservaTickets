import './bootstrap';

window.novaTrack = function novaTrack(eventName, payload = {}) {
    if (!eventName || typeof window.axios === 'undefined') {
        return;
    }

    const body = {
        event_name: eventName,
        event_id: payload.event_id ?? null,
        path: payload.path ?? window.location.pathname,
        referrer: payload.referrer ?? document.referrer ?? null,
    };

    window.axios.post('/analytics/events', body).catch(() => {
        // Ignore tracking failures to avoid affecting UX.
    });
};
