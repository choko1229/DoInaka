import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// 地理院タイル(淡色)。夜の配色では CSS でタイルを暗くする。ピンは文字を出すだけで、サーバーが渡した値を textContent で入れる
const TILE = 'https://cyberjapandata.gsi.go.jp/xyz/pale/{z}/{x}/{y}.png';
const ATTRIBUTION = '<a href="https://maps.gsi.go.jp/development/ichiran.html" target="_blank" rel="noopener">地理院タイル</a>';

L.Icon.Default.mergeOptions({ imagePath: '' });

function pinIcon(type) {
    return L.divIcon({
        className: 'map-pin map-pin-' + type,
        html: '<span></span>',
        iconSize: [28, 28],
        iconAnchor: [14, 28],
    });
}

function init(el) {
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
    }
    const map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], parseInt(el.dataset.zoom || '15', 10));
    L.tileLayer(TILE, { maxZoom: 18, attribution: ATTRIBUTION }).addTo(map);

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