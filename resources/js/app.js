import '../css/app.css';
import './theme.js';
import './auth.js';
import './repeat.js';
import './public.js';

// イラスト(WebP)をビルドに含める。画面からは Vite::asset() でハッシュ付きの URL を引く。
// 各ページが使う画像だけをブラウザに読ませるため、JS からは使わない(URL の表をここに持たせない)
import.meta.glob(['../images/illust/**/*.webp'], { eager: true, query: '?url', import: 'default' });
