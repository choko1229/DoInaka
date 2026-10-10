/**
 * ページの中で、コントラストの足りない文字と部品を探す(ブラウザの中で動かす。page.evaluate に渡す)。
 *  - 文字: 4.5:1(大きい文字 = 24px 以上、または 18.66px 以上の太字は 3:1)
 *  - 部品(ボタン・入力欄・チップ・タブ・バッジ・カードのリンク): 枠、枠がなければ塗りが、まわりの背景に対して 3:1 以上
 * 背景が画像・グラデーションの場所は、色が決められないので見ない(axe-core も同じ)。
 * この関数は自己完結(外の変数を使わない)にしてあり、本番の管理画面など、手でページに貼って動かすこともできる。
 */
export function scanContrast(doc) {
    doc = doc ?? document;
    const win = doc.defaultView;
    const parse = (c) => {
        const m = c.match(/rgba?\(([^)]+)\)/);
        if (!m) return null;
        const p = m[1].split(/[ ,/]+/).filter(Boolean).map(Number);
        return [p[0], p[1], p[2], p[3] ?? 1];
    };
    const lum = ([r, g, b]) => {
        const f = (v) => {
            v /= 255;
            return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
    };
    const ratio = (a, b) => {
        const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p);
        return (x + 0.05) / (y + 0.05);
    };
    const over = (top, bottom) => [0, 1, 2].map((i) => top[i] * top[3] + bottom[i] * (1 - top[3])).concat([1]);
    const backdrop = (el) => {
        const chain = [];
        for (let n = el; n; n = n.parentElement) {
            const s = win.getComputedStyle(n);
            if (s.backgroundImage !== 'none') return null;
            const c = parse(s.backgroundColor);
            if (c && c[3] > 0) {
                chain.push(c);
                if (c[3] === 1) break;
            }
        }
        let base = [255, 255, 255, 1];
        for (const c of chain.reverse()) base = over(c, base);
        return base;
    };
    const visible = (el) => {
        const r = el.getBoundingClientRect();
        const s = win.getComputedStyle(el);
        return r.width > 2 && r.height > 2 && s.visibility !== 'hidden' && s.display !== 'none' && Number(s.opacity) > 0 && !el.closest('.visually-hidden, [hidden], [aria-hidden=true]');
    };
    const name = (el) => el.tagName.toLowerCase() + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : '');

    const problems = [];

    // 文字
    const seen = new Set();
    const walker = doc.createTreeWalker(doc.body, win.NodeFilter.SHOW_TEXT);
    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        if (!node.nodeValue.trim()) continue;
        const el = node.parentElement;
        if (!el || seen.has(el) || !visible(el) || ['SCRIPT', 'STYLE', 'NOSCRIPT'].includes(el.tagName)) continue;
        seen.add(el);
        const s = win.getComputedStyle(el);
        const bg = backdrop(el);
        const fg = parse(s.color);
        if (!bg || !fg) continue;
        const size = parseFloat(s.fontSize);
        const bold = Number(s.fontWeight) >= 700;
        const need = size >= 24 || (size >= 18.66 && bold) ? 3 : 4.5;
        const r = ratio(over([fg[0], fg[1], fg[2], fg[3] * Number(s.opacity)], bg), bg);
        if (r < need) problems.push({ kind: '文字', target: name(el), text: node.nodeValue.trim().slice(0, 24), ratio: Number(r.toFixed(2)), need });
    }

    // 部品
    const sel = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]), select, textarea, button, .btn, .chip, .tabs a, [role=tab], .pill, a.card, .badge';
    for (const el of doc.querySelectorAll(sel)) {
        if (!visible(el)) continue;
        const s = win.getComputedStyle(el);
        const parentBg = backdrop(el.parentElement);
        if (!parentBg) continue;
        const sides = ['Top', 'Right', 'Bottom', 'Left'].map((side) => ({ w: parseFloat(s[`border${side}Width`]), c: parse(s[`border${side}Color`]), st: s[`border${side}Style`] }));
        const edge = sides.find((b) => b.w >= 1 && b.st !== 'none' && b.c && b.c[3] > 0);
        const own = parse(s.backgroundColor);
        const fill = own && own[3] > 0 ? over(own, parentBg) : null;
        let r = null;
        if (edge) r = ratio(over(edge.c, parentBg), parentBg);
        else if (fill) r = ratio(fill, parentBg);
        else continue; // 枠も塗りもない(文字だけ)ものは、文字の検査で見る
        // 枠と塗りの両方があるときは、どちらかが 3:1 あればよい
        if (edge && fill) r = Math.max(r, ratio(fill, parentBg));
        // 塗りだけで、塗りと背景の差が小さくても、塗りの上の文字が十分に読め、かつ文字が背景と同じ明るさ側ではないものは、文字の色で見分けられる(バッジ・タグの類)
        if (r < 3) problems.push({ kind: '部品', target: name(el), text: (el.innerText || el.value || el.placeholder || '').trim().slice(0, 24), ratio: Number(r.toFixed(2)), need: 3 });
    }
    return problems;
}
