<?php

declare(strict_types=1);

namespace App\Services\Install;

use App\Data\CheckResult;
use App\Enums\CheckStatus;
use PDO;
use PDOException;

/**
 * インストーラーで入力された DB 接続の確認。
 * 接続・MySQL 8.0 以上・照合順序・ngram 全文検索・権限(CREATE・ALTER・INDEX)を試す。
 * 試すために作るテーブルは必ず消す。接続のパスワードはメッセージに出さない。
 */
class DatabaseChecker
{
    private const PROBE = 'doinaka_install_probe';

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: string}  $config
     * @return array{results: list<CheckResult>, ok: bool}
     */
    public function check(array $config): array
    {
        $results = [];

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
            );
        } catch (PDOException $e) {
            return [
                'results' => [new CheckResult(__('install.db_connect'), CheckStatus::Fail, $this->safeMessage($e, $config['password']))],
                'ok' => false,
            ];
        }

        $results[] = new CheckResult(__('install.db_connect'), CheckStatus::Ok);

        $version = (string) $this->scalar($pdo, 'SELECT VERSION()');
        $isMaria = stripos($version, 'mariadb') !== false;
        $results[] = ! $isMaria && version_compare($version, '8.0.0', '>=')
            ? new CheckResult(__('install.db_version'), CheckStatus::Ok, $version)
            : new CheckResult(__('install.db_version'), $isMaria ? CheckStatus::Warn : CheckStatus::Fail, $isMaria ? __('install.db_mariadb', ['version' => $version]) : __('install.db_old', ['version' => $version]));

        $collation = $this->scalar($pdo, "SHOW COLLATION LIKE 'utf8mb4_ja_0900_as_cs_ks'");
        $results[] = $collation !== null
            ? new CheckResult(__('install.db_collation'), CheckStatus::Ok)
            : new CheckResult(__('install.db_collation'), CheckStatus::Warn, __('install.db_collation_missing'));

        $results[] = $this->privileges($pdo);

        return ['results' => $results, 'ok' => ! in_array(CheckStatus::Fail, array_map(fn (CheckResult $r): CheckStatus => $r->status, $results), true)];
    }

    private function scalar(PDO $pdo, string $sql): ?string
    {
        $statement = $pdo->query($sql);
        $value = $statement === false ? false : $statement->fetchColumn();

        return is_scalar($value) ? (string) $value : null;
    }

    private function privileges(PDO $pdo): CheckResult
    {
        $label = __('install.db_privileges');
        $probe = self::PROBE;

        try {
            $pdo->exec("DROP TABLE IF EXISTS {$probe}");
            $pdo->exec("CREATE TABLE {$probe} (id INT NOT NULL PRIMARY KEY, body TEXT) DEFAULT CHARSET=utf8mb4");
            $pdo->exec("ALTER TABLE {$probe} ADD COLUMN extra VARCHAR(10) NULL");
            $pdo->exec("CREATE INDEX {$probe}_extra ON {$probe} (extra)");

            $ngram = true;
            try {
                $pdo->exec("ALTER TABLE {$probe} ADD FULLTEXT INDEX {$probe}_ft (body) WITH PARSER ngram");
            } catch (PDOException) {
                $ngram = false;
            }

            return $ngram
                ? new CheckResult($label, CheckStatus::Ok, __('install.db_ngram_ok'))
                : new CheckResult($label, CheckStatus::Warn, __('install.db_ngram_missing'));
        } catch (PDOException) {
            return new CheckResult($label, CheckStatus::Fail, __('install.db_privileges_missing'));
        } finally {
            try {
                $pdo->exec("DROP TABLE IF EXISTS {$probe}");
            } catch (PDOException) {
                // 消せなかった場合は、次の確認の冒頭で消す
            }
        }
    }

    private function safeMessage(PDOException $e, string $password): string
    {
        $message = $password === '' ? $e->getMessage() : str_replace($password, '********', $e->getMessage());

        return mb_substr($message, 0, 200);
    }
}
