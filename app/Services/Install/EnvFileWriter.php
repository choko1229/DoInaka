<?php

declare(strict_types=1);

namespace App\Services\Install;

use InvalidArgumentException;

/**
 * .env の中身を作る。値は必ず二重引用符で囲み、\ " $ をエスケープする。
 * 改行・制御文字を含む値は受け付けない(1行を超えて別の設定を書き込めないように)。
 */
final class EnvFileWriter
{
    /**
     * @param  array<string, string|int|bool>  $values
     */
    public function render(array $values): string
    {
        $lines = [];

        foreach ($values as $name => $value) {
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $name) !== 1) {
                throw new InvalidArgumentException(__('install.env_invalid_name'));
            }

            $lines[] = $name.'='.$this->quote($value);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, string|int|bool>  $values
     */
    public function write(string $path, array $values): void
    {
        $content = $this->render($values);

        // 先に 600 で空のファイルを作ってから書く(一瞬でも他の人に読ませない)
        $handle = fopen($path, 'cb');
        if ($handle === false) {
            throw new InvalidArgumentException(__('install.env_cannot_write'));
        }
        @chmod($path, 0600);
        ftruncate($handle, 0);
        fwrite($handle, $content);
        fclose($handle);
        @chmod($path, 0600);
    }

    private function quote(string|int|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value)) {
            return (string) $value;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new InvalidArgumentException(__('install.env_invalid_value'));
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }
}
