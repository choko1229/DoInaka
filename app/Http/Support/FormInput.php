<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Http\Request;

/**
 * 検証を通ったあとのフォーム入力を、型つきで取り出す(PHPStan max で mixed を扱わないための薄い包み)。
 */
final readonly class FormInput
{
    public function __construct(private Request $request) {}

    public function string(string $key): string
    {
        return $this->request->string($key)->trim()->toString();
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->string($key);

        return $value === '' ? null : $value;
    }

    public function int(string $key): int
    {
        return $this->request->integer($key);
    }

    public function nullableInt(string $key): ?int
    {
        $raw = $this->request->input($key);

        return is_numeric($raw) ? (int) $raw : null;
    }

    public function bool(string $key): bool
    {
        return $this->request->boolean($key);
    }

    /**
     * 緯度経度などの小数。空なら null。
     */
    public function nullableDecimal(string $key): ?string
    {
        $raw = $this->request->input($key);

        return is_numeric($raw) ? (string) $raw : null;
    }

    /**
     * 行のくり返し(日程・情報元)。空の行(すべての欄が空)は捨てる。
     *
     * @param  list<string>  $fields
     * @return list<array<string, mixed>>
     */
    public function rows(string $key, array $fields, string $mustHave): array
    {
        $rows = [];
        $input = $this->request->input($key, []);
        if (! is_array($input)) {
            return [];
        }

        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }
            $clean = [];
            foreach ($fields as $field) {
                $value = $row[$field] ?? null;
                $clean[$field] = is_string($value) ? trim($value) : $value;
                if ($clean[$field] === '') {
                    $clean[$field] = null;
                }
            }
            if (($clean[$mustHave] ?? null) === null) {
                continue;
            }
            $rows[] = $clean;
        }

        return $rows;
    }

    /**
     * 「,」や改行で区切られたタグ。
     *
     * @return list<string>
     */
    public function tags(string $key): array
    {
        $parts = preg_split('/[,、\n]+/u', $this->string($key)) ?: [];

        return array_values(array_unique(array_filter(array_map(fn (string $p): string => mb_substr(trim($p), 0, 60), $parts), fn (string $p): bool => $p !== '')));
    }
}
