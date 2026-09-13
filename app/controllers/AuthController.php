<?php
/**
 * app/controllers/AuthController.php
 * Autenticação de usuários internos (Operador/Gestor).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class AuthController
{
    public function actionIndex(): void
    {
        if (AuthHelper::logado()) {
            Router::redirecionar(AuthHelper::ehGestor() ? 'dashboard' : 'kanban');
        }
        ViewHelper::render('auth/login', [
            'titulo' => 'Acesso à Operação',
        ], 'externo');
    }

    public function actionLogar(): void
    {
        CsrfHelper::checarRequisicao();

        $login = trim($_POST['login'] ?? $_POST['matricula'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');

        $erroLogin = null;
        $erroSenha = null;

        if ($login === '') {
            $erroLogin = 'Favor preencher o campo matrícula.';
        }
        if ($senha === '') {
            $erroSenha = 'Favor preencher o campo senha.';
        }

        if ($login === '' || $senha === '') {
            LogHelper::registrarSeguranca('LOGIN_CAMPOS_VAZIOS', 'Tentativa de login sem preenchimento.');
        } else {
            $usuario = UsuarioModel::buscarPorMatricula($login);
            if ($usuario === null) {
                $erroLogin = 'Matrícula inexistente. Consulte o Administrador.';
            } elseif ((int) $usuario['ativo'] !== 1) {
                $erroLogin = 'Conta desativada. Consulte o Administrador.';
            } elseif (!password_verify($senha, $usuario['senha_hash'])) {
                $erroSenha = 'Senha incorreta.';
            } else {
                AuthHelper::iniciarSessao($usuario);
                LogHelper::registrarSeguranca('LOGIN_SUCESSO', 'Login realizado.', (int) $usuario['id']);
                Router::redirecionar(AuthHelper::ehGestor() ? 'dashboard' : 'kanban');
            }
        }

        if ($erroLogin !== null) {
            LogHelper::registrarSeguranca('LOGIN_FALHA', 'Login informado: ' . $login);
        }

        ViewHelper::render('auth/login', [
            'titulo'    => 'Acesso à Operação',
            'erroLogin' => $erroLogin,
            'erroSenha' => $erroSenha,
            'login'     => $login,
        ], 'externo');
    }

    public function actionLogout(): void
    {
        if (AuthHelper::logado()) {
            LogHelper::registrarSeguranca('LOGOUT', 'Logout manual.', (int) AuthHelper::usuario('id'));
        }
        AuthHelper::logout();
        Router::redirecionar('login');
    }
}