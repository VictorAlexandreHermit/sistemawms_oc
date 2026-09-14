<?php
/**
 * app/controllers/PedidosController.php
 * Simulação de Pedidos de Venda: cria pedidos a partir do estoque armazenado
 * e é a ÚNICA porta de entrada para o fluxo de Picking.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class PedidosController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $vendas = PedidoModel::listarVendas();
        foreach ($vendas as &$p) {
            $p['sla'] = PedidoModel::classificarSla($p);
            $p['itens_count'] = count(PedidoModel::itens((int) $p['id']));
        }
        unset($p);

        $produtos = [];
        foreach (ProdutoModel::listar() as $p) {
            $p['saldo'] = EstoqueModel::saldoTotalDisponivel((int) $p['id']);
            if ($p['saldo'] > 0) {
                $produtos[] = $p;
            }
        }

        ViewHelper::render('pedidos/index', [
            'titulo'    => 'Pedidos de Venda',
            'subtitulo' => 'Simule um pedido do cliente: só assim uma mercadoria armazenada entra no Picking.',
            'vendas'    => $vendas,
            'produtos'  => $produtos,
        ]);
    }

    public function actionCriar(): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $cliente  = trim($_POST['cliente'] ?? '');
        $contato  = trim($_POST['contato'] ?? '');
        $produtos = $_POST['produto'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];

        $itens = [];
        $quantas = is_array($produtos) ? count($produtos) : 0;
        for ($i = 0; $i < $quantas; $i++) {
            $pid = (int) ($produtos[$i] ?? 0);
            $qtd = max(1, (int) ($quantidades[$i] ?? 0));
            if ($pid > 0) {
                $itens[] = ['produto_id' => $pid, 'quantidade' => $qtd];
            }
        }

        if ($cliente === '') {
            ViewHelper::setFlash('erro', 'Informe o nome do cliente do pedido.');
            Router::redirecionar('pedidos');
        }
        if (empty($itens)) {
            ViewHelper::setFlash('erro', 'Adicione ao menos um produto com quantidade ao pedido.');
            Router::redirecionar('pedidos');
        }

        try {
            $pedidoId = PedidoModel::criarVenda($itens, $cliente, $contato);
            ViewHelper::setFlash('sucesso', 'Pedido ' . PedidoModel::buscarPorId($pedidoId)['numero_nota_xml'] . ' aberto para o Picking.');
            Router::redirecionar('pedidos');
        } catch (RuntimeException $e) {
            ViewHelper::setFlash('erro', $e->getMessage());
            Router::redirecionar('pedidos');
        }
    }

    /**
     * Cancela um pedido de venda ainda aberto (antes da conclusão do Picking,
     * quando o estoque ainda não foi baixado).
     */
    public function actionCancelar(int $pedidoId): void
    {
        AuthHelper::requireLogin();
        CsrfHelper::checarRequisicao();

        $pedido = PedidoModel::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['tipo'] !== 'VENDA') {
            ViewHelper::setFlash('erro', 'Pedido de venda não encontrado.');
            Router::redirecionar('pedidos');
        }
        if ($pedido['status_kanban'] !== 'A_SEPARAR') {
            ViewHelper::setFlash('erro', 'Só é possível cancelar um pedido ainda aberto no Picking.');
            Router::redirecionar('pedidos');
        }

        PedidoModel::excluir($pedidoId);
        ViewHelper::setFlash('aviso', 'Pedido de venda cancelado (estoque não foi baixado).');
        Router::redirecionar('pedidos');
    }
}