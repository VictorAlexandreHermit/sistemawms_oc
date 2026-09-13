<?php
/**
 * app/controllers/EnderecosController.php
 * CRUD da estrutura física do galpão.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class EnderecosController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $termo = trim($_GET['q'] ?? '');
        $enderecos = EnderecoModel::listar($termo !== '' ? $termo : null);

        // Ocupação de cada endereço
        foreach ($enderecos as &$e) {
            $usado = 0;
            $linhas = EstoqueModel::listarPorEndereco((int) $e['id']);
            foreach ($linhas as $l) {
                $usado += (int) $l['quantidade'];
            }
            $e['total_ocupado'] = $usado;
            $e['percentual'] = $e['capacidade_maxima'] > 0 ? min(100, round(($usado / $e['capacidade_maxima']) * 100)) : 0;
        }
        unset($e);

        ViewHelper::render('enderecos/index', [
            'titulo'    => 'Endereços do Galpão',
            'subtitulo' => 'Estrutura Corredor-Galpão-Prateleira · C01-G01-P01 … · capacidade configurável por posição.',
            'enderecos' => $enderecos,
            'termo'     => $termo,
        ]);
    }

    public function actionNovo(): void
    {
        AuthHelper::requireLogin();
        ViewHelper::render('enderecos/formulario', [
            'titulo'    => 'Novo Endereço',
            'subtitulo' => '',
            'endereco'  => null,
        ]);
    }

    public function actionEditar(int $id): void
    {
        AuthHelper::requireLogin();
        $endereco = EnderecoModel::buscarPorId($id);
        if ($endereco === null) {
            ViewHelper::setFlash('erro', 'Endereço não encontrado.');
            Router::redirecionar('enderecos');
        }
        ViewHelper::render('enderecos/formulario', [
            'titulo'    => 'Editar Endereço',
            'subtitulo' => '',
            'endereco'  => $endereco,
        ]);
    }

    public function actionSalvar(?int $id = null): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $corredor    = trim($_POST['corredor'] ?? '');
        $galpao      = trim($_POST['galpao'] ?? '');
        $prateleira  = trim($_POST['prateleira'] ?? '');
        $cap         = (int) ($_POST['capacidade_maxima'] ?? 1000);

        if ($corredor === '' || $galpao === '' || $prateleira === '' || $cap <= 0) {
            ViewHelper::setFlash('erro', 'Corredor, galpão e prateleira são obrigatórios e a capacidade deve ser positiva.');
            Router::redirecionar($id ? 'enderecos/editar/' . $id : 'enderecos/novo');
        }

        if (preg_match('/^[A-Za-z0-9]{1,5}$/', $corredor) !== 1 ||
            preg_match('/^[A-Za-z0-9]{1,5}$/', $galpao) !== 1 ||
            preg_match('/^[A-Za-z0-9]{1,5}$/', $prateleira) !== 1) {
            ViewHelper::setFlash('erro', 'Corredor, galpão e prateleira aceitam até 5 caracteres alfanuméricos (padrão CORREDOR-GALPAO-PRATELEIRA).');
            Router::redirecionar($id ? 'enderecos/editar/' . $id : 'enderecos/novo');
        }

        EnderecoModel::salvar([
            'corredor'    => $corredor,
            'galpao'      => $galpao,
            'prateleira'  => $prateleira,
            'descricao'   => trim($_POST['descricao'] ?? ''),
            'capacidade_maxima' => $cap,
        ], $id);

        ViewHelper::setFlash('sucesso', 'Endereço ' . ($id ? 'atualizado' : 'criado') . ' com sucesso.');
        Router::redirecionar('enderecos');
    }

    public function actionExcluir(int $id): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();
        EnderecoModel::excluir($id);
        ViewHelper::setFlash('sucesso', 'Endereço removido (soft delete).');
        Router::redirecionar('enderecos');
    }
}