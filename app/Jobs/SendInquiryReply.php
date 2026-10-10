<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ReplyStatus;
use App\Mail\InquiryMail;
use App\Models\Inquiry;
use App\Models\InquiryReply;
use App\Services\Mail\MailConfigurator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * 返信・結果のお知らせのメールを送る。3回まで試し、だめなら管理画面に「送れなかった」と出す。
 * ログには宛先も本文も出さない(例外のクラス名だけ残す)。
 */
final class SendInquiryReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(public readonly int $replyId) {}

    public function handle(MailConfigurator $mail): void
    {
        $reply = InquiryReply::query()->find($this->replyId);
        $inquiry = $reply === null ? null : Inquiry::query()->find($reply->inquiry_id);
        if ($reply === null || $inquiry === null || $reply->status === ReplyStatus::Sent) {
            return;
        }
        if ($inquiry->email === null || $inquiry->email === '') {
            $reply->forceFill(['status' => ReplyStatus::Failed, 'failure' => 'no_email'])->save();

            return;
        }

        $mail->apply();
        Mail::to($inquiry->email)->send(new InquiryMail($inquiry->receipt_no, $reply->body));
        $reply->forceFill(['status' => ReplyStatus::Sent, 'sent_at' => now(), 'failure' => null])->save();
    }

    public function failed(Throwable $e): void
    {
        InquiryReply::query()->whereKey($this->replyId)->update(['status' => ReplyStatus::Failed->value, 'failure' => mb_substr(class_basename($e), 0, 100)]);
    }
}
