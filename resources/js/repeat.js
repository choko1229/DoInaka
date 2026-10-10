// 行のくり返し(日程・情報元)。<template data-repeat-template="名前"> を、[data-repeat-target="名前"] の末尾に足す。
// テンプレートの中の __INDEX__ を、行の番号に置き換える。

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;

    const add = target.closest('[data-repeat-add]');
    if (add) {
        const name = add.dataset.repeatAdd;
        const template = document.querySelector(`template[data-repeat-template="${name}"]`);
        const container = document.querySelector(`[data-repeat-target="${name}"]`);
        if (!template || !container) return;
        const index = Number(container.dataset.nextIndex || container.children.length);
        container.dataset.nextIndex = String(index + 1);
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        container.insertAdjacentHTML('beforeend', html);
        return;
    }

    const remove = target.closest('[data-repeat-remove]');
    if (remove) {
        remove.closest('.repeat-row')?.remove();
    }
});