import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// 地理院タイル(淡色)。夜の配色では CSS でタイルを暗くする。ピンは文字を出すだけで、サーバーが渡した値を textContent で入れる
const TILE = 'https://cyberjapandata.gsi.go.jp/xyz/pale/{z}/{x}/{y}.png';
const ATTRIBUTION = '<a href="https://maps.gsi.go.jp/development/ichiran.html" target="_blank" rel="noopener">地理院タイル</a>';

L.Icon.Default.mergeOptions({ imagePath: '' });

const PIN_SVG = '<svg viewBox="0 0 30 40" width="30" height="40" aria-hidden="true"><path d="M15 1C7.8 1 2 6.6 2 13.6 2 23 15 39 15 39s13-16 13-25.4C28 6.6 22.2 1 15 1z"/><circle cx="15" cy="14" r="5"/></svg>';

function pinIcon(type) {
    return L.divIcon({
        className: 'map-pin map-pin-' + type,
        html: PIN_SVG,
        iconSize: [30, 40],
        iconAnchor: [15, 40],
    });
}

function button(label, text, onClick) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'map-ctl';
    b.setAttribute('aria-label', label);
    b.textContent = text;
    b.addEventListener('click', onClick);
    return b;
}

// 画面デザインの操作(拡大・縮小・現在地)。Leaflet 標準の操作の代わりに置く
function addControls(map, root) {
    const box = document.createElement('div');
    box.className = 'map-ctls';
    const labels = JSON.parse(root.dataset.labels || '{}');
    box.append(
        button(labels.in || '拡大', '+', () => map.zoomIn()),
        button(labels.out || '縮小', '−', () => map.zoomOut()),
        button(labels.locate || '現在地', '◎', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((p) => map.setView([p.coords.latitude, p.coords.longitude], 13), () => {});
            }
        }),
    );
    root.appendChild(box);
}

function distanceKm(a, b) {
    const rad = Math.PI / 180;
    const dLat = (b.lat - a.lat) * rad;
    const dLng = (b.lng - a.lng) * rad;
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLng / 2) ** 2;
    return 6371 * 2 * Math.asin(Math.sqrt(h));
}

// 地図の画面: 左の一覧と、範囲・種類・今週末・分類・ことばでの絞り込み。一覧は見えている範囲だけ
function initApp(app, map, pins) {
    const list = app.querySelector('[data-map-list]');
    const count = app.querySelector('[data-map-count]');
    const q = app.querySelector('[data-map-q]');
    const chips = app.querySelectorAll('.map-chip');
    const markers = new Map();
    const countText = count.dataset.template || 'この範囲に :count件';

    pins.forEach((pin, i) => {
        const link = document.createElement('a');
        link.href = pin.url;
        link.textContent = pin.title;
        const marker = L.marker([pin.lat, pin.lng], { icon: pinIcon(pin.type), title: pin.title }).bindPopup(link);
        markers.set(i, marker);
    });

    function state() {
        const kinds = new Set([...chips].filter((c) => c.dataset.kind && c.getAttribute('aria-pressed') === 'true').map((c) => c.dataset.kind));
        const weekend = [...chips].some((c) => c.hasAttribute('data-weekend') && c.getAttribute('aria-pressed') === 'true');
        const cats = [...chips].filter((c) => c.dataset.category && c.getAttribute('aria-pressed') === 'true').map((c) => c.dataset.category);
        return { kinds, weekend, cats, text: q.value.trim().toLowerCase() };
    }

    function render() {
        const s = state();
        const bounds = map.getBounds();
        const center = map.getCenter();
        const visible = [];
        pins.forEach((pin, i) => {
            const ok = s.kinds.has(pin.type)
                && (!s.weekend || pin.weekend)
                && (s.cats.length === 0 || s.cats.includes(pin.category))
                && (s.text === '' || pin.title.toLowerCase().includes(s.text));
            const marker = markers.get(i);
            if (ok) {
                marker.addTo(map);
                if (bounds.contains([pin.lat, pin.lng])) {
                    visible.push({ pin, km: distanceKm(center, { lat: pin.lat, lng: pin.lng }), i });
                }
            } else {
                marker.remove();
            }
        });
        visible.sort((a, b) => a.km - b.km);

        count.textContent = countText.replace(':count', String(visible.length));
        list.replaceChildren(...visible.slice(0, 50).map(({ pin, km, i }) => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = pin.url;
            const photo = document.createElement('span');
            photo.className = 'map-row-photo';
            const body = document.createElement('span');
            body.className = 'map-row-body';
            const kicker = document.createElement('span');
            kicker.className = 'map-row-kicker' + (pin.type === 'event' ? ' is-event' : '');
            kicker.textContent = pin.label || '';
            const title = document.createElement('strong');
            title.textContent = pin.title;
            const sub = document.createElement('span');
            sub.className = 'map-row-sub';
            sub.textContent = (pin.area ? pin.area + ' ・ ' : '') + km.toFixed(1) + 'km';
            body.append(kicker, title, sub);
            a.append(photo, body);
            a.addEventListener('mouseenter', () => markers.get(i).openPopup());
            li.appendChild(a);

            return li;
        }));
    }

    chips.forEach((chip) => chip.addEventListener('click', () => {
        chip.setAttribute('aria-pressed', chip.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
        render();
    }));
    q.addEventListener('input', render);
    map.on('moveend', render);
    render();
}

function init(el) {
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
    }
    const custom = el.dataset.controls === 'custom';
    const map = L.map(el, { scrollWheelZoom: custom, zoomControl: !custom }).setView([lat, lng], parseInt(el.dataset.zoom || '15', 10));
    L.tileLayer(TILE, { maxZoom: 18, attribution: ATTRIBUTION }).addTo(map);

    const app = el.closest('[data-map-app]');
    if (app && el.dataset.pins) {
        addControls(map, el);
        initApp(app, map, JSON.parse(el.dataset.pins));

        return;
    }
    if (el.dataset.pins) {
        for (const pin of JSON.parse(el.dataset.pins)) {
            const link = document.createElement('a');
            link.href = pin.url;
            link.textContent = pin.title;
            L.marker([pin.lat, pin.lng], { icon: pinIcon(pin.type), title: pin.title }).addTo(map).bindPopup(link);
        }
    } else {
        L.marker([lat, lng], { icon: pinIcon('spot'), title: el.dataset.title || '' }).addTo(map);
    }
}

document.querySelectorAll('[data-map]').forEach(init);
