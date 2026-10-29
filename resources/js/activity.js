const MAX_MS = 30 * 60 * 1000;
const HEARTBEAT_MS = 60000;

function endpoint() {
    return document.querySelector('meta[name="nova-analytics"]')?.getAttribute('content') || '';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function routeName() {
    const name = document.querySelector('meta[name="nova-route"]')?.getAttribute('content') || '';
    return name || null;
}

function viewToken() {
    const bytes = new Uint8Array(8);
    if (window.crypto?.getRandomValues) {
        window.crypto.getRandomValues(bytes);
    } else {
        for (let i = 0; i < bytes.length; i += 1) {
            bytes[i] = Math.floor(Math.random() * 256);
        }
    }

    return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
}

function controlLabel(el) {
    if (el instanceof HTMLInputElement && (el.type === 'submit' || el.type === 'button')) {
        return (el.value || el.getAttribute('aria-label') || 'Enviar').replace(/\s+/g, ' ').trim().slice(0, 80);
    }

    const raw = el.getAttribute('aria-label') || el.getAttribute('title') || el.textContent || '';

    return raw.replace(/\s+/g, ' ').trim().slice(0, 80);
}

function startActivity() {
    if (!endpoint() || !csrfToken() || navigator.webdriver) {
        return;
    }

    const token = viewToken();
    const queue = [];
    let accumulated = 0;
    let visibleStart = document.visibilityState === 'visible' ? performance.now() : null;
    let pageViewSent = false;
    let lastSentDuration = 0;
    let timer = null;
    let heartbeat = null;
    let lastClickKey = '';
    let lastClickAt = 0;

    function visibleMs() {
        let extra = 0;
        if (visibleStart !== null && document.visibilityState === 'visible') {
            extra = performance.now() - visibleStart;
        }

        return Math.min(MAX_MS, Math.round(accumulated + extra));
    }

    function pause() {
        if (visibleStart === null) {
            return;
        }
        accumulated = Math.min(MAX_MS, accumulated + (performance.now() - visibleStart));
        visibleStart = null;
    }

    function resume() {
        if (document.visibilityState === 'visible' && visibleStart === null && accumulated < MAX_MS) {
            visibleStart = performance.now();
        }
    }

    function pagePayload(name) {
        const payload = {
            event_name: name,
            path: window.location.pathname,
            route_name: routeName(),
            view_token: token,
            duration_ms: visibleMs(),
        };
        if (name === 'page_view') {
            payload.referrer = document.referrer || null;
        }

        return payload;
    }

    function post(events) {
        if (!events.length) {
            return;
        }

        try {
            fetch(endpoint(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ events }),
                credentials: 'same-origin',
                keepalive: true,
            }).catch(() => {});
        } catch (error) {
            // La medición nunca debe interrumpir la página.
        }
    }

    function flush(leaving = false) {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }

        const duration = visibleMs();
        const sendPage = !pageViewSent;
        const sendDuration = pageViewSent && duration - lastSentDuration >= (leaving ? 1000 : 15000);
        const events = queue.splice(0, sendPage || sendDuration ? 24 : 25);
        if (sendPage) {
            events.unshift(pagePayload('page_view'));
            pageViewSent = true;
            lastSentDuration = duration;
        } else if (sendDuration) {
            events.push(pagePayload('page_duration'));
            lastSentDuration = duration;
        }

        post(events);

        if (queue.length) {
            scheduleFlush(200);
        } else if (pageViewSent && duration >= MAX_MS && heartbeat) {
            clearInterval(heartbeat);
            heartbeat = null;
        }
    }

    function scheduleFlush(delay) {
        if (timer) {
            return;
        }
        timer = setTimeout(() => flush(false), delay);
    }

    window.novaTrack = function novaTrack(eventName, payload = {}) {
        if (!eventName) {
            return;
        }

        queue.push({
            event_name: eventName,
            event_id: payload.event_id ?? null,
            path: payload.path || window.location.pathname,
            referrer: payload.referrer ?? null,
            route_name: routeName(),
            view_token: token,
        });
        scheduleFlush(800);
    };

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const el = target.closest('a, button, [role="button"], input[type="submit"], input[type="button"], summary');
        if (!el || el.closest('[data-nova-track="off"]')) {
            return;
        }
        if (el.matches('button[type="submit"], input[type="submit"], [type="password"]')) {
            return;
        }

        const label = controlLabel(el);
        if (!label) {
            return;
        }

        const key = `${label}|${window.location.pathname}`;
        const now = Date.now();
        if (key === lastClickKey && now - lastClickAt < 400) {
            return;
        }
        lastClickKey = key;
        lastClickAt = now;

        queue.push({
            event_name: 'click',
            label,
            path: window.location.pathname,
            route_name: routeName(),
            view_token: token,
        });

        if (queue.length >= 12) {
            flush(false);
            return;
        }

        scheduleFlush(1000);
    }, { passive: true });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.closest('[data-nova-track="off"]')) {
            return;
        }

        const button = form.querySelector('button[type="submit"], input[type="submit"]');
        queue.push({
            event_name: 'submit',
            label: button ? controlLabel(button) : 'Formulario',
            path: window.location.pathname,
            route_name: routeName(),
            view_token: token,
        });
        flush(true);
    }, true);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            pause();
            flush(true);
            return;
        }
        resume();
    });

    window.addEventListener('pagehide', () => {
        pause();
        flush(true);
    });

    heartbeat = setInterval(() => {
        if (document.visibilityState === 'visible') {
            flush(false);
        }
    }, HEARTBEAT_MS);
}

if (document.prerendering) {
    document.addEventListener('prerenderingchange', () => {
        if (!document.prerendering) {
            startActivity();
        }
    }, { once: true });
} else {
    startActivity();
}
