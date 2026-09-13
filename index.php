<?php
/*
 * [Diretório do Projeto - Repositório]/index.php
 * Arquivo de entrada único da aplicação (Front Controller).
 */

define('WMS_EXEC', true);
define('BASE_DIR', __DIR__);

// =============================================================
// Autoload de classes (helpers, models e controllers)
// =============================================================
spl_autoload_register(function ($class) {
    $nome     = strtolower($class);
    $diretorio = null;

    if (strpos($nome, 'controller') !== false) {
        $diretorio = '/app/controllers/';
    } elseif (strpos($nome, 'model') !== false) {
        $diretorio = '/app/models/';
    } else {
        $diretorio = '/app/helpers/';
    }

    $arquivo = BASE_DIR . $diretorio . $class . '.php';
    if (is_file($arquivo)) {
        require_once $arquivo;
    }
});

// =============================================================
// Configuração global
// =============================================================
$CONFIG = require_once BASE_DIR . '/config/config.php';
define('APP_CONFIG', $CONFIG);

foreach ($CONFIG['app'] as $chave => $valor) {
    define('CFG_' . strtoupper($chave), $valor);
}

// Timezone
date_default_timezone_set($CONFIG['app']['timezone']);

// =============================================================
// Sessão
// =============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name('WMSAGILIZA');
    $httpsAtivo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $httpsAtivo,
    ]);
    session_start();
}

// =============================================================
// Tratamento de erros global (mensagens amigáveis + logs)
// =============================================================
require_once BASE_DIR . '/app/helpers/LogHelper.php';
error_reporting(E_ALL);
ini_set('display_errors', '0');
LogHelper::registerHandlers();
LogHelper::initContingencyFile();
register_shutdown_function([LogHelper::class, 'shutdownHandler']);

// =============================================================
// Conexão com o banco de dados (PDO)
// =============================================================
require_once BASE_DIR . '/config/database.php';

// =============================================================
// Roteamento
// =============================================================
require_once BASE_DIR . '/app/helpers/Router.php';
Router::dispatch();