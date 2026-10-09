<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value JSON(秘密の値は暗号化した文字列)
 * @property bool $is_secret
 * @property int|null $updated_by
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret', 'updated_by'];

    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
        ];
    }
}
