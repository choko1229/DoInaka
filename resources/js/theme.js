// 配色(時間帯と季節)。サーバーが日本時間で初期値を描画し、ここで1分ごとに見直す。
// 利用者の選択(自動 / 昼固定 / 夜固定)は Cookie に1年保存する。

const COOKIE = 'doinaka_theme';
const root = document.documentElement;

export function themeAt(date) {
    // 日本時間の時刻にそろえる(端末のタイムゾーンに関係なく)
    const hour = Number(
        new Intl.DateTimeFormat('en-GB', { hour: '2-digit', hourCycle: 'h23', timeZone: 'Asia/Tokyo' }).format(date),
    );
    if (hour >= 5 && hour < 10) return 'morning';
    if (hour >= 10 && hour < 16) return 'day';
    if (hour >= 16 && hour < 19) return 'evening';
    return 'night';
}

export function seasonAt(date) {
    const month = Number(
        new Intl.DateTimeFormat('en-GB', { month: 'numeric', timeZone: 'Asia/Tokyo' }).format(date),
    );
    if (month >= 3 && month <= 5) return 'spring';
    if (month >= 6 && month <= 8) return 'summer';
    if (month >= 9 && month <= 11) return 'autumn';
    return 'winter';
}

function readPreference() {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE}=([^;]*)`));
    const value = match ? decodeURIComponent(match[1]) : 'auto';
    return ['auto', 'day', 'night'].includes(value) ? value : 'auto';
}

function writePreference(value) {
    document.cookie = `${COOKIE}=${encodeURIComponent(value)}; path=/; max-age=31536000; samesite=lax`;
}

function resolveTheme(preference, now) {
    if (preference === 'day') return 'day';
    if (preference === 'night') return 'night';
    // 「自動」で OS がダークモードなら夜を優先する
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'night';
    return themeAt(now);
}

function apply() {
    const now = new Date();
    const preference = readPreference();
    root.dataset.theme = resolveTheme(preference, now);
    root.dataset.season = seasonAt(now);
    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.themeOption === preference));
    });
}

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-theme-option]') : null;
    if (!button) return;
    writePreference(button.dataset.themeOption);
    apply();
});

apply();
setInterval(apply, 60_000);
