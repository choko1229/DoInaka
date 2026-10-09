// 投稿フォームの部品: 都道府県 → 市区町村、地図で指した場所の候補、写真の枚数の確認。
// どれも JavaScript がなくても送れるように、HTML の入力欄は普通のフォームで作ってある。

const picker = document.querySelector('[data-region-picker]');

async function loadCities(prefId, selectedId) {
    const city = picker.querySelector('[data-city]');
    const res = await fetch(`${picker.dataset.api}?parent_id=${encodeURIComponent(prefId)}`, { headers: { Accept: 'application/json' } });
    if (!res.ok) {
        return;
    }
    const { data } = await res.json();
    const placeholder = city.querySelector('option').cloneNode(true);
    city.replaceChildren(placeholder);
    for (const r of data) {
        const option = document.createElement('option');
        option.value = String(r.id);
        option.textContent = r.name;
        city.append(option);
    }
    if (selectedId) {
        city.value = String(selectedId);
    }
}

if (picker) {
    picker.querySelector('[data-pref]').addEventListener('change', (e) => loadCities(e.target.value, null));
}

const mapEl = document.querySelector('[data-map-picker]');
if (mapEl) {
    import('leaflet').then(async ({ default: L }) => {
        await import('leaflet/dist/leaflet.css');
        const latInput = document.querySelector('[data-lat]');
        const lngInput = document.querySelector('[data-lng]');
        const start = latInput.value && lngInput.value ? [parseFloat(latInput.value), parseFloat(lngInput.value)] : [34.34, 134.04];
        const map = L.map(mapEl, { scrollWheelZoom: false }).setView(start, latInput.value ? 15 : 9);
        L.tileLayer('https://cyberjapandata.gsi.go.jp/xyz/pale/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '<a href="https://maps.gsi.go.jp/development/ichiran.html" target="_blank" rel="noopener">地理院タイル</a>',
        }).addTo(map);

        let marker = null;
        const place = (lat, lng) => {
            marker ? marker.setLatLng([lat, lng]) : (marker = L.marker([lat, lng]).addTo(map));
            latInput.value = lat.toFixed(6);
            lngInput.value = lng.toFixed(6);
        };
        if (latInput.value) {
            place(start[0], start[1]);
        }

        map.on('click', async (e) => {
            place(e.latlng.lat, e.latlng.lng);
            // 近い市区町村を、地域の候補として自動で入れる
            if (!picker) {
                return;
            }
            const res = await fetch(`${picker.dataset.nearest}?lat=${e.latlng.lat}&lng=${e.latlng.lng}`, { headers: { Accept: 'application/json' } });
            const { data } = res.ok ? await res.json() : { data: null };
            if (data) {
                const pref = picker.querySelector('[data-pref]');
                if (data.parent_id && pref.value !== String(data.parent_id)) {
                    pref.value = String(data.parent_id);
                    await loadCities(data.parent_id, data.id);
                } else {
                    picker.querySelector('[data-city]').value = String(data.id);
                }
            }
        });
    });
}

const photos = document.querySelector('[data-photos]');
if (photos) {
    photos.addEventListener('change', () => {
        const max = parseInt(photos.dataset.max, 10);
        if (photos.files.length > max) {
            window.alert(`写真は ${max} 枚までです。`);
            photos.value = '';
        }
    });
}