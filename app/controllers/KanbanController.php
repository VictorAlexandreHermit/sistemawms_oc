<?php
/**
 * app/controllers/KanbanController.php
 * Quadro operacional: colunas com priorização ABC e alerta visual de SLA.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class KanbanController
{
    private const COLUNAS = ['RECEBIDO', 'A_ARMAZENAR', 'ARMAZENADO', 'A_SEPARAR', 'A_EMBALAR', 'A_EXPEDIR', 'EM_TRANSITO'];

    public function actionIndex(): void
    {
        AuthHelper::requireLogin();

        $pedidos = PedidoModel::listarKanban();

        $colunas = [];
        foreach (self::COLUNAS as $etapa) {
            $colunas[$etapa] = [];
        }

        foreach ($pedidos as $p) {
            $p['sla'] = PedidoModel::classificarSla($p);
            $p['itens_count'] = count(PedidoModel::itens((int) $p['id']));
            $colunas[$p['status_kanban']][] = $p;
        }

        $limites = [];
        foreach (ConfigSlaModel::todos() as $sla) {
            $limites[$sla['etapa_kanban']] = (int) $sla['tempo_limite_minutos'];
        }

        ViewHelper::render('kanban/index', [
            'titulo'    => 'Quadro Operacional (Kanban)',
            'subtitulo' => 'Recebimento → Guarda → Armazenado → Picking → Packing → Expedição → Entrega · na coluna Armazenado, abra o pedido de venda para liberar ao Picking · borda amarela aos 80% do SLA, vermelha ao estourar.',
            'colunas'   => $colunas,
            'limites'   => $limites,
        ]);
    }

    /**
     * Administrador remove por completo um processo travado no fluxo
     * (pedido + itens + OTIF + divergências + avarias vinculadas).
     */
    public function actionExcluir(int $pedidoId): void
    {
        AuthHelper::requirePerfil('ADMINISTRADOR');
        CsrfHelper::checarRequisicao();

        $pedido = PedidoModel::buscarPorId($pedidoId);
        if ($pedido === null) {
            ViewHelper::setFlash('erro', 'Processo não encontrado no fluxo.');
            Router::redirecionar('kanban');
        }

        PedidoModel::excluir($pedidoId);
        ViewHelper::setFlash('sucesso', 'Processo ' . SecurityHelper::e($pedido['numero_nota_xml']) . ' removido de todo o fluxo.');
        Router::redirecionar('kanban');
    }
}