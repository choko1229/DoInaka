// 処理中・待ち・延期のあいだ、10秒ごとに、画面の中の「自動で更新する部分」([data-ai-live])だけを差し替える。
//  - 同じ URL をもう一度読み、[data-ai-live] の id が同じ部分だけを入れ替える(入力中のフォームや、スクロールの位置は変えない)
//  - [data-ai-active](まだ変わりうるもの)が1つもなくなったら、止める
//  - 画面が見えていないタブでは、動かさない
const INTERVAL = 10000;

const live = () => document.querySelectorAll('[data-ai-live]');
const active = () => document.querySelector('[data-ai-active]') !== null;

async function refresh() {
    if (document.hidden || !active()) {
        return;
    }
    try {
        const response = await fetch(location.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) {
            return;
        }
        const next = new DOMParser().parseFromString(await response.text(), 'text/html');
        live().forEach((el) => {
            const fresh = el.id ? next.getElementById(el.id) : null;
            if (fresh) {
                el.replaceWith(fresh);
            }
        });
        document.querySelectorAll('[data-ai-updated]').forEach((el) => {
            el.textContent = ' / ' + new Date().toLocaleTimeString('ja-JP');
        });
    } catch {
        // つながらないときは、次の回にまた試す
    }
}

if (live().length > 0) {
    setInterval(refresh, INTERVAL);
}