// タグの入力: 読点・カンマで区切った文字列を、チップ(押すと外せる)で見せる。元の入力欄が値の持ち主で、JavaScript がなくても使える
document.querySelectorAll('input[data-tags]').forEach((input) => {
    const wrap = document.createElement('div');
    wrap.className = 'tag-input';
    const list = document.createElement('span');
    list.className = 'tag-input-list';
    const add = document.createElement('input');
    add.type = 'text';
    add.className = 'tag-input-add';
    add.placeholder = '+ タグ';
    add.maxLength = 40;
    add.setAttribute('aria-label', input.getAttribute('aria-label') || 'タグを足す');

    const read = () => input.value.split(/[、,，]/).map((s) => s.trim()).filter((s) => s !== '');
    const write = (tags) => {
        input.value = tags.join('、');
        render();
    };
    const render = () => {
        list.replaceChildren(...read().map((tag) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'tag-chip tag-chip-button';
            chip.textContent = tag + ' ×';
            chip.setAttribute('aria-label', tag + ' を外す');
            chip.addEventListener('click', () => write(read().filter((t) => t !== tag)));

            return chip;
        }));
    };
    const commit = () => {
        const value = add.value.trim();
        if (value !== '' && !read().includes(value)) {
            write([...read(), value]);
        }
        add.value = '';
    };
    add.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',' || e.key === '、') {
            e.preventDefault();
            commit();
        }
    });
    add.addEventListener('blur', commit);

    input.type = 'hidden';
    input.parentNode.insertBefore(wrap, input);
    wrap.append(list, add, input);
    render();
});