<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * お問い合わせへの返信・削除依頼の結果のお知らせ(テキストのメール)。
 */
final class InquiryMail extends Mailable
{
    public function __construct(public readonly string $receiptNo, public readonly string $messageBody) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('inquiry.mail_subject', ['receipt' => $this->receiptNo]));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.inquiry');
    }
}
