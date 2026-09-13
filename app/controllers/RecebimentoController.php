<?php
/**
 * app/controllers/RecebimentoController.php
 * Recebimento e entrada (Inbound): upload de XML, conferência cega e
 * fila de exceções do Gestor.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class RecebimentoController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $pendentes = PedidoModel::listarPorStatus('RECEBIDO');

        ViewHelper::render('recebimento/index', [
            'titulo'    => 'Recebimento de Mercadorias',
            'subtitulo' => 'Importe o XML da NF-e e execute a conferência cega por bipagem.',
            'pendentes' => $pendentes,
        ]);
    }

    public function actionImportar(): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        if (!isset($_FILES['xml_nota']) || ($_FILES['xml_nota']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            ViewHelper::setFlash('erro', 'Selecione um arquivo XML de NF-e para importar.');
            Router::redirecionar('recebimento');
        }

        try {
            $xml = XmlHelper::lerNFe($_FILES['xml_nota']);
            $pedidoId = PedidoModel::criarDoXml($xml);

            $_SESSION['conferencia_pedido'] = $pedidoId;
            ViewHelper::setFlash('sucesso', 'XML importado com sucesso. NF-e ' . SecurityHelper::e($xml['numero_nota']) . ' pronta para conferência cega.');
            Router::redirecionar('recebimento/conferir/' . $pedidoId);
        } catch (RuntimeException $e) {
            ViewHelper::setFlash('erro', $e->getMessage());
            Router::redirecionar('recebimento');
        }
    }

    /**
     * Entrada manual por código de barras (sem XML): uma ou mais linhas de
     * produto + quantidade. Códigos que ainda não existem no catálogo são
     * cadastrados automaticamente com um SKU interno gerado pelo sistema.
     * A carga é liberada direto para a Guarda.
     */
    public function actionEntradaManual(): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $fornecedor = trim($_POST['fornecedor'] ?? '');
        $codigos    = $_POST['codigo_barras'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];
        $descricoes = $_POST['descricao'] ?? [];
        $curvas     = $_POST['curva_abc'] ?? [];

        if (!is_array($codigos) || empty($codigos)) {
            ViewHelper::setFlash('erro', 'Informe ao menos um item (código de barras) para a entrada manual.');
            Router::redirecionar('recebimento');
        }

        $itens   = [];
        $erros   = [];
        $criados = 0;
        $totalLinhas = count($codigos);
        for ($i = 0; $i < $totalLinhas; $i++) {
            $codigo = trim((string) ($codigos[$i] ?? ''));
            $qtd    = (int) ($quantidades[$i] ?? 0);
            $desc   = trim((string) ($descricoes[$i] ?? ''));
            $curva  = strtoupper(trim((string) ($curvas[$i] ?? '')));
            if (!in_array($curva, ['A', 'B', 'C'], true)) {
                $curva = 'C';
            }
            if ($codigo === '') {
                continue;
            }
            if ($qtd <= 0) {
                $erros[] = 'Quantidade inválida para o código "' . $codigo . '".';
                continue;
            }
            try {
                $produto = ProdutoModel::obterOuCriarManual($codigo, $desc, $curva);
            } catch (RuntimeException $e) {
                $erros[] = $e->getMessage();
                continue;
            }
            if ($produto === null) {
                $erros[] = 'Não foi possível cadastrar o código "' . $codigo . '".';
                continue;
            }
            $itens[] = ['produto_id' => (int) $produto['id'], 'quantidade' => $qtd];
        }

        if (empty($itens)) {
            ViewHelper::setFlash('erro', empty($erros)
                ? 'Nenhum item válido informado na entrada manual.'
                : implode(' ', array_slice($erros, 0, 3)));
            Router::redirecionar('recebimento');
        }

        try {
            $pedidoId = PedidoModel::criarManual($itens, $fornecedor);
        } catch (RuntimeException $e) {
            ViewHelper::setFlash('erro', $e->getMessage());
            Router::redirecionar('recebimento');
        }

        $mensagem = 'Mercadorias adicionadas com sucesso.';
        if (!empty($erros)) {
            $mensagem .= ' Alguns itens foram ignorados: ' . implode(' ', array_slice($erros, 0, 2));
            ViewHelper::setFlash('aviso', $mensagem);
        } else {
            ViewHelper::setFlash('sucesso', $mensagem);
        }
        Router::redirecionar('recebimento');
    }

    public function actionConferir(int $pedidoId): void
    {
        AuthHelper::requireLogin();

        $pedido = PedidoModel::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] === 'ENTREGUE') {
            ViewHelper::setFlash('erro', 'Carga não encontrada.');
            Router::redirecionar('recebimento');
        }

        $_SESSION['conferencia_pedido'] = (int) $pedidoId;

        ViewHelper::render('recebimento/conferencia', [
            'titulo'    => 'Conferência Cega',
            'subtitulo' => 'Nota ' . SecurityHelper::e($pedido['numero_nota_xml']) . ' · ' . SecurityHelper::e($pedido['cliente_nome']) . ' — Não exibimos as quantidades esperadas.',
            'pedido'    => $pedido,
            'itens'     => PedidoModel::itens($pedidoId),
        ]);
    }

    public function actionBipar(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $codigo = trim($_POST['codigo'] ?? '');

        if ($codigo === '') {
            ViewHelper::setFlash('erro', 'Leia ou digite um código de barras.');
            Router::redirecionar('recebimento/conferir/' . $pedidoId);
        }

        $r = PedidoModel::conferirBipagem($pedidoId, $codigo);
        ViewHelper::setFlash($r['ok'] ? 'sucesso' : 'aviso', $r['mensagem']);
        Router::redirecionar('recebimento/conferir/' . $pedidoId);
    }

    public function actionDesfazer(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo === '') {
            ViewHelper::setFlash('erro', 'Informe o código para desfazer a última bipagem do item.');
            Router::redirecionar('recebimento/conferir/' . $pedidoId);
        }

        $produto = ProdutoModel::buscarPorCodigoBarras($codigo);
        if ($produto === null) {
            ViewHelper::setFlash('erro', 'Produto não encontrado.');
            Router::redirecionar('recebimento/conferir/' . $pedidoId);
        }

        PedidoModel::desfazerBipagem($pedidoId, (int) $produto['id']);
        ViewHelper::setFlash('aviso', 'Bipagem desfeita para este produto.');
        Router::redirecionar('recebimento/conferir/' . $pedidoId);
    }

    public function actionFinalizar(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $resumo = PedidoModel::finalizarConferencia($pedidoId);
        unset($_SESSION['conferencia_pedido']);

        if ($resumo['divergencias'] > 0) {
            ViewHelper::setFlash('aviso',
                'Conferência finalizada: ' . $resumo['itens_ok'] . ' item(ns) conferido(s) e ' .
                $resumo['divergencias'] . ' divergência(s) enviada(s) à aprovação do Gestor.');
        } else {
            ViewHelper::setFlash('sucesso',
                'Conferência 100% validada (' . $resumo['itens_ok'] . ' item(ns)). Carga liberada para a Guarda (Putaway).');
        }
        Router::redirecionar('guarda');
    }

    // =============================================================
    // Fila de exceções (Gestor)
    // =============================================================

    public function actionExcecoes(): void
    {
        AuthHelper::requirePerfil('GESTOR');

        ViewHelper::render('recebimento/excecoes', [
            'titulo'    => 'Fila de Exceções do Recebimento',
            'subtitulo' => 'Divergências entre o físico e o XML aguardando aprovação.',
            'pendentes' => DivergenciaModel::pendentes(),
        ]);
    }

    public function actionAprovar(int $id): void
    {
        AuthHelper::requirePerfil('GESTOR');
        CsrfHelper::checarRequisicao();

        $d = DivergenciaModel::buscarPorId($id);
        if ($d === null) {
            ViewHelper::setFlash('erro', 'Divergência não encontrada.');
            Router::redirecionar('recebimento/excecoes');
        }

        DivergenciaModel::aprovar($id, (int) AuthHelper::usuario('id'));
        ViewHelper::setFlash('sucesso', 'Divergência aprovada. Entrada consolidada conforme conferência física.');
        Router::redirecionar('recebimento/excecoes');
    }

    public function actionRejeitar(int $id): void
    {
        AuthHelper::requirePerfil('GESTOR');
        CsrfHelper::checarRequisicao();

        $d = DivergenciaModel::buscarPorId($id);
        if ($d === null) {
            ViewHelper::setFlash('erro', 'Divergência não encontrada.');
            Router::redirecionar('recebimento/excecoes');
        }

        DivergenciaModel::rejeitar($id, (int) AuthHelper::usuario('id'));
        ViewHelper::setFlash('aviso', 'Divergência rejeitada. O saldo seguirá a conferência física registrada.');
        Router::redirecionar('recebimento/excecoes');
    }
}