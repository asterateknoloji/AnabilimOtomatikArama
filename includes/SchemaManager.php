<?php

function ensureApplicationSchema(PDO $pdo)
{
    static $completed = false;
    if ($completed) {
        return [];
    }

    $schemaFile = dirname(__DIR__) . '/database/full_schema.sql';
    if (!is_file($schemaFile)) {
        throw new RuntimeException('Genel veritabanı şema dosyası bulunamadı.');
    }

    $schema = file_get_contents($schemaFile);
    preg_match_all(
        '/CREATE TABLE IF NOT EXISTS\s+`([^`]+)`\s*\((.*?)\)\s*ENGINE=.*?;/si',
        $schema,
        $matches,
        PREG_SET_ORDER
    );

    $changes = [];
    foreach ($matches as $match) {
        $tableName = $match[1];
        $table = table($tableName);

        // Eksik tabloyu bütün kolonları ve temel indeksleriyle oluştur.
        $pdo->exec($match[0]);

        $existingColumns = [];
        foreach ($pdo->query("SHOW COLUMNS FROM {$table}") as $column) {
            $existingColumns[strtolower($column['Field'])] = true;
        }

        $existingIndexes = [];
        foreach ($pdo->query("SHOW INDEX FROM {$table}") as $index) {
            $existingIndexes[strtolower($index['Key_name'])] = true;
        }

        foreach (preg_split('/\R/', $match[2]) as $line) {
            $line = trim($line);

            if (preg_match('/^`([^`]+)`\s+(.+?)(?:,\s*)?$/', $line, $columnMatch)) {
                $columnName = $columnMatch[1];
                if (!isset($existingColumns[strtolower($columnName)])) {
                    $definition = rtrim(trim($columnMatch[2]), ',');
                    $pdo->exec("ALTER TABLE {$table} ADD COLUMN `{$columnName}` {$definition}");
                    $existingColumns[strtolower($columnName)] = true;
                    $changes[] = "{$tableName}.{$columnName} kolonu eklendi";
                }
                continue;
            }

            if (preg_match('/^(UNIQUE\s+)?KEY\s+`([^`]+)`\s+(.+?)(?:,\s*)?$/i', $line, $indexMatch)) {
                $indexName = $indexMatch[2];
                if (!isset($existingIndexes[strtolower($indexName)])) {
                    $unique = trim((string)$indexMatch[1]) !== '' ? 'UNIQUE ' : '';
                    $definition = rtrim(trim($indexMatch[3]), ',');
                    $pdo->exec("ALTER TABLE {$table} ADD {$unique}INDEX `{$indexName}` {$definition}");
                    $existingIndexes[strtolower($indexName)] = true;
                    $changes[] = "{$tableName}.{$indexName} indeksi eklendi";
                }
            }
        }
    }

    $completed = true;
    return $changes;
}
