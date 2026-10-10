<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * 列挙型の表示名を lang/ja/enums.php から引く(画面の文言は lang から出す)。
 */
trait HasLabel
{
    public function label(): string
    {
        $label = __('enums.'.Str::snake(class_basename(static::class)).'.'.$this->value);

        return is_string($label) ? $label : $this->value;
    }
}
