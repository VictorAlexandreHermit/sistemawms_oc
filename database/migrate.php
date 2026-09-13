<?php
/**
 * database/migrate.php
 * Executor de migrations via CLI:
 *   php database/migrate.php
 *
 * Controla a execução através da tabela schema_migrations, impedindo a
 * reexecução de scripts já aplicados.
 */

if (PHP_SAPI !== 'cli') {
    die("Este script só pode ser executado via linha de comando.\n");
}

define('WMS_EXEC', true);
define('BASE_DIR', __DIR__ . '/..');

require_once BASE_DIR . '/config/config.php';
require_once BASE_DIR . '/config/database.php';

echo "=== WMS AGILIZA - Migrations ===\n";

try {
    $pdo = Database::conexao();
} catch (\Throwable $e) {
    echo "ERRO: Não foi possível conectar ao banco de dados.\n";
    echo "       Verifique o MySQL (XAMPP) e as credenciais em config/config.php.\n";
    echo "       " . $e->getMessage() . "\n";
    exit(1);
}

// Garante a tabela de controle de migrations
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$migrationsDir = __DIR__ . '/migrations';
$arquivos = glob($migrationsDir . '/*.sql');
sort($arquivos);

$aplicadas   = 0;
$ignoradas   = 0;
$listaAplicada = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration = :m');

foreach ($arquivos as $arquivo) {
    $nome = basename($arquivo);
    echo "  -> " . $nome . " ... ";

    $listaAplicada->execute([':m' => $nome]);
    if ($listaAplicada->fetch()) {
        echo "ignorada (já aplicada)\n";
        $ignoradas++;
        continue;
    }

$sql = file_get_contents($arquivo);
        if ($sql === false || trim($sql) === '') {
            echo "ERRO (arquivo vazio)\n";
            exit(1);
        }

        try {
            // Remove linhas de comentário SQL e executa cada statement
            $sqlSemComentarios = preg_replace('/^\s*--.*$/m', '', $sql);
            $declaracoes = array_filter(array_map('trim', explode(';', $sqlSemComentarios)));
            foreach ($declaracoes as $declaracao) {
                if ($declaracao === '') {
                    continue;
                }
                $pdo->exec($declaracao);
            }

        $registra = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)');
        $registra->execute([':m' => $nome]);

        echo "aplicada\n";
        $aplicadas++;
    } catch (\Throwable $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\nResumo: {$aplicadas} migration(s) aplicada(s), {$ignoradas} ignorada(s), " . count($arquivos) . " arquivo(s) total.\n";
echo "Concluído.\n";