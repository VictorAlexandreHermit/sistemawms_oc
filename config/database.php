<?php
/**
 * [Diretório do Projeto - Repositório]/config/database.php
 * Conexão PDO segura com MySQL. Garante que o bootstrap já tenha sido executado.
 */

if (!defined('WMS_EXEC')) {
    @http_response_code(403);
    die('Acesso direto não permitido.');
}

final class Database
{
    private static ?PDO $instancia = null;
    private static array $config = [];

    public static function configurar(array $config): void
    {
        self::$config = $config;
    }

    public static function conexao(): PDO
    {
        if (self::$instancia instanceof PDO) {
            return self::$instancia;
        }

        if (empty(self::$config)) {
            throw new RuntimeException('Configuração do banco de dados não fornecida.');
        }

        $c = self::$config;
        $dsn = 'mysql:host=' . $c['host'] . ';dbname=' . $c['dbname'] . ';charset=' . $c['charset'];

        self::$instancia = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]);

        return self::$instancia;
    }

    public static function resetar(): void
    {
        self::$instancia = null;
    }
}

$CONFIG = $GLOBALS['WMS_CONFIG'] ?? [];
Database::configurar(isset($CONFIG['db']) ? $CONFIG['db'] : []);
unset($CONFIG);