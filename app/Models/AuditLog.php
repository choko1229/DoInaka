<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property AuditAction $action
 * @property string|null $target_type
 * @property string|null $target_id
 * @property array<string, mixed>|null $detail
 * @property string|null $ip_hash
 */
class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'target_type', 'target_id', 'detail', 'ip_hash'];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'detail' => 'array',
        ];
    }
}
