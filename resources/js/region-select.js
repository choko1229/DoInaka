// 管理画面の地域の選択: 都道府県 → 市区町村 → 旧町村の3つに分けて選ぶ。元の1つの選択(name=region_id)が値の持ち主で、JavaScript がなくてもそのまま使える
document.querySelectorAll('[data-region-tree]').forEach((wrap) => {
    const source = wrap.querySelector('select[data-region-source]');
    if (!source) {
        return;
    }
    const tree = JSON.parse(wrap.dataset.regionTree);
    const labels = JSON.parse(wrap.dataset.labels || '{}');

    const make = (label) => {
        const field = document.createElement('label');
        field.className = 'region-part';
        const text = document.createElement('span');
        text.textContent = label;
        const select = document.createElement('select');
        field.append(text, select);

        return { field, select };
    };
    const fill = (select, items, selected, none) => {
        select.replaceChildren(new Option(none, ''), ...items.map((item) => new Option(item.name, String(item.id), false, String(item.id) === String(selected))));
        if (selected && ![...select.options].some((o) => o.value === String(selected))) {
            select.value = '';
        }
    };

    const pref = make(labels.pref || '都道府県');
    const city = make(labels.city || '市区町村');
    const old = make(labels.old || '旧町村');
    const row = document.createElement('div');
    row.className = 'region-parts';
    row.append(pref.field, city.field, old.field);

    // いまの値から、どの階層かを割り出す
    let p = null;
    let c = null;
    let o = null;
    const current = Number(source.value || 0);
    for (const pr of tree) {
        if (pr.id === current) {
            p = pr.id;
        }
        for (const ct of pr.cities) {
            if (ct.id === current) {
                p = pr.id;
                c = ct.id;
            }
            for (const od of ct.olds) {
                if (od.id === current) {
                    p = pr.id;
                    c = ct.id;
                    o = od.id;
                }
            }
        }
    }

    const sync = () => {
        const prefNode = tree.find((x) => String(x.id) === pref.select.value);
        const cityNode = prefNode?.cities.find((x) => String(x.id) === city.select.value);
        fill(city.select, prefNode?.cities ?? [], city.select.value, labels.none || '(選ばない)');
        const cityNode2 = prefNode?.cities.find((x) => String(x.id) === city.select.value) ?? cityNode;
        fill(old.select, cityNode2?.olds ?? [], old.select.value, labels.none || '(選ばない)');
        source.value = old.select.value || city.select.value || pref.select.value || '';
    };

    fill(pref.select, tree, p, '—');
    pref.select.value = p ? String(p) : '';
    fill(city.select, tree.find((x) => x.id === p)?.cities ?? [], c, labels.none || '(選ばない)');
    city.select.value = c ? String(c) : '';
    fill(old.select, tree.find((x) => x.id === p)?.cities.find((x) => x.id === c)?.olds ?? [], o, labels.none || '(選ばない)');
    old.select.value = o ? String(o) : '';

    pref.select.addEventListener('change', () => {
        city.select.value = '';
        old.select.value = '';
        sync();
    });
    city.select.addEventListener('change', () => {
        old.select.value = '';
        sync();
    });
    old.select.addEventListener('change', sync);

    const wasRequired = source.hasAttribute('required');
    source.hidden = true;
    source.removeAttribute('required');
    pref.select.required = wasRequired;
    wrap.insertBefore(row, source);
});