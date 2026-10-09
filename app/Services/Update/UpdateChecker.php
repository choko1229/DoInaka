<?php

declare(strict_types=1);

namespace App\Services\Update;

use App\Contracts\ReleaseSource;
use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GitHub Releases の最新を確認する(適用はしない)。
 *
 * - ベータ(プレリリース)は、設定 update.accept_beta が ON のときだけ対象にする
 * - 版の形が違うタグ・ZIP のないリリースは無視する
 * - 今の版が分からない(開発環境)ときは何も更新しない
 */
class UpdateChecker
{
    public function __construct(
        private readonly ReleaseSource $source,
        private readonly CurrentVersion $current,
        private readonly SettingsService $settings,
        private readonly AppMetaService $meta,
    ) {}

    public function check(): UpdateCheckResult
    {
        $current = $this->current->get();
        $repository = $this->settings->string(SettingKey::UpdateRepository);

        try {
            $releases = $this->source->releases($repository);
        } catch (Throwable $e) {
            $this->remember('failed', null, null);
            Log::channel('app')->warning('更新の確認に失敗しました。', ['reason' => $e->getMessage()]);

            throw $e;
        }

        $acceptBeta = $this->settings->bool(SettingKey::UpdateAcceptBeta);
        $available = null;
        $skippedBeta = null;

        foreach ($releases as $release) {
            if ($current === null || ! $release->version->isNewerThan($current)) {
                continue;
            }
            if ($release->prerelease && ! $acceptBeta) {
                $skippedBeta ??= $release;

                continue;
            }
            $available = $release;
            break;
        }

        // 新しい正式版があれば、それより古いベータは「見送った」に出さない
        if ($available !== null && $skippedBeta !== null && ! $skippedBeta->version->isNewerThan($available->version)) {
            $skippedBeta = null;
        }

        $this->remember('success', $available, $skippedBeta);

        return new UpdateCheckResult($current, $available, $skippedBeta);
    }

    private function remember(string $status, ?ReleaseInfo $available, ?ReleaseInfo $skippedBeta): void
    {
        $this->meta->set(AppMetaKey::LastUpdateCheck, json_encode([
            'at' => now()->toIso8601String(),
            'status' => $status,
            'latest' => $available === null ? null : $this->summary($available),
            'skipped_beta' => $skippedBeta === null ? null : $this->summary($skippedBeta),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(ReleaseInfo $release): array
    {
        return [
            'version' => $release->version->__toString(),
            'name' => $release->name,
            'prerelease' => $release->prerelease,
            'body' => mb_substr($release->body, 0, 4000),
            'url' => $release->htmlUrl,
            'published_at' => $release->publishedAt?->toIso8601String(),
        ];
    }
}
