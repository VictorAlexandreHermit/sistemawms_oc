<?php
/**
 * app/controllers/KanbanController.php
 * Quadro operacional: colunas com priorização ABC e alerta visual de SLA.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class KanbanController
{
    private const COLUNAS = ['RECEBIDO', 'A_ARMAZENAR', 'A_SEPARAR', 'A_EXPEDIR'];

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
            'subtitulo' => 'Recebido → A Armazenar → A Separar → A Expedir · borda amarela aos 80% do SLA, vermelha ao estourar.',
            'colunas'   => $colunas,
            'limites'   => $limites,
        ]);
    }
}