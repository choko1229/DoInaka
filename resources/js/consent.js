// Cookie の同意(Google の同意モード v2)。
//  - 最初は、分析・広告のストレージをすべて「拒否」にしておく。選ぶまで、Google のスクリプトは読み込まない
//  - 許可: GA4(<meta name="ga4-id">)と AdSense(ページにある [data-consent-ads])を読み込む。広告は閲覧履歴に応じたものになりうる
//  - 許可しない: GA4 は読み込まない。AdSense は、閲覧履歴に応じない広告(NPA)だけ
//  - 選んだ内容は Cookie(doinaka_consent)に1年間覚える。フッターの「Cookie の設定」から選び直せる
const COOKIE = 'doinaka_consent';
const YEAR = 60 * 60 * 24 * 365;

const read = () => {
    const match = document.cookie.split('; ').find((c) => c.startsWith(COOKIE + '='));
    const value = match ? match.slice(COOKIE.length + 1) : '';
    return value === 'granted' || value === 'denied' ? value : null;
};

const write = (value) => {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${value}; Max-Age=${YEAR}; Path=/; SameSite=Lax${secure}`;
};

window.dataLayer = window.dataLayer || [];
function gtag() {
    window.dataLayer.push(arguments);
}
gtag('consent', 'default', { ad_storage: 'denied', analytics_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', wait_for_update: 500 });

const loaded = new Set();
const loadScript = (src, attrs = {}) => {
    if (loaded.has(src)) {
        return;
    }
    loaded.add(src);
    const el = document.createElement('script');
    el.async = true;
    el.src = src;
    Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v));
    document.head.appendChild(el);
};

const apply = (choice) => {
    const granted = choice === 'granted';
    gtag('consent', 'update', {
        ad_storage: granted ? 'granted' : 'denied',
        analytics_storage: granted ? 'granted' : 'denied',
        ad_user_data: granted ? 'granted' : 'denied',
        ad_personalization: granted ? 'granted' : 'denied',
    });

    const ga4 = document.querySelector('meta[name="ga4-id"]')?.getAttribute('content');
    if (granted && ga4 && /^G-[A-Z0-9]+$/.test(ga4)) {
        loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ga4));
        gtag('js', new Date());
        gtag('config', ga4, { anonymize_ip: true });
    }

    const ads = document.querySelectorAll('[data-consent-ads]');
    const client = ads[0]?.getAttribute('data-adsense-client');
    if (client && /^ca-pub-\d+$/.test(client)) {
        window.adsbygoogle = window.adsbygoogle || [];
        if (!granted) {
            window.adsbygoogle.requestNonPersonalizedAds = 1;
        }
        loadScript('https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(client), { crossorigin: 'anonymous' });
        ads.forEach(() => window.adsbygoogle.push({}));
    }
};

const banner = document.querySelector('[data-cookie-banner]');
const show = () => banner && (banner.hidden = false);
const hide = () => banner && (banner.hidden = true);

const stored = read();
if (stored) {
    apply(stored);
} else {
    show();
}

document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }
    const choice = target.closest('[data-cookie-choice]');
    if (choice instanceof HTMLElement) {
        const value = choice.dataset.cookieChoice;
        if (value === 'granted' || value === 'denied') {
            write(value);
            hide();
            apply(value);
        }
        return;
    }
    if (target.closest('[data-cookie-settings]')) {
        show();
    }
});