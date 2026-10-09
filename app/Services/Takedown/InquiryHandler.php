<?php

declare(strict_types=1);

namespace App\Services\Takedown;

use App\Enums\AuditAction;
use App\Enums\InquiryStatus;
use App\Enums\ReplyStatus;
use App\Jobs\SendInquiryReply;
use App\Models\Article;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\InquiryReply;
use App\Models\Media;
use App\Models\Spot;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * お問い合わせの対応(管理画面)。返信メール、状態の変更、削除依頼の「削除する / ぼかしを外して残す」。
 * 削除は管理者が押したときだけ。自動では何も消さない。結果はメールアドレスがあれば知らせる(キューで送り、3回まで試す)。
 */
final class InquiryHandler
{
    public function __construct(
        private readonly ContentHolds $holds,
        private readonly MediaBlur $blur,
        private readonly AuditLogger $audit,
    ) {}

    public function setStatus(Inquiry $inquiry, InquiryStatus $status, User $actor): void
    {
        $inquiry->forceFill(['status' => $status, 'handled_by' => $actor->id, 'handled_at' => $status === InquiryStatus::Done ? now() : null])->save();
        $this->audit->record(AuditAction::InquiryHandle, $actor, 'inquiry', $inquiry->id, ['status' => $status->value]);
    }

    /** 返信を保存して、送信をキューに積む */
    public function reply(Inquiry $inquiry, string $body, User $actor, string $kind = 'reply'): InquiryReply
    {
        $reply = InquiryReply::query()->create([
            'inquiry_id' => $inquiry->id,
            'kind' => $kind,
            'body' => $body,
            'sent_by' => $actor->id,
            'status' => ($inquiry->email === null || $inquiry->email === '') ? ReplyStatus::Failed : ReplyStatus::Queued,
            'failure' => ($inquiry->email === null || $inquiry->email === '') ? 'no_email' : null,
        ]);

        if ($reply->status === ReplyStatus::Queued) {
            SendInquiryReply::dispatch($reply->id)->onQueue('high')->afterCommit();
        }
        $this->audit->record(AuditAction::InquiryReply, $actor, 'inquiry', $inquiry->id, ['kind' => $kind]);

        return $reply;
    }

    /** 送れなかったメールをもう一度送る */
    public function retry(InquiryReply $reply): void
    {
        $reply->forceFill(['status' => ReplyStatus::Queued, 'failure' => null])->save();
        SendInquiryReply::dispatch($reply->id)->onQueue('high');
    }

    /** 削除する: 確認中の対象(ページまたは写真)を消し、依頼を済みにして、結果を知らせる */
    public function remove(Inquiry $inquiry, User $actor): void
    {
        DB::transaction(function () use ($inquiry): void {
            foreach ($inquiry->holds()->whereNull('released_at')->get() as $hold) {
                if ($hold->media_id !== null) {
                    $media = Media::query()->find($hold->media_id);
                    if ($media !== null) {
                        $this->deleteMedia($media);
                    }

                    continue;
                }
                $target = match ($hold->holdable_type) {
                    'event' => Event::query()->find($hold->holdable_id),
                    'spot' => Spot::query()->find($hold->holdable_id),
                    'article' => Article::query()->find($hold->holdable_id),
                    default => null,
                };
                if ($target !== null) {
                    $target->forceFill(['is_published' => false])->save();
                    $target->delete();
                }
            }
            $this->holds->close($inquiry);
            $inquiry->forceFill(['result' => 'removed'])->save();
        });

        $this->finish($inquiry, $actor, AuditAction::TakedownRemove, __('inquiry.result_removed'));
    }

    /** ぼかしを外して残す */
    public function keep(Inquiry $inquiry, User $actor): void
    {
        $this->holds->release($inquiry);
        $inquiry->forceFill(['result' => 'kept'])->save();

        $this->finish($inquiry, $actor, AuditAction::TakedownKeep, __('inquiry.result_kept'));
    }

    private function finish(Inquiry $inquiry, User $actor, AuditAction $action, string $message): void
    {
        $this->setStatus($inquiry, InquiryStatus::Done, $actor);
        $this->audit->record($action, $actor, 'inquiry', $inquiry->id);
        if ($inquiry->email !== null && $inquiry->email !== '') {
            $this->reply($inquiry, $message, $actor, 'result');
        }
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
        $this->blur->purge($media);
        $media->delete();
    }
}
