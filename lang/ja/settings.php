<?php

declare(strict_types=1);

return [
    'errors' => [
        'type' => '設定「:key」の型が違います(:type にしてください)。',
        'null' => '設定「:key」は空にできません。',
        'search_driver' => '検索の方式は ngram か like です。',
        'window_mode' => '更新の時間帯の決め方は auto か fixed です。',
        'hour' => '時刻は 0〜23 の整数にしてください。',
        'score' => 'スコアは 0〜1 の数にしてください。',
        'positive_int' => '1以上の整数にしてください。',
        'models_list' => 'モデルは、順番どおりのリスト(第1候補、予備…)で指定してください。',
        'models_free' => '使えるのは無料のモデル(名前の末尾が :free)だけです。有料のモデルは設定できません。',
        'positive_int_or_null' => '1以上の整数にするか、空にしてください。',
    ],
];
