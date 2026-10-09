<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Security\IpHasher;
use Illuminate\Http\Request;

/**
 * 管理操作を audit_logs に残す(設計書13.2)。設定の変更・会員の停止・更新の適用など、管理操作はすべてここを通す。
 */
class AuditLogger
{
    public function __construct(
        private readonly IpHasher $ipHasher,
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $detail  変更前後など。秘密の値はマスクしてから渡す
     */
    public function record(AuditAction $action, ?User $user, ?string $targetType = null, string|int|null $targetId = null, array $detail = []): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId === null ? null : (string) $targetId,
            'detail' => $detail === [] ? null : $detail,
            'ip_hash' => $this->ipHasher->hash($this->request->ip()),
        ]);
    }
}
