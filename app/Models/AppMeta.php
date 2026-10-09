<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
class AppMeta extends Model
{
    protected $table = 'app_meta';

    protected $fillable = ['key', 'value'];
}
