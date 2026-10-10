// 管理画面のログイン周り(キーや回復コードのコピー・ダウンロード、「保存しました」で進むボタンを有効にする)

function textOf(selector) {
    const el = document.querySelector(selector);
    if (!el) return '';
    // 回復コードは1行に1つ。キーは見えているとおりに
    return el.tagName === 'UL' ? [...el.querySelectorAll('li')].map((li) => li.textContent.trim()).join('\n') : el.textContent.trim();
}

document.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;

    const copy = target.closest('[data-copy]');
    if (copy) {
        try {
            await navigator.clipboard.writeText(textOf(copy.dataset.copy));
            const original = copy.textContent;
            copy.textContent = copy.dataset.copied || 'OK';
            setTimeout(() => { copy.textContent = original; }, 1500);
        } catch {
            // クリップボードが使えない環境では、画面の文字を選んでコピーしてもらう
        }
        return;
    }

    const download = target.closest('[data-download]');
    if (download) {
        const blob = new Blob([textOf(download.dataset.download) + '\n'], { type: 'text/plain' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = download.dataset.filename || 'download.txt';
        link.click();
        URL.revokeObjectURL(link.href);
    }
});

document.addEventListener('change', (event) => {
    const box = event.target instanceof HTMLInputElement ? event.target : null;
    if (!box || !box.dataset.enables) return;
    const next = document.querySelector(box.dataset.enables);
    if (!next) return;
    next.setAttribute('aria-disabled', box.checked ? 'false' : 'true');
    next.tabIndex = box.checked ? 0 : -1;
});

// 無効のまま押されても進まない
document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[aria-disabled="true"]') : null;
    if (link) event.preventDefault();
});