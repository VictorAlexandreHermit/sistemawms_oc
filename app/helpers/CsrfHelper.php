<?php
/**
 * app/helpers/CsrfHelper.php
 * Proteção contra CSRF (Cross-Site Request Forgery).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class CsrfHelper
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = SecurityHelper::tokenAleatorio(32);
        }
        return $_SESSION['csrf_token'];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . SecurityHelper::e(self::token()) . '">';
    }

    public static function validar(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Valida o token presente em $_POST. Em caso de falha, registra no log de
     * segurança e finaliza a requisição com HTTP 403 (Use: em actions POST).
     */
    public static function checarRequisicao(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!self::validar($token)) {
            LogHelper::registrarSeguranca('TENTATIVA_CSRF', 'Token CSRF inválido.' . (isset($_SESSION['usuario_id']) ? ' Usuario=' . $_SESSION['usuario_id'] : ''));
            http_response_code(403);
            exit('Token de segurança expirado. Recarregue a página e tente novamente.');
        }
    }
}