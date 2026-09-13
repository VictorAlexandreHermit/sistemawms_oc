<?php
/**
 * app/helpers/AuthHelper.php
 * Controle de sessão, autenticação e RBAC.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class AuthHelper
{
    private const TEMPO_INATIVIDADE = CFG_SESSAO_INATIVIDADE_SEGUNDOS;

    public static function iniciarSessao(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario_id']    = (int) $usuario['id'];
        $_SESSION['usuario_nome']  = $usuario['nome_completo'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];
        $_SESSION['ultima_atividade'] = time();
    }

    public static function logado(): bool
    {
        if (empty($_SESSION['usuario_id'])) {
            return false;
        }

        // Expiração automática por inatividade
        if (isset($_SESSION['ultima_atividade'])) {
            if (time() - (int) $_SESSION['ultima_atividade'] > self::TEMPO_INATIVIDADE) {
                self::logout();
                return false;
            }
        }
        $_SESSION['ultima_atividade'] = time();
        return true;
    }

    public static function usuario(?string $chave = null)
    {
        if (!self::logado()) {
            return null;
        }
        $dados = [
            'id'        => $_SESSION['usuario_id'],
            'nome_completo' => $_SESSION['usuario_nome'],
            'perfil'    => $_SESSION['usuario_perfil'],
        ];
        return $chave === null ? $dados : ($dados[$chave] ?? null);
    }

    public static function perfil(): ?string
    {
        return isset($_SESSION['usuario_perfil']) ? $_SESSION['usuario_perfil'] : null;
    }

    public static function ehGestor(): bool
    {
        return self::perfil() === 'GESTOR';
    }

    public static function ehOperador(): bool
    {
        return self::perfil() === 'OPERADOR';
    }

    /**
     * Exige usuário autenticado; redireciona para /login caso contrário.
     */
    public static function requireLogin(): void
    {
        if (!self::logado()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Exige perfil específico; registra tentativa de acesso negado.
     */
    public static function requirePerfil(array|string $perfisPermitidos): void
    {
        self::requireLogin();
        $perfis = is_array($perfisPermitidos) ? array_map('strtoupper', $perfisPermitidos) : [strtoupper($perfisPermitidos)];

        if (!in_array(self::perfil(), $perfis, true)) {
            LogHelper::registrarSeguranca(
                'ACESSO_NEGADO_PERFIL',
                'Tentativa de acesso a rota restrita. Perfil atual: ' . (self::perfil() ?? 'NULL'),
                (int) $_SESSION['usuario_id']
            );
            http_response_code(403);
            ViewHelper::render('templates/acesso_negado', [], 'externo');
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        @session_destroy();
    }
}