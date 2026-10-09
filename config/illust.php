<?php

declare(strict_types=1);

return [
    // 元の PNG(Git には入れない。約200MB)
    'source_path' => resource_path('images/illust/src'),

    // illust:build が作る WebP の置き場所(コミットする)。下に wide / card / card-sm ができる
    'output_path' => resource_path('images/illust'),

    'webp_quality' => 80,
];
