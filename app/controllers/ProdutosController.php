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
            $p['localizacoes'] = EstoqueModel::consultarPorProduto($p['codigo_barras']);
        }
        unset($p);

        ViewHelper::render('produtos/index', [
            'titulo'    => 'Catálogo de Produtos',
            'subtitulo' => 'SKU, código de barras, estoque e onde cada mercadoria está armazenada (endereçamento).',
            'produtos'  => $produtos,
            'termo'     => $termo,
        ]);
    }

    public function actionNovo(): void
    {
        AuthHelper::requireLogin();
        ViewHelper::render('produtos/formulario', [
            'titulo'      => 'Novo Produto',
            'subtitulo'   => '',
            'produto'     => null,
            'diretorio'   => EnderecoModel::diretorio(),
            'corredores'  => self::corredoresParaFormulario(),
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
            'unidade_medida' => strtoupper(trim($_POST['unidade_medida'] ?? 'UN')),
            'curva_abc'      => strtoupper(trim($_POST['curva_abc'] ?? 'C')),
        ];

        $quantidadeInicial = max(0, (int) ($_POST['quantidade_inicial'] ?? 0));
        $corredor   = strtoupper(trim($_POST['corredor'] ?? ''));
        $prateleira = strtoupper(trim($_POST['prateleira'] ?? ''));

        if ($dados['sku'] === '' || $dados['codigo_barras'] === '') {
            ViewHelper::setFlash('erro', 'SKU e código de barras são obrigatórios.');
            Router::redirecionar($id ? 'produtos/editar/' . $id : 'produtos/novo');
        }
        if (!in_array($dados['curva_abc'], ['A', 'B', 'C'], true)) {
            $dados['curva_abc'] = 'C';
        }
        if ($id === null && $quantidadeInicial > 0 && ($corredor === '' || $prateleira === '')) {
            ViewHelper::setFlash('erro', 'Informe corredor e prateleira para cadastrar o estoque inicial.');
            Router::redirecionar('produtos/novo');
        }

        $novoId = ProdutoModel::salvar($dados, $id);

        if ($id === null && $quantidadeInicial > 0) {
            $endereco = EnderecoModel::obterOuCriarPorCorredorPrateleira($corredor, $prateleira);
            EstoqueModel::entrada($novoId, (int) $endereco['id'], $quantidadeInicial);
        }

        ViewHelper::setFlash('sucesso', 'Produto ' . ($id ? 'atualizado' : 'criado') . ' com sucesso.');
        Router::redirecionar('produtos');
    }

    public function actionExcluir(int $id): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');
        CsrfHelper::checarRequisicao();
        ProdutoModel::excluir($id);
        ViewHelper::setFlash('sucesso', 'Produto removido (soft delete) de todo o sistema (estoque, kanban, guarda e separação).');
        Router::redirecionar('produtos');
    }

    private static function corredoresParaFormulario(): array
    {
        $corredores = array_map(function (array $reg) {
            return $reg['corredor'];
        }, EnderecoModel::diretorio());
        return array_values(array_unique($corredores));
    }
}