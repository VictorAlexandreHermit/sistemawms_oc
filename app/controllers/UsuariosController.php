<?php
/**
 * app/controllers/UsuariosController.php
 * Gestão de contas de acesso (exclusivo do perfil ADMINISTRADOR).
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class UsuariosController
{
    private const PERFIS = ['OPERADOR', 'GESTOR', 'ADMINISTRADOR'];

    public function actionIndex(): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');

        ViewHelper::render('usuarios/index', [
            'titulo'    => 'Usuários & Contas',
            'subtitulo' => 'Crie e administre os logins de acesso ao WMS.',
            'usuarios'  => UsuarioModel::listar(false),
            'perfis'    => self::PERFIS,
            'idAtual'   => (int) AuthHelper::usuario('id'),
        ]);
    }

    public function actionSalvar(): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');
        CsrfHelper::checarRequisicao();

        $dados = [
            'matricula'     => strtoupper(trim($_POST['matricula'] ?? '')),
            'nome_completo' => trim($_POST['nome_completo'] ?? ''),
            'perfil'        => strtoupper(trim($_POST['perfil'] ?? '')),
            'senha'         => (string) ($_POST['senha'] ?? ''),
        ];

        if ($dados['matricula'] === '' || $dados['nome_completo'] === '') {
            ViewHelper::setFlash('erro', 'Matrícula (login) e nome completo são obrigatórios.');
            Router::redirecionar('usuarios');
        }
        if (!in_array($dados['perfil'], self::PERFIS, true)) {
            ViewHelper::setFlash('erro', 'Selecione um perfil válido.');
            Router::redirecionar('usuarios');
        }
        if (strlen($dados['senha']) < 6) {
            ViewHelper::setFlash('erro', 'A senha deve ter pelo menos 6 caracteres.');
            Router::redirecionar('usuarios');
        }
        if (UsuarioModel::matriculaExiste($dados['matricula'])) {
            ViewHelper::setFlash('erro', 'Já existe uma conta com essa matrícula.');
            Router::redirecionar('usuarios');
        }

        UsuarioModel::criar($dados);
        ViewHelper::setFlash('sucesso', 'Login ' . $dados['matricula'] . ' criado com sucesso.');
        Router::redirecionar('usuarios');
    }

    public function actionAtivar(int $id): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');
        CsrfHelper::checarRequisicao();
        $usuario = UsuarioModel::buscarPorId($id);
        if ($usuario === null) {
            ViewHelper::setFlash('erro', 'Usuário não encontrado.');
        } else {
            UsuarioModel::atualizarStatus($id, 1);
            ViewHelper::setFlash('sucesso', 'Acesso de ' . $usuario['matricula'] . ' reativado.');
        }
        Router::redirecionar('usuarios');
    }

    public function actionDesativar(int $id): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');
        CsrfHelper::checarRequisicao();

        if ((int) $id === (int) AuthHelper::usuario('id')) {
            ViewHelper::setFlash('erro', 'Você não pode desativar a própria conta.');
            Router::redirecionar('usuarios');
        }

        $usuario = UsuarioModel::buscarPorId($id);
        if ($usuario === null) {
            ViewHelper::setFlash('erro', 'Usuário não encontrado.');
        } else {
            UsuarioModel::atualizarStatus($id, 0);
            ViewHelper::setFlash('sucesso', 'Acesso de ' . $usuario['matricula'] . ' desativado.');
        }
        Router::redirecionar('usuarios');
    }

    public function actionResetarSenha(int $id): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');
        CsrfHelper::checarRequisicao();

        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        if (strlen($novaSenha) < 6) {
            ViewHelper::setFlash('erro', 'A nova senha deve ter pelo menos 6 caracteres.');
            Router::redirecionar('usuarios');
        }

        $usuario = UsuarioModel::buscarPorId($id);
        if ($usuario === null) {
            ViewHelper::setFlash('erro', 'Usuário não encontrado.');
        } else {
            UsuarioModel::atualizarSenha($id, $novaSenha);
            ViewHelper::setFlash('sucesso', 'Senha de ' . $usuario['matricula'] . ' redefinida.');
        }
        Router::redirecionar('usuarios');
    }

    public function actionExcluir(int $id): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');
        CsrfHelper::checarRequisicao();

        if ((int) $id === (int) AuthHelper::usuario('id')) {
            ViewHelper::setFlash('erro', 'Você não pode excluir a própria conta.');
            Router::redirecionar('usuarios');
        }

        $usuario = UsuarioModel::buscarPorId($id);
        if ($usuario === null) {
            ViewHelper::setFlash('erro', 'Usuário não encontrado.');
        } else {
            UsuarioModel::excluir($id);
            ViewHelper::setFlash('sucesso', 'Conta de ' . $usuario['matricula'] . ' excluída permanentemente.');
        }
        Router::redirecionar('usuarios');
    }
}