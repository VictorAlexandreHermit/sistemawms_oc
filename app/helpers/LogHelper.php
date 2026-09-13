<?php
/**
 * app/helpers/LogHelper.php
 * Gravação de logs de erro e segurança com contingência em arquivo.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class LogHelper
{
    private static string $dirLogs = BASE_DIR . '/logs';

    public static function initContingencyFile(): void
    {
        if (!is_dir(self::$dirLogs)) {
            @mkdir(self::$dirLogs, 0755, true);
        }
        if (!is_file(self::$dirLogs . '/error.log')) {
            @file_put_contents(self::$dirLogs . '/error.log', "# Log de contingência do WMS Agiliza\n", LOCK_EX);
        }
        if (!is_file(self::$dirLogs . '/security.log')) {
            @file_put_contents(self::$dirLogs . '/security.log', "# Log de segurança do WMS Agiliza\n", LOCK_EX);
        }
    }

    public static function registerHandlers(): void
    {
        set_exception_handler(function ($e) {
            self::registrarErro($e);
        });

        set_error_handler(function ($severidade, $mensagem, $arquivo, $linha) {
            throw new ErrorException($mensagem, 0, $severidade, $arquivo, $linha);
        });
    }

    public static function shutdownHandler(): void
    {
        $erro = error_get_last();
        if ($erro && in_array($erro['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $e = new ErrorException($erro['message'], 0, $erro['type'], $erro['file'], $erro['line']);
            self::registrarErro($e);
        }
    }

    /**
     * Grava o erro primariamente na tabela MySQL e, em caso de falha da
     * conexão, escrita em arquivo físico local (contingência).
     */
    public static function registrarErro(\Throwable $e): void
    {
        $mensagem = $e->getMessage();
        $arquivo  = $e->getFile();
        $linha    = $e->getLine();
        $trace    = $e->getTraceAsString();
        $usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;

        try {
            $pdo = Database::conexao();
            $sql = 'INSERT INTO logs_erro (mensagem, arquivo, linha, trace, usuario_id)
                    VALUES (:mensagem, :arquivo, :linha, :trace, :usuario_id)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':mensagem'   => substr($mensagem, 0, 2000),
                ':arquivo'    => $arquivo,
                ':linha'      => $linha,
                ':trace'      => substr($trace, 0, 4000),
                ':usuario_id' => $usuarioId,
            ]);
        } catch (\Throwable $falhaBanco) {
            self::escreverContingencia('error.log', sprintf(
                "[%s] ERRO: %s em %s:%d\n%s\n",
                date('Y-m-d H:i:s'),
                $mensagem,
                $arquivo,
                $linha,
                $trace
            ));
        }
    }

    /**
     * Grava eventos de segurança (falhas de login, acesso negado, etc.).
     */
    public static function registrarSeguranca(string $evento, ?string $detalhes = null, ?int $usuarioId = null): void
    {
        $ip = SecurityHelper::ipDoCliente();

        try {
            $pdo = Database::conexao();
            $sql = 'INSERT INTO logs_seguranca (evento, ip_origem, usuario_id, detalhes)
                    VALUES (:evento, :ip, :usuario_id, :detalhes)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':evento'     => $evento,
                ':ip'         => $ip,
                ':usuario_id' => $usuarioId,
                ':detalhes'   => $detalhes !== null ? substr($detalhes, 0, 2000) : null,
            ]);
        } catch (\Throwable $f) {
            self::escreverContingencia('security.log', sprintf(
                "[%s] SEGURANÇA: %s | IP=%s | USUARIO=%s\n",
                date('Y-m-d H:i:s'),
                $evento,
                $ip,
                $usuarioId ?? '-'
            ));
        }
    }

    private static function escreverContingencia(string $arquivo, string $linha): void
    {
        $caminho = self::$dirLogs . '/' . $arquivo;
        @file_put_contents($caminho, $linha, FILE_APPEND | LOCK_EX);
    }
}