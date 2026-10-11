// 公開画面の小さな部品: 管理者バー、絞り込みの部分更新、URL のコピー、お気に入り・行った!
// どれも JavaScript がなくても動くように、HTML は普通のリンクとフォームで作ってある。

const bar = document.getElementById('admin-bar');
if (bar && bar.dataset.url) {
    // 管理者の情報は公開ページの HTML に入れず、表示後に読み込む(ページキャッシュに混ざらない)
    fetch(bar.dataset.url + '?url=' + encodeURIComponent(bar.dataset.page || '/'), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((r) => (r.ok ? r.text() : ''))
        .then((html) => {
            if (html) {
                bar.innerHTML = html;
            }
        })
        .catch(() => {});
}

// JavaScript が動くことを CSS に知らせる(スマホの分類の選択など)
document.documentElement.classList.add('js');

// 「現在地から10km以内」: チェックして送るときに、位置を調べて lat・lng・r を入れる(位置はサーバーに保存しない)
const withLocation = (form) =>
    new Promise((resolve) => {
        const near = form.querySelector('[data-near]');
        const set = (lat, lng, r) => {
            form.querySelector('[data-near-lat]').value = lat;
            form.querySelector('[data-near-lng]').value = lng;
            form.querySelector('[data-near-r]').value = r;
        };
        if (!near || !near.checked) {
            set('', '', '');
            resolve();
            return;
        }
        if (!navigator.geolocation) {
            near.checked = false;
            set('', '', '');
            resolve();
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                set(pos.coords.latitude.toFixed(5), pos.coords.longitude.toFixed(5), '10');
                resolve();
            },
            () => {
                near.checked = false;
                set('', '', '');
                resolve();
            },
            { timeout: 8000, maximumAge: 300000 },
        );
    });

const filter = document.querySelector('[data-filter-form]');
if (filter) {
    filter.addEventListener('submit', async (e) => {
        e.preventDefault();
        await withLocation(filter);
        // 位置が取れたら、lat・lng・r を付けて送る。チェックを外したときは、付けない
        const data = new FormData(filter);
        data.delete('near');
        // カレンダー表示は、部分更新せずに、ふつうに送る
        if (data.get('view') === 'calendar') {
            filter.submit();
            return;
        }
        const params = new URLSearchParams(data);
        for (const [k, v] of [...params]) {
            if (v === '') {
                params.delete(k);
            }
        }
        const results = document.querySelector('.results');
        try {
            const res = await fetch(filter.dataset.api + '?pref=' + encodeURIComponent(filter.dataset.pref) + '&' + params, { headers: { Accept: 'application/json' } });
            if (!res.ok) {
                throw new Error('failed');
            }
            const data = await res.json();
            results.innerHTML = data.html;
            // 条件は URL に持つ(共有・戻るで再現できる)
            history.pushState(null, '', params.toString() === '' ? filter.action : filter.action + '?' + params);
        } catch {
            filter.submit();
        }
    });
}

document.querySelectorAll('[data-copy]').forEach((b) => {
    b.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(b.dataset.copy);
        } catch {
            // コピーできないブラウザでは何もしない
        }
    });
});

document.querySelectorAll('form[data-reaction]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = form.querySelector('input[name=_token]')?.value ?? '';
        const res = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: new FormData(form) });
        if (res.status === 401) {
            window.location.href = (await res.json()).login_url;
            return;
        }
        if (res.ok) {
            const data = await res.json();
            const count = form.querySelector('[data-count]');
            if (count) {
                count.textContent = String(data.count);
            }
            form.querySelector('button')?.setAttribute('aria-pressed', data.on ? 'true' : 'false');
        }
    });
});

if (document.querySelector('[data-map]')) {
    import('./map.js');
}

if (document.querySelector('[data-region-picker], [data-map-picker], [data-photos]')) {
    import('./post.js');
}