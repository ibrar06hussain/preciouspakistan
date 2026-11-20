import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Progressive-enhancement mobile navigation + Leaflet.js geographic maps.
// Compatible with Livewire's bundled Alpine.js without double-initializing Alpine.
document.addEventListener('DOMContentLoaded', () => {
    initMobileDrawer();
    initLeafletMaps();
});

function initMobileDrawer() {
    const toggleButtons = document.querySelectorAll('[data-mobile-nav-toggle]');
    const drawer = document.getElementById('mobile-navigation-drawer');

    if (!drawer || toggleButtons.length === 0) {
        return;
    }

    const setOpen = (open) => {
        drawer.classList.toggle('hidden', !open);
        toggleButtons.forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));
    };

    toggleButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const isCurrentlyOpen = !drawer.classList.contains('hidden');
            setOpen(!isCurrentlyOpen);
        });
    });

    drawer.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !drawer.classList.contains('hidden')) {
            setOpen(false);
        }
    });
}

function initLeafletMaps() {
    const containers = document.querySelectorAll('[data-leaflet-map]');
    if (containers.length === 0) {
        return;
    }

    containers.forEach((el) => {
        const lat = parseFloat(el.dataset.centerLat || '30.3753');
        const lng = parseFloat(el.dataset.centerLng || '69.3451');
        const zoom = parseInt(el.dataset.zoom || '6', 10);

        const map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const scriptId = el.dataset.geojsonId;
        const jsonEl = scriptId ? document.getElementById(scriptId) : null;
        if (!jsonEl) {
            return;
        }

        let collection = null;
        try {
            collection = JSON.parse(jsonEl.textContent || '{}');
        } catch {
            return;
        }

        if (!collection || !Array.isArray(collection.features) || collection.features.length === 0) {
            return;
        }

        const bounds = [];

        collection.features.forEach((feature) => {
            if (!feature.geometry || feature.geometry.type !== 'Point' || !Array.isArray(feature.geometry.coordinates)) {
                return;
            }
            const [fLng, fLat] = feature.geometry.coordinates;
            const props = feature.properties || {};
            const isAttraction = props.kind === 'attraction';

            const marker = L.circleMarker([fLat, fLng], {
                radius: isAttraction ? 6 : 8,
                color: isAttraction ? '#b45309' : '#065f46',
                fillColor: isAttraction ? '#f59e0b' : '#10b981',
                fillOpacity: 0.9,
                weight: 2,
            }).addTo(map);

            const title = escapeHtml(props.name || '');
            const sub = escapeHtml(props.region || props.category || '');
            const summary = escapeHtml(props.summary || '');
            const link = props.url ? `<div class="mt-1.5"><a href="${escapeHtml(props.url)}" style="color:#065f46;font-weight:700;text-decoration:underline;">Explore destination &rarr;</a></div>` : '';

            marker.bindPopup(
                `<div style="min-width:180px;"><strong>${title}</strong>${sub ? `<div style="font-size:11px;color:#475569;">${sub}</div>` : ''}${summary ? `<p style="font-size:12px;margin:4px 0 0;">${summary}</p>` : ''}${link}</div>`
            );

            bounds.push([fLat, fLng]);
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [32, 32], maxZoom: 11 });
        } else if (bounds.length === 1) {
            map.setView(bounds[0], Math.max(zoom, 9));
        }
    });
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
