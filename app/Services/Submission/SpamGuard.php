<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\MatchType;
use App\Enums\SettingKey;
use App\Models\NgWord;
use App\Models\Submission;
use App\Services\Security\IpHasher;
use App\Services\Setting\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 受付前のスパム・荒らし対策(設計書13.1): ハニーポット、Turnstile、IPハッシュ単位の件数制限、本文のURL数、NGワード。
 * 断るときは、利用者に見せる理由(文)を返す。
 */
final class SpamGuard
{
    public const TURNSTILE_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly IpHasher $hasher,
    ) {}

    /**
     * @param  list<string>  $texts  本文など、URL数と NG ワードを調べる文字列
     * @return array{field: string, message: string}|null 断る理由。通してよければ null
     */
    public function check(Request $request, array $texts): ?array
    {
        // ハニーポット: 人には見えない欄に何か入っていたら機械
        if ($request->filled('website')) {
            return ['field' => 'website', 'message' => __('submission.reject_spam')];
        }

        if (! $this->turnstile($request)) {
            return ['field' => 'turnstile', 'message' => __('submission.reject_turnstile')];
        }

        if ($this->overLimit($request)) {
            return ['field' => 'rate_limit', 'message' => __('submission.reject_rate', ['count' => $this->settings->int(SettingKey::SpamPostPerHour)])];
        }

        $text = implode("\n", $texts);
        $max = $this->settings->int(SettingKey::SpamMaxUrls);
        if ($this->urlCount($text) > $max) {
            return ['field' => 'urls', 'message' => __('submission.reject_urls', ['count' => $max])];
        }

        if ($this->containsNgWord($text)) {
            return ['field' => 'ng_word', 'message' => __('submission.reject_ng_word')];
        }

        return null;
    }

    /** 同じIPハッシュからの、直近1時間の受付件数が上限に達しているか */
    public function overLimit(Request $request): bool
    {
        $hash = $this->hasher->hash($request->ip());
        if ($hash === null) {
            return false;
        }

        return Submission::query()->where('ip_hash', $hash)->where('created_at', '>=', now()->subHour())->count() >= $this->settings->int(SettingKey::SpamPostPerHour);
    }

    public function urlCount(string $text): int
    {
        return (int) preg_match_all('#https?://#i', $text);
    }

    public function containsNgWord(string $text): bool
    {
        $normalized = mb_strtolower($text);

        foreach (NgWord::query()->get() as $word) {
            $needle = mb_strtolower($word->word);
            if ($needle === '') {
                continue;
            }
            $hit = $word->match_type === MatchType::Exact ? trim($normalized) === $needle : str_contains($normalized, $needle);
            if ($hit) {
                return true;
            }
        }

        return false;
    }

    /** Turnstile の確認。秘密鍵が未設定(開発環境など)のときは確認しない */
    private function turnstile(Request $request): bool
    {
        $secret = $this->settings->string(SettingKey::TurnstileSecretKey);
        if ($secret === '') {
            return true;
        }

        $token = $request->input('cf-turnstile-response');
        if (! is_string($token) || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post(self::TURNSTILE_URL, [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => (string) $request->ip(),
            ]);

            return $response->successful() && $response->json('success') === true;
        } catch (Throwable $e) {
            Log::warning('Turnstile の確認に失敗しました。', ['exception' => $e::class]);

            return false;
        }
    }
}
