<?php

declare(strict_types=1);

namespace App\Services\Install;

use App\Data\CheckResult;
use App\Enums\CheckStatus;
use Imagick;

/**
 * 動作環境の確認(doinaka-check.php と同じ判定。実装指示書 2章)。
 * .htaccess の php_value が実際に効いているか(ini の現在値)も見る。
 */
class EnvironmentChecker
{
    private const REQUIRED_EXTENSIONS = ['mbstring', 'pdo_mysql', 'fileinfo', 'openssl', 'curl', 'ctype', 'json', 'tokenizer', 'xml', 'dom', 'zip', 'intl'];

    public function __construct(private readonly string $basePath) {}

    /**
     * @return list<CheckResult>
     */
    public function run(): array
    {
        $results = [];

        $results[] = version_compare(PHP_VERSION, '8.3.0', '>=')
            ? new CheckResult(__('install.check_php'), CheckStatus::Ok, PHP_VERSION)
            : new CheckResult(__('install.check_php'), CheckStatus::Fail, __('install.check_php_old', ['version' => PHP_VERSION]));

        $missing = array_values(array_filter(self::REQUIRED_EXTENSIONS, fn (string $ext): bool => ! extension_loaded($ext)));
        $results[] = $missing === []
            ? new CheckResult(__('install.check_extensions'), CheckStatus::Ok)
            : new CheckResult(__('install.check_extensions'), CheckStatus::Fail, __('install.check_missing', ['list' => implode(', ', $missing)]));

        $results[] = $this->image();

        $results[] = $this->iniAtLeast('memory_limit', 256, __('install.check_memory'));
        $results[] = $this->iniAtLeast('upload_max_filesize', 10, __('install.check_upload'));
        $results[] = $this->iniAtLeast('post_max_size', 110, __('install.check_post'));

        $display = strtolower((string) ini_get('display_errors'));
        $results[] = in_array($display, ['', '0', 'off', 'false', 'stderr'], true)
            ? new CheckResult(__('install.check_display_errors'), CheckStatus::Ok)
            : new CheckResult(__('install.check_display_errors'), CheckStatus::Fail, __('install.check_display_errors_on'));

        foreach (['storage', 'bootstrap/cache'] as $directory) {
            $results[] = is_writable($this->basePath.'/'.$directory)
                ? new CheckResult(__('install.check_writable', ['path' => $directory]), CheckStatus::Ok)
                : new CheckResult(__('install.check_writable', ['path' => $directory]), CheckStatus::Fail, __('install.check_not_writable'));
        }

        $results[] = is_writable($this->basePath) || is_file($this->basePath.'/.env')
            ? new CheckResult(__('install.check_env_writable'), CheckStatus::Ok)
            : new CheckResult(__('install.check_env_writable'), CheckStatus::Fail, __('install.check_not_writable'));

        $results[] = is_file($this->basePath.'/public/.htaccess')
            ? new CheckResult(__('install.check_htaccess'), CheckStatus::Ok)
            : new CheckResult(__('install.check_htaccess'), CheckStatus::Warn, __('install.check_htaccess_missing'));

        $results[] = config('app.debug') === true
            ? new CheckResult(__('install.check_debug'), CheckStatus::Warn, __('install.check_debug_on'))
            : new CheckResult(__('install.check_debug'), CheckStatus::Ok);

        return $results;
    }

    /**
     * @param  list<CheckResult>  $results
     */
    public function passes(array $results): bool
    {
        foreach ($results as $result) {
            if ($result->status === CheckStatus::Fail) {
                return false;
            }
        }

        return true;
    }

    private function image(): CheckResult
    {
        $label = __('install.check_image');

        if (extension_loaded('imagick') && class_exists(Imagick::class)) {
            $formats = array_map('strtoupper', Imagick::queryFormats('*'));
            if (in_array('WEBP', $formats, true)) {
                return in_array('HEIC', $formats, true)
                    ? new CheckResult($label, CheckStatus::Ok, 'Imagick: WebP, HEIC')
                    : new CheckResult($label, CheckStatus::Warn, __('install.check_no_heic'));
            }
        }

        if (extension_loaded('gd') && function_exists('imagewebp')) {
            return new CheckResult($label, CheckStatus::Warn, __('install.check_gd_only'));
        }

        return new CheckResult($label, CheckStatus::Fail, __('install.check_no_webp'));
    }

    private function iniAtLeast(string $name, int $megabytes, string $label): CheckResult
    {
        $raw = (string) ini_get($name);
        $bytes = $this->toBytes($raw);

        if ($bytes === -1 || $bytes >= $megabytes * 1024 * 1024) {
            return new CheckResult($label, CheckStatus::Ok, $raw);
        }

        return new CheckResult($label, CheckStatus::Fail, __('install.check_ini_low', ['name' => $name, 'value' => $raw, 'need' => $megabytes.'M']));
    }

    public function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return -1;
        }
        if (preg_match('/^(\d+)\s*([KMG]?)B?$/i', $value, $m) !== 1) {
            return 0;
        }

        $number = (int) $m[1];

        return match (strtoupper($m[2])) {
            'K' => $number * 1024,
            'M' => $number * 1024 * 1024,
            'G' => $number * 1024 * 1024 * 1024,
            default => $number,
        };
    }
}
