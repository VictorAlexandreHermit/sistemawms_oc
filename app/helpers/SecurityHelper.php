<?php
/**
 * app/helpers/SecurityHelper.php
 * Funções de sanitização, validação e utilitários de segurança.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class SecurityHelper
{
    /**
     * Escape de saída HTML (proteção XSS).
     */
    public static function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitiza o nome do arquivo removendo caracteres especiais e caminhos relativos.
     */
    public static function sanitizarNomeArquivo(string $nome): string
    {
        $nome = preg_replace('/[^A-Za-z0-9._\-]/', '_', $nome);
        $nome = str_replace(['../', '..\\', '/', '\\'], '_', $nome);
        return trim($nome, '_ .');
    }

    /**
     * Gera hash único (MD5 do conteúdo + timestamp) para renomear anexos.
     */
    public static function gerarNomeHash(string $conteudo, string $extensao): string
    {
        return md5($conteudo . '|' . time() . '|' . uniqid('', true)) . '.' . strtolower(ltrim($extensao, '.'));
    }

    /**
     * IP de origem do cliente.
     */
    public static function ipDoCliente(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $lista = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($lista[0]);
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function tokenAleatorio(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Converte um valor SIM/NAO em booleano normalizado (para lógica e exibição).
     */
    public static function simNaoParaBool(?string $valor): ?bool
    {
        if ($valor === null) {
            return null;
        }
        return strtoupper($valor) === 'SIM';
    }
}