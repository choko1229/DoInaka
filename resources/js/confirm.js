// インラインの onsubmit / onchange は、CSP(script-src 'self')で動かせないので、data 属性で受ける。
//   <form data-confirm="本当に消しますか?">  → 送信の前に確認する
//   <select data-autosubmit>                  → 選んだらフォームを送る
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('change', (event) => {
    const el = event.target;
    if (el instanceof HTMLElement && el.hasAttribute('data-autosubmit') && 'form' in el && el.form instanceof HTMLFormElement) {
        el.form.submit();
    }
});

// 削除依頼の確認中の写真は、ぼかして出す。著作権・名誉・その他の依頼のときだけ、タップで元の写真を見られる
document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement) || !target.hasAttribute('data-reveal-button')) {
        return;
    }
    const img = target.closest('figure')?.querySelector('img[data-reveal]');
    if (img instanceof HTMLImageElement && img.dataset.reveal) {
        img.src = img.dataset.reveal;
        img.removeAttribute('srcset');
        img.classList.remove('blurred-image');
        target.remove();
    }
});