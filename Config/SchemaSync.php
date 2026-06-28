<?php
namespace Config;

use PDO;
use PDOException;

class SchemaSync {
    public static function syncFromFile(PDO $pdo, $schemaFile) {
        if (!is_file($schemaFile)) {
            return false;
        }

        $sql = file_get_contents($schemaFile);
        if ($sql === false || trim($sql) === '') {
            return false;
        }

        $sql = self::stripComments($sql);
        $statements = array_filter(array_map('trim', preg_split('/;[\r\n]+|;\s*$/m', $sql)));
        $dbName = self::getCurrentDatabaseName($pdo);

        foreach ($statements as $stmt) {
            if ($stmt === '') {
                continue;
            }

            if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+ADD\s+COLUMN\s+(IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $stmt, $m)) {
                $table = $m[1];
                $column = $m[3];
                if (self::columnExists($pdo, $dbName, $table, $column)) {
                    continue;
                }
                $pdo->exec($stmt);
                continue;
            }

            if (preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?/i', $stmt)) {
                $pdo->exec($stmt);
                continue;
            }

            if (preg_match('/^INSERT\s+/i', $stmt)) {
                $pdo->exec($stmt);
                continue;
            }

            if (preg_match('/^USE\s+`?([a-zA-Z0-9_]+)`?/i', $stmt)) {
                continue;
            }

            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignore schema statements that are no longer applicable on an
                // existing database, but keep the app running.
                if (stripos($e->getMessage(), 'Duplicate') === false) {
                    error_log('[SchemaSync] ' . $e->getMessage() . ' | SQL: ' . $stmt);
                }
            }
        }

        return true;
    }

    private static function stripComments($sql) {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = preg_replace('/^\s*#.*$/m', '', $sql);
        return $sql;
    }

    private static function getCurrentDatabaseName(PDO $pdo) {
        try {
            return (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
        } catch (PDOException $e) {
            return '';
        }
    }

    private static function columnExists(PDO $pdo, $dbName, $table, $column) {
        if (!$dbName) {
            return false;
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $stmt->execute([$dbName, $table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
