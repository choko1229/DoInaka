<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\SettingKey;
use App\Enums\SubmissionStatus;
use App\Models\Media;
use App\Models\MediaOriginal;
use App\Models\Submission;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * 定期処理(設計書8・12.4): 却下ボックスの90日を過ぎた投稿(画像も)と、60日を過ぎた元画像を物理削除する。
 */
final class SubmissionPruner
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array{rejected: int, originals: int, ip_hashes: int}
     */
    public function prune(?Carbon $now = null): array
    {
        $now ??= now();

        return ['rejected' => $this->pruneRejected($now), 'originals' => $this->pruneOriginals($now), 'ip_hashes' => $this->pruneIpHashes($now)];
    }

    /** 期限(expires_at)を過ぎた却下・自動却下を、公開用・元画像のファイルごと消す */
    private function pruneRejected(Carbon $now): int
    {
        $count = 0;

        Submission::query()
            ->whereIn('status', [SubmissionStatus::Rejected, SubmissionStatus::AutoRejected])
            ->where('expires_at', '<', $now)
            ->each(function (Submission $submission) use (&$count): void {
                foreach ($submission->media as $media) {
                    // 公開コンテンツに付いている画像(承認後)は、投稿の削除では消さない
                    if ($media->mediable_type !== null) {
                        continue;
                    }
                    $this->deleteMedia($media);
                }
                $submission->delete();
                $count++;
            });

        return $count;
    }

    /** 期限を過ぎた元画像のファイルと記録を消す(公開用の WebP は残る) */
    private function pruneOriginals(Carbon $now): int
    {
        $count = 0;

        MediaOriginal::query()->where('expires_at', '<', $now)->each(function (MediaOriginal $original) use (&$count): void {
            Storage::disk($original->disk)->delete($original->path);
            $original->delete();
            $count++;
        });

        return $count;
    }

    /** IP のハッシュは90日(設定 privacy.ip_hash_retention_days)で消す。投稿そのものは残る */
    private function pruneIpHashes(Carbon $now): int
    {
        $days = $this->settings->int(SettingKey::PrivacyIpHashRetentionDays);

        return Submission::query()->whereNotNull('ip_hash')->where('created_at', '<', $now->copy()->subDays($days))->update(['ip_hash' => null]);
    }

    private function deleteMedia(Media $media): void
    {
        foreach (['path_large', 'path_medium', 'path_small'] as $column) {
            $path = $media->getAttribute($column);
            if (is_string($path) && $path !== '') {
                Storage::disk($media->disk)->delete($path);
            }
        }

        $original = $media->original;
        if ($original !== null) {
            Storage::disk($original->disk)->delete($original->path);
        }

        $media->delete();
    }
}
