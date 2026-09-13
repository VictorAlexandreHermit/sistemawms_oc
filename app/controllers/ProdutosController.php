<?php
/**
 * app/controllers/ProdutosController.php
 * CRUD do catálogo de produtos.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class ProdutosController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $termo = trim($_GET['q'] ?? '');

        $produtos = ProdutoModel::listar($termo !== '' ? $termo : null);
        foreach ($produtos as &$p) {
            $p['saldo_total'] = EstoqueModel::saldoTotalDisponivel((int) $p['id']);
        }
        unset($p);

        ViewHelper::render('produtos/index', [
            'titulo'    => 'Catálogo de Produtos',
            'subtitulo' => 'SKU, código de barras, unidade e Curva ABC para a priorização do pick.',
            'produtos'  => $produtos,
            'termo'     => $termo,
        ]);
    }

    public function actionNovo(): void
    {
        AuthHelper::requireLogin();
        ViewHelper::render('produtos/formulario', [
            'titulo'    => 'Novo Produto',
            'subtitulo' => '',
            'produto'   => null,
        ]);
    }

    public function actionEditar(int $id): void
    {
        AuthHelper::requireLogin();
        $produto = ProdutoModel::buscarPorId($id);
        if ($produto === null) {
            ViewHelper::setFlash('erro', 'Produto não encontrado.');
            Router::redirecionar('produtos');
        }
        ViewHelper::render('produtos/formulario', [
            'titulo'    => 'Editar Produto',
            'subtitulo' => '',
            'produto'   => $produto,
        ]);
    }

    public function actionSalvar(?int $id = null): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $dados = [
            'sku'            => trim($_POST['sku'] ?? ''),
            'codigo_barras'  => trim($_POST['codigo_barras'] ?? ''),
            'descricao'      => trim($_POST['descricao'] ?? ''),
            'unidade_medida' => trim($_POST['unidade_medida'] ?? 'UN'),
            'curva_abc'      => strtoupper(trim($_POST['curva_abc'] ?? 'C')),
        ];

        if ($dados['sku'] === '' || $dados['codigo_barras'] === '') {
            ViewHelper::setFlash('erro', 'SKU e código de barras são obrigatórios.');
            Router::redirecionar($id ? 'produtos/editar/' . $id : 'produtos/novo');
        }
        if (!in_array($dados['curva_abc'], ['A', 'B', 'C'], true)) {
            $dados['curva_abc'] = 'C';
        }

        ProdutoModel::salvar($dados, $id);
        ViewHelper::setFlash('sucesso', 'Produto ' . ($id ? 'atualizado' : 'criado') . ' com sucesso.');
        Router::redirecionar('produtos');
    }

    public function actionExcluir(int $id): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();
        ProdutoModel::excluir($id);
        ViewHelper::setFlash('sucesso', 'Produto removido (soft delete).');
        Router::redirecionar('produtos');
    }
}