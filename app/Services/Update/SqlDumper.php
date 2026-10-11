<?php

declare(strict_types=1);

namespace App\Services\Update;

use Illuminate\Database\Connection;
use PDO;
use RuntimeException;

/**
 * DB のダンプと復元(PHP だけで行う。レンタルサーバーに mysqldump があるとは限らないため)。
 *
 * 形式は「1行1文」の SQL。行のデータの改行・引用符は PDO::quote が \n などに直すので、行で分けても壊れない。
 * セッション・キャッシュの中身は、戻すときに要らないので構造だけを保存する。
 */
class SqlDumper
{
    private const STRUCTURE_ONLY = ['sessions', 'cache', 'cache_locks'];

    private const CHUNK = 500;

    /**
     * @param  list<string>|null  $only  指定すると、そのテーブルだけ(テスト用)。null なら全テーブル
     * @return int 書き出したテーブル数
     */
    public function dump(Connection $db, string $path, ?array $only = null): int
    {
        $pdo = $db->getPdo();
        $handle = @fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException(__('update.cannot_write', ['path' => basename($path)]));
        }

        $count = 0;

        try {
            // 全テーブルのダンプには印を付ける(戻すとき、ダンプにないテーブルを消してよいのは、全テーブルのダンプのときだけ)
            fwrite($handle, "-- doinaka dump\n".($only === null ? "-- scope: all\n" : '')."SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");

            foreach ($this->tables($pdo) as $table) {
                if ($only !== null && ! in_array($table, $only, true)) {
                    continue;
                }

                $quoted = '`'.str_replace('`', '``', $table).'`';
                fwrite($handle, "DROP TABLE IF EXISTS {$quoted};\n");

                $create = $pdo->query("SHOW CREATE TABLE {$quoted}");
                $row = $create === false ? false : $create->fetch(PDO::FETCH_NUM);
                if (! is_array($row) || ! is_string($row[1] ?? null)) {
                    throw new RuntimeException(__('update.dump_failed', ['table' => $table]));
                }
                fwrite($handle, preg_replace('/\s*\R\s*/', ' ', $row[1]).";\n");

                if (! in_array($table, self::STRUCTURE_ONLY, true)) {
                    $this->dumpRows($pdo, $handle, $table, $quoted);
                }
                $count++;
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        return $count;
    }

    public function restore(Connection $db, string $path): int
    {
        $pdo = $db->getPdo();
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException(__('update.cannot_read', ['path' => basename($path)]));
        }

        $statements = 0;

        // ダンプにないテーブル(更新で新しい版の migration が作ったもの)が残ると、やり直しの migrate が「already exists」で落ちる。先に消す
        $this->dropExtraTables($db, $path);

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '--')) {
                    continue;
                }
                $pdo->exec($line);
                $statements++;
            }
        } finally {
            fclose($handle);
        }

        return $statements;
    }

    /**
     * 戻すときに消すテーブル: 全テーブルのダンプ(印あり)に書かれていないテーブル。
     * 更新で新しい版の migration が作ったテーブルがあたる(残ると、やり直しの migrate が「already exists」で落ちる)。
     * 部分のダンプ・印のないダンプ・空のダンプのときは、何も消さない。
     *
     * @return list<string>
     */
    public function extraTables(Connection $db, string $path): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }
        $known = [];
        $full = false;
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '-- scope: all') {
                $full = true;
            } elseif (preg_match('/^DROP TABLE IF EXISTS `((?:[^`]|``)+)`;/', $line, $m) === 1) {
                $known[str_replace('``', '`', $m[1])] = true;
            }
        }
        fclose($handle);

        if (! $full || $known === []) {
            return [];
        }

        return array_values(array_filter($this->tables($db->getPdo()), fn (string $table): bool => ! isset($known[$table])));
    }

    private function dropExtraTables(Connection $db, string $path): void
    {
        $extra = $this->extraTables($db, $path);
        if ($extra === []) {
            return;
        }
        $pdo = $db->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($extra as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `'.str_replace('`', '``', $table).'`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @return list<string>
     */
    private function tables(PDO $pdo): array
    {
        $statement = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = [];
        foreach ($statement === false ? [] : $statement->fetchAll(PDO::FETCH_NUM) as $row) {
            if (is_array($row) && is_string($row[0] ?? null)) {
                $tables[] = $row[0];
            }
        }

        return $tables;
    }

    /**
     * @param  resource  $handle
     */
    private function dumpRows(PDO $pdo, $handle, string $table, string $quoted): void
    {
        // 大きなテーブルでもメモリを食わないよう、1行ずつ読む(読み終えるまで、この接続で別のクエリは出さない)
        $buffered = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            $result = $pdo->query("SELECT * FROM {$quoted}");
            if ($result === false) {
                return;
            }

            $this->writeRows($pdo, $handle, $quoted, $result);
        } finally {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered === false ? false : true);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function writeRows(PDO $pdo, $handle, string $quoted, \PDOStatement $result): void
    {
        $columns = '';
        $batch = [];

        while (($row = $result->fetch(PDO::FETCH_ASSOC)) !== false) {
            $columns = $columns !== '' ? $columns : implode(',', array_map(fn ($c): string => '`'.str_replace('`', '``', (string) $c).'`', array_keys($row)));
            $values = array_map(function (mixed $value) use ($pdo): string {
                if ($value === null) {
                    return 'NULL';
                }

                return (string) $pdo->quote(is_scalar($value) ? (string) $value : '');
            }, array_values($row));
            $batch[] = '('.implode(',', $values).')';

            if (count($batch) >= self::CHUNK) {
                fwrite($handle, "INSERT INTO {$quoted} ({$columns}) VALUES ".implode(',', $batch).";\n");
                $batch = [];
            }
        }

        if ($batch !== []) {
            fwrite($handle, "INSERT INTO {$quoted} ({$columns}) VALUES ".implode(',', $batch).";\n");
        }
    }
}
