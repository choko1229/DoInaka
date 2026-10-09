<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UpdateStatus;
use App\Enums\UpdateTrigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $version_from
 * @property string $version_to
 * @property bool $is_beta
 * @property UpdateTrigger $trigger
 * @property string|null $triggered_by
 * @property UpdateStatus $status
 * @property string|null $log
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class UpdateRun extends Model
{
    protected $fillable = [
        'version_from', 'version_to', 'is_beta', 'trigger', 'triggered_by', 'status', 'log', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'is_beta' => 'boolean',
            'trigger' => UpdateTrigger::class,
            'status' => UpdateStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
