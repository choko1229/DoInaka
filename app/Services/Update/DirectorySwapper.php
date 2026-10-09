<?php

declare(strict_types=1);

namespace App\Services\Update;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * アプリのディレクトリを丸ごと入れ替える(更新の「入れ替え」)。
 *
 * 新しい版は兄弟ディレクトリに展開してあり、次の順に動く:
 *   1. 引き継ぐもの(.env・storage)を、今のディレクトリから新しい方へ移す
 *   2. 今のディレクトリを「…-old-時刻」にし、新しい方を今の名前にする
 * 戻すときは、引き継いだものを元に戻して、名前を入れ替え直す。
 */
class DirectorySwapper
{
    /** @var list<string> */
    public const CARRY = ['.env', 'storage'];

    /**
     * @return string 退避した古い版のディレクトリ
     */
    public function swap(string $current, string $incoming, string $stamp): string
    {
        $old = $current.'-old-'.$stamp;
        $moved = [];

        try {
            foreach (self::CARRY as $item) {
                $from = $current.'/'.$item;
                if (! file_exists($from)) {
                    continue;
                }
                $to = $incoming.'/'.$item;
                $this->remove($to);
                $this->rename($from, $to);
                $moved[] = $item;
            }

            $this->rename($current, $old);

            try {
                $this->rename($incoming, $current);
            } catch (RuntimeException $e) {
                $this->rename($old, $current);

                throw $e;
            }
        } catch (RuntimeException $e) {
            // 途中で失敗したら、移した分を元に戻す(今のディレクトリが残っていればそこへ)
            foreach ($moved as $item) {
                if (file_exists($incoming.'/'.$item) && is_dir($current)) {
                    @rename($incoming.'/'.$item, $current.'/'.$item);
                }
            }

            throw $e;
        }

        return $old;
    }

    /**
     * swap を元に戻す。新しい版のディレクトリは取り除く。
     */
    public function rollback(string $current, string $old): void
    {
        foreach (self::CARRY as $item) {
            $from = $current.'/'.$item;
            if (! file_exists($from)) {
                continue;
            }
            $to = $old.'/'.$item;
            $this->remove($to);
            $this->rename($from, $to);
        }

        $failed = $current.'-failed-'.date('YmdHis');
        $this->rename($current, $failed);
        $this->rename($old, $current);
        $this->remove($failed);
    }

    public function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);
        } elseif (is_dir($path)) {
            File::deleteDirectory($path);
        }
    }

    private function rename(string $from, string $to): void
    {
        if (! @rename($from, $to)) {
            throw new RuntimeException(__('update.rename_failed', ['from' => basename($from), 'to' => basename($to)]));
        }
    }
}
