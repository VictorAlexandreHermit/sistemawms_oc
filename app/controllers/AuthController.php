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

        if ($login === '' || $senha === '') {
            LogHelper::registrarSeguranca('LOGIN_CAMPOS_VAZIOS', 'Tentativa de login sem preenchimento.');
            ViewHelper::setFlash('erro', 'Informe login e senha.');
            Router::redirecionar('login');
        }

        $usuario = UsuarioModel::autenticar($login, $senha);

        if ($usuario === null) {
            LogHelper::registrarSeguranca('LOGIN_FALHA', 'Login informado: ' . $login);
            ViewHelper::setFlash('erro', 'Login ou senha inválidos.');
            Router::redirecionar('login');
        }

        AuthHelper::iniciarSessao($usuario);
        LogHelper::registrarSeguranca('LOGIN_SUCESSO', 'Login realizado.', (int) $usuario['id']);

        Router::redirecionar(AuthHelper::ehGestor() ? 'dashboard' : 'kanban');
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